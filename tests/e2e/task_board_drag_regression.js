const { chromium } = require('../../node_modules/playwright');

async function login(page, baseUrl, username, password) {
  await page.goto(baseUrl + '/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]')
  ]);
}

async function fetchCard(page, cardId) {
  return page.evaluate(async (id) => {
    const response = await fetch((window.BASE_PATH || '') + '/api/task/cards/' + id, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      cache: 'no-store'
    });
    return response.json();
  }, cardId);
}

async function moveCardByApi(page, cardId, listId, position) {
  return page.evaluate(async (payload) => {
    const response = await fetch((window.BASE_PATH || '') + '/api/task/cards/' + payload.cardId + '/order', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({ list_id: payload.listId, position: payload.position })
    });
    return response.json();
  }, { cardId, listId, position });
}

async function moveCardBySortableCallback(page, cardId, sourceListId, targetListId) {
  return page.evaluate(async (payload) => {
    const sourceContainer = document.getElementById('cards-' + payload.sourceListId);
    const targetContainer = document.getElementById('cards-' + payload.targetListId);
    const card = sourceContainer && sourceContainer.querySelector(`.kanban-card[data-card-id="${payload.cardId}"]`);

    if (!sourceContainer || !targetContainer || !card || typeof Sortable === 'undefined') {
      return { success: false, reason: 'missing_dom_or_sortable' };
    }

    const targetSortable = Sortable.get(targetContainer);
    if (!targetSortable) {
      return { success: false, reason: 'missing_sortable_instance' };
    }

    const oldIndex = Array.from(sourceContainer.querySelectorAll('.kanban-card')).indexOf(card);
    targetContainer.insertBefore(card, targetContainer.querySelector('.kanban-card'));

    const onEnd = targetSortable.option('onEnd');
    if (typeof onEnd !== 'function') {
      return { success: false, reason: 'missing_on_end' };
    }

    onEnd({
      item: card,
      from: sourceContainer,
      to: targetContainer,
      oldIndex,
      newIndex: 0
    });

    return { success: true };
  }, { cardId, sourceListId, targetListId });
}

async function run() {
  const baseUrl = process.env.GW_BASE_URL || 'http://192.168.1.5/groupware';
  const username = process.env.GW_USERNAME || 'admin';
  const password = process.env.GW_PASSWORD || 'admin123';
  const boardId = process.env.GW_BOARD_ID || '1';

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 1440, height: 1200 }
  });
  const page = await context.newPage();
  page.setDefaultTimeout(30000);

  try {
    await login(page, baseUrl, username, password);
    await page.goto(baseUrl + '/task/board/' + boardId, { waitUntil: 'networkidle' });
    await page.waitForFunction(() => typeof TaskBoard !== 'undefined' && TaskBoard._initialized === true && typeof Sortable !== 'undefined');

    const info = await page.evaluate(() => {
      const lists = Array.from(document.querySelectorAll('.kanban-list')).map((list) => ({
        listId: list.getAttribute('data-list-id'),
        cardIds: Array.from(list.querySelectorAll('.kanban-card')).map((card) => card.getAttribute('data-card-id'))
      }));

      const source = lists.find((list) => list.cardIds.length > 0);
      const target = lists.find((list) => source && list.listId !== source.listId);

      return {
        source,
        target
      };
    });

    if (!info.source || !info.target) {
      throw new Error('Need at least two lists and one card to verify drag and drop');
    }

    const sourceListId = info.source.listId;
    const targetListId = info.target.listId;
    const cardId = info.source.cardIds[0];

    const responsePromise = page.waitForResponse((response) => {
      return response.url().includes(`/api/task/cards/${cardId}/order`) && response.request().method() === 'POST';
    });

    const simulated = await moveCardBySortableCallback(page, cardId, sourceListId, targetListId);
    if (!simulated.success) {
      throw new Error('Failed to simulate Sortable onEnd callback: ' + simulated.reason);
    }
    const response = await responsePromise;
    if (!response.ok()) {
      throw new Error('Drag and drop order API returned non-OK status: ' + response.status());
    }

    await page.waitForTimeout(1500);
    const moved = await fetchCard(page, cardId);
    const movedListId = String(moved.data.list_id || (moved.data.card && moved.data.card.list_id) || '');
    if (movedListId !== String(targetListId)) {
      throw new Error(`Card did not move to target list. expected=${targetListId} actual=${movedListId}`);
    }

    const restore = await moveCardByApi(page, cardId, sourceListId, 0);
    if (!restore.success) {
      throw new Error('Failed to restore dragged card after verification');
    }

    console.log('task_board_drag_regression: OK');
  } finally {
    await browser.close();
  }
}

run().catch((error) => {
  console.error('task_board_drag_regression: FAIL');
  console.error(error && error.stack ? error.stack : String(error));
  process.exit(1);
});
