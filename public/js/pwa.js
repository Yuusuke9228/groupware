(function () {
  const PWA = {
    deferredInstallPrompt: null,
    swRegistration: null,
    config: null,
    initialized: false,
    installBannerTimer: null,
    storageKeys: {
      dismissedUntil: 'ts_pwa_install_dismissed_until',
      mutedUntil: 'ts_pwa_install_muted_until',
      installedAt: 'ts_pwa_installed_at'
    },

    init: async function () {
      if (this.initialized) {
        return;
      }
      this.initialized = true;

      if (!('serviceWorker' in navigator)) {
        return;
      }

      await this.fetchConfig();
      if (!this.config || !this.config.pwa_enabled) {
        return;
      }

      await this.registerServiceWorker();
      this.bindInstallPrompt();
      this.bindSecurityPageButtons();
    },

    fetchConfig: async function () {
      try {
        const res = await fetch(`${BASE_PATH}/api/pwa/config`, {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) {
          return;
        }
        const json = await res.json();
        if (json && json.success) {
          this.config = json.data || null;
        }
      } catch (error) {
        // ignore
      }
    },

    registerServiceWorker: async function () {
      try {
        const reg = await navigator.serviceWorker.register(`${BASE_PATH}/service-worker.js`, {
          scope: `${BASE_PATH}/`
        });
        this.swRegistration = reg;
      } catch (error) {
        // ignore
      }
    },

    bindInstallPrompt: function () {
      window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        this.deferredInstallPrompt = event;
        this.maybeShowInstallBanner();
      });

      window.addEventListener('appinstalled', () => {
        this.deferredInstallPrompt = null;
        this.setStoredTs(this.storageKeys.installedAt, Date.now());
        this.dismissInstallBanner('installed');
      });
    },

    maybeShowInstallBanner: function () {
      if (!this.canShowInstallBanner()) {
        return;
      }

      window.setTimeout(() => this.showInstallBanner(), 1200);
    },

    canShowInstallBanner: function () {
      if (!this.deferredInstallPrompt || this.isStandaloneMode()) {
        return false;
      }
      const now = Date.now();
      const installedAt = this.getStoredTs(this.storageKeys.installedAt);
      const dismissedUntil = this.getStoredTs(this.storageKeys.dismissedUntil);
      const mutedUntil = this.getStoredTs(this.storageKeys.mutedUntil);
      return !(installedAt > 0 || dismissedUntil > now || mutedUntil > now);
    },

    getStoredTs: function (key) {
      try {
        return Number(localStorage.getItem(key) || 0);
      } catch (error) {
        return 0;
      }
    },

    setStoredTs: function (key, ts) {
      try {
        localStorage.setItem(key, String(ts));
      } catch (error) {
        // ignore
      }
    },

    isStandaloneMode: function () {
      return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    },

    dismissInstallBanner: function (reason) {
      const banner = document.getElementById('pwaInstallBanner');
      if (banner) {
        banner.remove();
      }

      if (this.installBannerTimer) {
        window.clearTimeout(this.installBannerTimer);
        this.installBannerTimer = null;
      }

      const now = Date.now();
      if (reason === 'installed') {
        this.setStoredTs(this.storageKeys.dismissedUntil, now + (365 * 24 * 60 * 60 * 1000));
        return;
      }
      if (reason === 'closed') {
        this.setStoredTs(this.storageKeys.dismissedUntil, now + (30 * 24 * 60 * 60 * 1000));
        return;
      }
      this.setStoredTs(this.storageKeys.mutedUntil, now + (3 * 24 * 60 * 60 * 1000));
    },

    showInstallBanner: function () {
      if (!this.canShowInstallBanner() || document.getElementById('pwaInstallBanner')) {
        return;
      }

      const banner = document.createElement('div');
      banner.id = 'pwaInstallBanner';
      banner.style.position = 'fixed';
      banner.style.right = '12px';
      banner.style.bottom = '72px';
      banner.style.zIndex = '1100';
      banner.style.background = '#111827';
      banner.style.color = '#fff';
      banner.style.padding = '10px 12px';
      banner.style.borderRadius = '12px';
      banner.style.boxShadow = '0 10px 24px rgba(0,0,0,.28)';
      banner.style.fontSize = '13px';
      banner.style.maxWidth = '260px';
      banner.innerHTML = `
        <div style="margin-bottom:8px;line-height:1.35;">このアプリをインストールできます</div>
        <div style="display:flex;gap:8px;">
          <button id="pwaInstallNow" class="btn btn-sm btn-light">インストール</button>
          <button id="pwaInstallClose" class="btn btn-sm btn-outline-light">閉じる</button>
        </div>
      `;
      document.body.appendChild(banner);

      document.getElementById('pwaInstallNow')?.addEventListener('click', async () => {
        if (!this.deferredInstallPrompt) {
          return;
        }
        this.deferredInstallPrompt.prompt();
        const choice = await this.deferredInstallPrompt.userChoice;
        this.deferredInstallPrompt = null;
        if (choice && choice.outcome === 'accepted') {
          this.dismissInstallBanner('installed');
          return;
        }
        this.dismissInstallBanner('muted');
      });
      document.getElementById('pwaInstallClose')?.addEventListener('click', () => this.dismissInstallBanner('closed'));

      this.installBannerTimer = window.setTimeout(() => this.dismissInstallBanner('timeout'), 12000);
    },

    bindSecurityPageButtons: function () {
      const enableBtn = document.getElementById('btnEnableBrowserPush');
      const disableBtn = document.getElementById('btnDisableBrowserPush');
      if (enableBtn) {
        enableBtn.addEventListener('click', async () => {
          const ok = await this.subscribePush();
          alert(ok ? 'Push購読を有効化しました。' : 'Push購読の有効化に失敗しました。');
        });
      }
      if (disableBtn) {
        disableBtn.addEventListener('click', async () => {
          const ok = await this.unsubscribePush();
          alert(ok ? 'Push購読を解除しました。' : 'Push購読解除に失敗しました。');
        });
      }
    },

    subscribePush: async function () {
      if (!this.config || !this.config.push_enabled) {
        return false;
      }
      if (!('PushManager' in window) || !this.swRegistration) {
        return false;
      }
      try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
          return false;
        }

        let subscription = await this.swRegistration.pushManager.getSubscription();
        if (!subscription) {
          const vapidPublicKey = this.config.vapid_public_key || '';
          if (!vapidPublicKey) {
            return false;
          }
          const convertedVapidKey = this.urlBase64ToUint8Array(vapidPublicKey);
          subscription = await this.swRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: convertedVapidKey
          });
        }

        const res = await fetch(`${BASE_PATH}/api/pwa/subscribe`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(subscription.toJSON())
        });
        const json = await res.json();
        return !!json.success;
      } catch (error) {
        return false;
      }
    },

    unsubscribePush: async function () {
      if (!this.swRegistration || !('PushManager' in window)) {
        return false;
      }
      try {
        const subscription = await this.swRegistration.pushManager.getSubscription();
        if (!subscription) {
          return true;
        }

        await fetch(`${BASE_PATH}/api/pwa/unsubscribe`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ endpoint: subscription.endpoint })
        });
        await subscription.unsubscribe();
        return true;
      } catch (error) {
        return false;
      }
    },

    urlBase64ToUint8Array: function (base64String) {
      const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
      const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
      const rawData = window.atob(base64);
      const outputArray = new Uint8Array(rawData.length);
      for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
      }
      return outputArray;
    }
  };

  window.TeamSpacePWA = PWA;
  document.addEventListener('DOMContentLoaded', () => PWA.init());
})();
