(function () {
    'use strict';

    function byId(id) {
        return document.getElementById(id);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function nl2br(value) {
        return escapeHtml(value).replace(/\n/g, '<br>');
    }

    function formatDateTime(value) {
        if (!value) return '';
        var date = new Date(value.replace(' ', 'T'));
        if (isNaN(date.getTime())) return '';
        var mm = String(date.getMonth() + 1).padStart(2, '0');
        var dd = String(date.getDate()).padStart(2, '0');
        var hh = String(date.getHours()).padStart(2, '0');
        var mi = String(date.getMinutes()).padStart(2, '0');
        return mm + '/' + dd + ' ' + hh + ':' + mi;
    }

    var dataNode = byId('chatBootstrapData');
    if (!dataNode) return;

    var bootstrap;
    try {
        bootstrap = JSON.parse(dataNode.textContent || '{}');
    } catch (error) {
        return;
    }

    var state = {
        basePath: bootstrap.basePath || '',
        userId: Number(bootstrap.userId || 0),
        roomId: Number(bootstrap.roomId || 0),
        csrfToken: bootstrap.csrfToken || '',
        i18n: bootstrap.i18n || {},
        lastMessageId: 0,
        notifiedMessageIds: {},
        pollTimer: null,
        roomsTimer: null,
    };

    var messageWrap = byId('chatMessages');
    var messageArea = byId('chatMessageArea');
    var messageForm = byId('chatMessageForm');
    var messageInput = byId('chatMessageInput');
    var attachmentInput = byId('chatAttachmentInput');
    var attachmentNameNode = byId('chatAttachmentName');
    var roomList = byId('chatRoomList');
    var chatShell = byId('chatShell');
    var chatMobileBackBtn = byId('chatMobileBackBtn');
    var readModalBody = byId('chatReadDetailModalBody');
    var readModalTitle = byId('chatReadDetailModalTitle');
    var readModalEl = byId('chatReadDetailModal');
    var readModal = null;
    var explicitRoomRequested = /(?:^|[?&])room_id=\d+/.test(window.location.search);

    function t(key, fallback) {
        if (state.i18n && state.i18n[key]) {
            return String(state.i18n[key]);
        }
        return fallback || key;
    }

    function isMobileScreen() {
        return window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
    }

    function shouldActivateRoom() {
        if (!state.roomId) return false;
        if (isMobileScreen() && !explicitRoomRequested) return false;
        return true;
    }

    function applyMobileLayoutMode() {
        if (!chatShell) return;
        chatShell.classList.remove('mobile-list', 'mobile-room');
        if (!isMobileScreen()) return;
        if (shouldActivateRoom()) {
            chatShell.classList.add('mobile-room');
        } else {
            chatShell.classList.add('mobile-list');
        }
    }

    if (Array.isArray(bootstrap.messages) && bootstrap.messages.length > 0) {
        var last = bootstrap.messages[bootstrap.messages.length - 1];
        state.lastMessageId = Number(last.id || 0);
        bootstrap.messages.forEach(function (m) {
            state.notifiedMessageIds[String(m.id)] = true;
        });
    }

    function scrollToBottom(force) {
        if (!messageArea) return;
        if (force || (messageArea.scrollHeight - messageArea.scrollTop - messageArea.clientHeight < 140)) {
            messageArea.scrollTop = messageArea.scrollHeight;
        }
    }

    function updateChatBadge(count) {
        var badge = byId('chatModuleBadge');
        var mobile = byId('chatMobileBadge');
        var value = Number(count || 0);

        [badge, mobile].forEach(function (node) {
            if (!node) return;
            if (value > 0) {
                node.textContent = String(value);
                node.classList.remove('d-none');
            } else {
                node.textContent = '0';
                node.classList.add('d-none');
            }
        });
    }

    function buildAttachmentHtml(message) {
        if (!message || !message.attachment_path) return '';
        var name = message.attachment_name || 'attachment';
        var href = state.basePath + '/chat/files/' + Number(message.id) + '/download';
        return '' +
            '<div class="chat-attachment">' +
            '<a href="' + href + '">' +
            '<i class="fas fa-paperclip me-1"></i>' + escapeHtml(name) +
            '</a>' +
            '</div>';
    }

    function initialText(value) {
        var text = String(value || '').trim();
        if (!text) return 'C';
        return text.charAt(0);
    }

    function buildMessageHtml(message) {
        var mine = Number(message.user_id || 0) === state.userId;
        var readByOthers = Math.max(0, Number(message.read_count || 0) - 1);
        var textHtml = message.message_text ? ('<div>' + nl2br(message.message_text) + '</div>') : '';
        var readHtml = (mine && readByOthers > 0)
            ? ('<div class="chat-read"><button type="button" class="chat-read-btn" data-message-id="' + Number(message.id || 0) + '">' + escapeHtml(t('read', 'Read')) + ' ' + readByOthers + '</button></div>')
            : '';
        return '' +
            '<div class="chat-message-row ' + (mine ? 'mine' : 'other') + '" data-message-id="' + Number(message.id || 0) + '">' +
            (mine ? '' : '<div class="chat-sender-avatar">' + escapeHtml(initialText(message.sender_name || '')) + '</div>') +
            '<div class="chat-bubble-wrap">' +
            '<div class="chat-sender">' + escapeHtml(message.sender_name || '') + '</div>' +
            '<div class="chat-bubble">' +
            textHtml +
            buildAttachmentHtml(message) +
            '</div>' +
            '<div class="chat-time">' + escapeHtml(formatDateTime(message.created_at)) + '</div>' +
            readHtml +
            '</div>' +
            '</div>';
    }

    function appendMessages(messages) {
        if (!Array.isArray(messages) || messages.length === 0 || !messageWrap) return;
        var atBottom = !messageArea || (messageArea.scrollHeight - messageArea.scrollTop - messageArea.clientHeight < 140);

        var html = '';
        messages.forEach(function (message) {
            html += buildMessageHtml(message);
            state.lastMessageId = Math.max(state.lastMessageId, Number(message.id || 0));
        });
        messageWrap.insertAdjacentHTML('beforeend', html);
        scrollToBottom(atBottom);
    }

    function roomLabel(room) {
        var text = room.last_message_text || '';
        if (!text && room.last_sender_name) {
            text = room.last_sender_name;
        }
        if (!text) {
            text = 'メッセージはまだありません。';
        }
        return text;
    }

    function renderRoomList(rooms) {
        if (!roomList || !Array.isArray(rooms)) return;

        if (rooms.length === 0) {
            roomList.innerHTML = '<div class="p-3 text-muted small">チャットルームがありません。</div>';
            return;
        }

        var html = '';
        rooms.forEach(function (room) {
            var roomId = Number(room.id || 0);
            var isActive = roomId === state.roomId;
            var unread = Number(room.unread_count || 0);
            var roomName = room.display_name || 'チャット';
            html += '' +
                '<a class="chat-room-item ' + (isActive ? 'active' : '') + '" href="' + state.basePath + '/chat?room_id=' + roomId + '" data-room-id="' + roomId + '">' +
                '<div class="chat-room-row">' +
                '<div class="chat-room-avatar">' + escapeHtml(initialText(roomName)) + '</div>' +
                '<div class="chat-room-main">' +
                '<div class="chat-room-title-row">' +
                '<span class="chat-room-title-text">' + escapeHtml(roomName) + '</span>' +
                (unread > 0 ? ('<span class="chat-badge">' + unread + '</span>') : '') +
                '</div>' +
                '<div class="chat-room-meta">' + escapeHtml(roomLabel(room)) + '</div>' +
                '</div>' +
                '</div>' +
                '</a>';
        });
        roomList.innerHTML = html;
    }

    function notifyNewMessage(message) {
        if (!message || Number(message.user_id || 0) === state.userId) return;
        var key = String(message.id || '');
        if (!key || state.notifiedMessageIds[key]) return;
        state.notifiedMessageIds[key] = true;

        var body = (message.message_text || '').trim();
        if (!body && message.attachment_name) {
            body = 'ファイル: ' + message.attachment_name;
        }
        if (!body) body = '新しいメッセージがあります。';

        if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(function (reg) {
                reg.showNotification('チャット新着', {
                    body: (message.sender_name ? message.sender_name + ': ' : '') + body,
                    icon: state.basePath + '/public/icons/pwa-192.png',
                    badge: state.basePath + '/public/icons/pwa-192.png',
                    tag: 'chat-room-' + state.roomId,
                    data: { url: state.basePath + '/chat?room_id=' + state.roomId }
                });
            }).catch(function () {});
        } else {
            try {
                var n = new Notification('チャット新着', {
                    body: (message.sender_name ? message.sender_name + ': ' : '') + body
                });
                setTimeout(function () { n.close(); }, 5000);
            } catch (error) {}
        }
    }

    function requestNotificationPermission() {
        if (typeof Notification === 'undefined') return;
        if (Notification.permission === 'default') {
            Notification.requestPermission().catch(function () {});
        }
    }

    function fetchMessages() {
        if (!shouldActivateRoom()) return;
        var url = state.basePath + '/api/chat/rooms/' + state.roomId + '/messages?since_id=' + state.lastMessageId;
        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success || !json.data) return;
            var messages = json.data.messages || [];
            if (messages.length > 0) {
                appendMessages(messages);
                if (document.hidden) {
                    messages.forEach(notifyNewMessage);
                }
            }
            updateChatBadge(json.data.unread_count || 0);
        })
        .catch(function () {});
    }

    function fetchRooms() {
        fetch(state.basePath + '/api/chat/rooms', {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success || !json.data) return;
            renderRoomList(json.data.rooms || []);
            updateChatBadge(json.data.unread_count || 0);
        })
        .catch(function () {});
    }

    function markRead() {
        if (!shouldActivateRoom() || !state.lastMessageId) return;
        fetch(state.basePath + '/api/chat/rooms/' + state.roomId + '/read', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                last_message_id: state.lastMessageId
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json && json.success && json.data) {
                updateChatBadge(json.data.unread_count || 0);
            }
        })
        .catch(function () {});
    }

    function ensureReadModal() {
        if (!readModalEl || typeof bootstrap === 'undefined' || !window.bootstrap || !window.bootstrap.Modal) {
            return null;
        }
        if (!readModal) {
            readModal = new window.bootstrap.Modal(readModalEl);
        }
        return readModal;
    }

    function openReadDetailModal(messageId) {
        if (!state.roomId || !messageId || !readModalBody) return;
        var modal = ensureReadModal();
        if (!modal) return;

        if (readModalTitle) {
            readModalTitle.textContent = t('readersTitle', 'Read users');
        }
        readModalBody.innerHTML = '<div class="text-muted small">Loading...</div>';
        modal.show();

        var url = state.basePath + '/api/chat/rooms/' + state.roomId + '/messages/' + Number(messageId) + '/readers?exclude_user_id=' + state.userId;
        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success || !json.data) {
                readModalBody.innerHTML = '<div class="text-danger small">' + escapeHtml(t('readersLoadError', 'Failed to load read users.')) + '</div>';
                return;
            }

            var readers = Array.isArray(json.data.readers) ? json.data.readers : [];
            if (readers.length === 0) {
                readModalBody.innerHTML = '<div class="text-muted small">' + escapeHtml(t('readersEmpty', 'No one has read yet.')) + '</div>';
                return;
            }

            var html = '<div class="list-group list-group-flush">';
            readers.forEach(function (reader) {
                var name = reader.display_name || reader.username || ('User #' + Number(reader.id || 0));
                var readAt = formatDateTime(reader.last_read_at || '');
                html += '' +
                    '<div class="list-group-item px-0">' +
                    '<div class="d-flex justify-content-between align-items-center gap-2">' +
                    '<span>' + escapeHtml(name) + '</span>' +
                    '<span class="text-muted small">' + escapeHtml(readAt) + '</span>' +
                    '</div>' +
                    '</div>';
            });
            html += '</div>';
            readModalBody.innerHTML = html;
        })
        .catch(function () {
            readModalBody.innerHTML = '<div class="text-danger small">' + escapeHtml(t('readersLoadError', 'Failed to load read users.')) + '</div>';
        });
    }

    function bindReadDetail() {
        if (!messageWrap) return;
        messageWrap.addEventListener('click', function (event) {
            var button = event.target.closest('.chat-read-btn');
            if (!button) return;
            event.preventDefault();
            var messageId = Number(button.getAttribute('data-message-id') || 0);
            if (!messageId) return;
            openReadDetailModal(messageId);
        });
    }

    function bindComposerUi() {
        if (attachmentInput && attachmentNameNode) {
            attachmentInput.addEventListener('change', function () {
                var fileName = '';
                if (attachmentInput.files && attachmentInput.files.length > 0) {
                    fileName = attachmentInput.files[0].name || '';
                }
                attachmentNameNode.textContent = fileName;
            });
        }

        if (messageInput) {
            var resizeInput = function () {
                messageInput.style.height = 'auto';
                var nextHeight = Math.min(Math.max(messageInput.scrollHeight, 38), 110);
                messageInput.style.height = nextHeight + 'px';
            };
            messageInput.addEventListener('input', resizeInput);
            resizeInput();
        }
    }

    function bindMobileNavigation() {
        applyMobileLayoutMode();

        if (chatMobileBackBtn) {
            chatMobileBackBtn.addEventListener('click', function () {
                window.location.href = state.basePath + '/chat';
            });
        }

        window.addEventListener('resize', function () {
            var before = shouldActivateRoom();
            applyMobileLayoutMode();
            var after = shouldActivateRoom();
            if (before !== after) {
                startPolling();
            }
        });
    }

    function bindMessageForm() {
        if (!messageForm) return;
        messageForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!state.roomId) return;

            var formData = new FormData(messageForm);
            if (!formData.get('message_text') && !(attachmentInput && attachmentInput.files && attachmentInput.files.length)) {
                return;
            }

            fetch(state.basePath + '/chat/rooms/' + state.roomId + '/message', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json || !json.success) {
                    alert((json && json.error) ? json.error : '送信に失敗しました。');
                    return;
                }
                var message = json.data && json.data.message ? json.data.message : null;
                if (message) {
                    appendMessages([message]);
                    state.notifiedMessageIds[String(message.id || '')] = true;
                }
                if (messageInput) messageInput.value = '';
                if (attachmentInput) attachmentInput.value = '';
                if (attachmentNameNode) attachmentNameNode.textContent = '';
                if (messageInput) messageInput.style.height = '38px';
                updateChatBadge((json.data && json.data.unread_count) ? json.data.unread_count : 0);
                fetchRooms();
                markRead();
                requestNotificationPermission();
            })
            .catch(function () {
                alert('送信に失敗しました。');
            });
        });
    }

    function bindVisibility() {
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                fetchMessages();
                markRead();
            }
        });
    }

    function startPolling() {
        if (state.pollTimer) clearInterval(state.pollTimer);
        if (state.roomsTimer) clearInterval(state.roomsTimer);

        if (shouldActivateRoom()) {
            state.pollTimer = setInterval(fetchMessages, 2500);
        }
        state.roomsTimer = setInterval(fetchRooms, 12000);
    }

    bindMessageForm();
    bindReadDetail();
    bindComposerUi();
    bindMobileNavigation();
    bindVisibility();
    startPolling();
    scrollToBottom(true);
    if (shouldActivateRoom()) {
        markRead();
    }
    fetchRooms();
    setTimeout(requestNotificationPermission, 1200);
})();
