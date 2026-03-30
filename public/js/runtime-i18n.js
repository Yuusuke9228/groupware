(function () {
    'use strict';

    var locale = (window.APP_LOCALE || '').toLowerCase();
    if (locale !== 'en') {
        return;
    }

    var endpoint = window.RUNTIME_I18N_ENDPOINT || ((window.BASE_PATH || '') + '/api/i18n/translate');
    var cache = Object.create(null);
    var endpointCalls = 0;
    var endpointCallLimit = 260;
    var scanning = false;

    function hasJapanese(text) {
        return /[\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff]/.test(text || '');
    }

    function normalize(text) {
        return String(text || '').replace(/\s+/g, ' ').trim();
    }

    function translateViaEndpointSync(text) {
        if (!endpoint || endpointCalls >= endpointCallLimit) {
            return text;
        }

        endpointCalls += 1;
        try {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', endpoint, false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(JSON.stringify({ text: text }));
            if (xhr.status >= 200 && xhr.status < 300) {
                var json = JSON.parse(xhr.responseText || '{}');
                if (json && typeof json.message === 'string' && json.message !== '') {
                    return json.message;
                }
            }
        } catch (e) {
            return text;
        }
        return text;
    }

    function translateText(text) {
        if (typeof text !== 'string' || text === '' || !hasJapanese(text)) {
            return text;
        }

        if (cache[text]) {
            return cache[text];
        }

        var translated = translateViaEndpointSync(text);
        cache[text] = translated || text;
        return cache[text];
    }

    function translateAttributes(el) {
        ['placeholder', 'title', 'aria-label', 'alt', 'data-original-title', 'data-bs-original-title'].forEach(function (attr) {
            var value = el.getAttribute(attr);
            if (!value || !hasJapanese(value)) {
                return;
            }
            var translated = translateText(value);
            if (translated && translated !== value) {
                el.setAttribute(attr, translated);
            }
        });
    }

    function translateDom(root) {
        if (scanning || !root) {
            return;
        }
        scanning = true;

        try {
            var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
            var node;
            var count = 0;
            while ((node = walker.nextNode())) {
                var parentTag = node.parentElement ? node.parentElement.tagName : '';
                if (parentTag === 'SCRIPT' || parentTag === 'STYLE' || parentTag === 'NOSCRIPT') {
                    continue;
                }
                var value = node.nodeValue;
                if (!value || !hasJapanese(value)) {
                    continue;
                }
                var translated = translateText(value);
                if (translated && translated !== value) {
                    node.nodeValue = translated;
                }
                count++;
                if (count >= 2200) {
                    break;
                }
            }

            var elems = root.querySelectorAll ? root.querySelectorAll('[placeholder],[title],[aria-label],[alt],[data-original-title],[data-bs-original-title]') : [];
            for (var i = 0; i < elems.length && i < 2200; i++) {
                translateAttributes(elems[i]);
            }
        } finally {
            scanning = false;
        }
    }

    function patchDialogApis() {
        var nativeAlert = window.alert ? window.alert.bind(window) : null;
        var nativeConfirm = window.confirm ? window.confirm.bind(window) : null;
        var nativePrompt = window.prompt ? window.prompt.bind(window) : null;

        if (nativeAlert) {
            window.alert = function (message) {
                return nativeAlert(translateText(String(message || '')));
            };
        }

        if (nativeConfirm) {
            window.confirm = function (message) {
                return nativeConfirm(translateText(String(message || '')));
            };
        }

        if (nativePrompt) {
            window.prompt = function (message, defaultValue) {
                return nativePrompt(translateText(String(message || '')), defaultValue);
            };
        }
    }

    function patchToastr() {
        if (!window.toastr || window.toastr.__runtimeI18nPatched) {
            return;
        }

        ['success', 'error', 'warning', 'info'].forEach(function (type) {
            var original = window.toastr[type];
            if (typeof original !== 'function') {
                return;
            }
            window.toastr[type] = function (message, title, optionsOverride) {
                var translatedMessage = translateText(String(message || ''));
                var translatedTitle = title ? translateText(String(title)) : title;
                return original.call(window.toastr, translatedMessage, translatedTitle, optionsOverride);
            };
        });

        window.toastr.__runtimeI18nPatched = true;
    }

    function patchAppNotification() {
        if (!window.App || window.App.__runtimeI18nPatched) {
            return false;
        }

        if (typeof window.App.showNotification === 'function') {
            var original = window.App.showNotification.bind(window.App);
            window.App.showNotification = function (message, type) {
                return original(translateText(String(message || '')), type);
            };
        }

        window.App.__runtimeI18nPatched = true;
        return true;
    }

    function patchDataTablesDefault() {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.dataTable || !jQuery.fn.dataTable.defaults) {
            return;
        }

        jQuery.extend(true, jQuery.fn.dataTable.defaults, {
            language: {
                emptyTable: 'No data available in table',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                lengthMenu: 'Show _MENU_ entries',
                loadingRecords: 'Loading...',
                processing: 'Processing...',
                search: 'Search:',
                zeroRecords: 'No matching records found',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            }
        });
    }

    function boot() {
        patchDialogApis();
        patchToastr();
        patchDataTablesDefault();
        patchAppNotification();
        translateDom(document.body || document.documentElement);

        var patchTimer = setInterval(function () {
            patchToastr();
            if (patchAppNotification()) {
                clearInterval(patchTimer);
            }
        }, 250);
        setTimeout(function () {
            clearInterval(patchTimer);
        }, 10000);

        var debounceTimer = null;
        var observer = new MutationObserver(function () {
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
            debounceTimer = setTimeout(function () {
                translateDom(document.body || document.documentElement);
            }, 120);
        });
        observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
