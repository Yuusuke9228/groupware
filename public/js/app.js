/**
 * GroupWare - メインアプリケーションJS
 */

// アプリケーション名前空間
const App = {
    // 設定
    config: {
        apiEndpoint: BASE_PATH + '/api',
        dateFormat: 'YYYY-MM-DD',
        timeFormat: 'HH:mm',
        datetimeFormat: 'YYYY-MM-DD HH:mm'
    },

    // 現在のページ
    currentPage: null,
    currentPageInitRetries: 0,
    currentPageInitTimer: null,

    // 初期化
    init: function () {
        this.initAjaxDefaults();

        // イベントリスナーを設定
        this.setupEventListeners();

        // 現在のページを判断して初期化
        this.initCurrentPage();

        // モーダルの初期化
        this.initModals();

        // 通知系の初期化
        this.initNotifications();

        // Select2の初期化（複数選択セレクトボックスをリッチUIに変換）
        this.initSelect2();
    },

    initAjaxDefaults: function () {
        this.patchFetch();

        if (window.jQuery) {
            $.ajaxSetup({
                cache: false,
                headers: this.buildNoCacheHeaders({
                    'X-Requested-With': 'XMLHttpRequest'
                })
            });

            $.ajaxPrefilter(function (options) {
                const method = (options.type || options.method || 'GET').toUpperCase();
                if (method !== 'GET' && method !== 'HEAD') {
                    return;
                }

                options.url = App.appendCacheBuster(options.url);
                options.cache = false;
                options.headers = App.buildNoCacheHeaders(options.headers || {});
            });
        }
    },

    patchFetch: function () {
        if (!window.fetch || window.__appFetchPatched) {
            return;
        }

        const nativeFetch = window.fetch.bind(window);
        window.fetch = function (resource, options = {}) {
            const request = resource instanceof Request ? resource : null;
            const inputUrl = request ? request.url : String(resource);
            const method = ((options.method || (request && request.method) || 'GET') + '').toUpperCase();
            const sameOrigin = App.isSameOriginRequest(inputUrl);
            const nextOptions = Object.assign({}, options);

            if (sameOrigin) {
                nextOptions.credentials = nextOptions.credentials || 'same-origin';
                nextOptions.headers = App.buildNoCacheHeaders(nextOptions.headers || {});

                if (method === 'GET' || method === 'HEAD') {
                    nextOptions.cache = 'no-store';

                    if (!request) {
                        resource = App.appendCacheBuster(inputUrl);
                    }
                }
            }

            return nativeFetch(resource, nextOptions);
        };

        window.__appFetchPatched = true;
    },

    buildNoCacheHeaders: function (headers = {}) {
        const normalized = {};

        if (headers instanceof Headers) {
            headers.forEach((value, key) => {
                normalized[key] = value;
            });
        } else {
            Object.keys(headers || {}).forEach(key => {
                normalized[key] = headers[key];
            });
        }

        normalized['Cache-Control'] = normalized['Cache-Control'] || 'no-cache, no-store, must-revalidate';
        normalized['Pragma'] = normalized['Pragma'] || 'no-cache';
        normalized['Expires'] = normalized['Expires'] || '0';

        return normalized;
    },

    appendCacheBuster: function (url) {
        if (!url || /^https?:\/\/[^/]+/i.test(url) && !this.isSameOriginRequest(url)) {
            return url;
        }

        const urlObj = new URL(url, window.location.origin);
        urlObj.searchParams.set('_ts', Date.now().toString());

        return /^https?:\/\//i.test(url) ? urlObj.toString() : (urlObj.pathname + urlObj.search + urlObj.hash);
    },

    isSameOriginRequest: function (url) {
        if (!url) {
            return false;
        }

        if (url.startsWith('/')) {
            return true;
        }

        try {
            return new URL(url, window.location.origin).origin === window.location.origin;
        } catch (error) {
            return false;
        }
    },

    // Select2 複数選択セレクトボックスの初期化
    initSelect2: function () {
        if (typeof $.fn.select2 === 'undefined') return;
        $('.select2-multi').each(function () {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                closeOnSelect: false,
                allowClear: true,
                placeholder: $(this).data('placeholder') || '選択してください...',
                language: {
                    noResults: function () { return '該当なし'; },
                    searching: function () { return '検索中...'; },
                    removeAllItems: function () { return 'すべて削除'; }
                }
            });
        });
    },

    // イベントリスナーを設定
    setupEventListeners: function () {
        // 標準のリンククリックをトラップして処理
        $(document).on('click', 'a:not([data-bs-toggle]):not([target="_blank"])', function (e) {
            let href = $(this).attr('href');

            // # だけのリンク、JavaScript:など特殊リンク、外部リンクは除外
            if (!href || href === '#' || href.indexOf('javascript:') === 0 || href.indexOf('http') === 0) {
                return true;
            }

            // BASE_PATHが含まれていないパスにはBASE_PATHを追加
            if (href.indexOf(BASE_PATH) !== 0 && href.charAt(0) === '/') {
                href = BASE_PATH + href;
                $(this).attr('href', href);
            }

            e.preventDefault();
            window.location.href = href;
        });

        // モーダル内のフォーム送信
        $(document).on('submit', '.modal-form', function (e) {
            if (e.isDefaultPrevented()) {
                return true;
            }

            e.preventDefault();
            const form = $(this);
            const url = form.attr('action');
            const method = form.attr('method') || 'POST';
            const data = form.serialize();

            if (!url) {
                return true;
            }

            App.submitForm(url, method, data, form);
        });

        // 通常のフォーム送信
        $(document).on('submit', 'form:not(.modal-form):not(.no-ajax)', function (e) {
            if (e.isDefaultPrevented()) {
                return true;
            }

            const form = $(this);
            const url = form.attr('action');
            const method = (form.attr('method') || 'GET').toUpperCase();

            // action未指定やGETフォームは通常送信に任せる
            if (!url || method === 'GET') {
                return true;
            }

            e.preventDefault();
            const data = form.serialize();

            App.submitForm(url, method, data, form);
        });

        // 削除ボタンのクリック
        $(document).on('click', '.btn-delete', function (e) {
            e.preventDefault();
            let url = $(this).data('url');

            if (!url) {
                return;
            }

            // BASE_PATHがない場合は追加
            if (url.indexOf(BASE_PATH) !== 0 && url.charAt(0) === '/') {
                url = BASE_PATH + url;
            }

            const message = $(this).data('confirm') || '本当に削除しますか？';

            if (confirm(message)) {
                App.apiDelete(url)
                    .then(response => {
                        if (response.success) {
                            App.showNotification(response.message || '削除しました', 'success');

                            // リダイレクト指定があればリダイレクト
                            if (response.redirect) {
                                window.location.href = response.redirect;
                                return;
                            }

                            // データテーブルがある場合は再読み込み
                            if ($.fn.DataTable.isDataTable('.datatable')) {
                                $('.datatable').DataTable().ajax.reload();
                            } else {
                                // 現在のページをリロード
                                window.location.reload();
                            }
                        } else {
                            App.showNotification(response.error || 'エラーが発生しました', 'error');
                        }
                    })
                    .catch(error => {
                        App.showNotification('エラーが発生しました', 'error');
                        console.error(error);
                    });
            }
        });
    },

    initCurrentPageModule: function (pageName, moduleName) {
        this.currentPage = pageName;

        if (typeof window[moduleName] !== 'undefined' && typeof window[moduleName].init === 'function') {
            window[moduleName].init();
            this.currentPageInitRetries = 0;

            if (this.currentPageInitTimer) {
                clearTimeout(this.currentPageInitTimer);
                this.currentPageInitTimer = null;
            }

            return true;
        }

        return false;
    },

    scheduleCurrentPageRetry: function () {
        if (this.currentPageInitRetries >= 10) {
            return;
        }

        if (this.currentPageInitTimer) {
            clearTimeout(this.currentPageInitTimer);
        }

        this.currentPageInitRetries += 1;
        this.currentPageInitTimer = setTimeout(() => {
            this.initCurrentPage();
        }, 150);
    },

    // 現在のページを判断して初期化
    initCurrentPage: function () {
        // URLから現在のページを判断
        const path = window.location.pathname;

        if (path.includes('/organizations')) {
            if (!this.initCurrentPageModule('organizations', 'Organization')) {
                this.scheduleCurrentPageRetry();
            }
        } else if (path.includes('/users')) {
            if (!this.initCurrentPageModule('users', 'User')) {
                this.scheduleCurrentPageRetry();
            }
        } else if (path.includes('/schedule')) {
            this.currentPage = 'schedule';
            // スケジュール管理ページの初期化
            if (typeof Schedule !== 'undefined' && !Schedule._initialized) {
                Schedule._initialized = true;
                Schedule.init();
            }
        }
    },

    // モーダルの初期化
    initModals: function () {
        // Bootstrap モーダルのイベント設定
        $('.modal').on('shown.bs.modal', function () {
            $(this).find('[autofocus]').focus();
        });

        // モーダルが閉じられたときにフォームをリセット
        $('.modal').on('hidden.bs.modal', function () {
            $(this).find('form').get(0)?.reset();
            $(this).find('.is-invalid').removeClass('is-invalid');
            $(this).find('.invalid-feedback').text('');
        });
    },

    // 通知系の初期化
    initNotifications: function () {
        // トースト通知の設定
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: true,
            progressBar: true,
            positionClass: "toast-top-right",
            preventDuplicates: false,
            onclick: null,
            showDuration: "300",
            hideDuration: "1000",
            timeOut: "5000",
            extendedTimeOut: "1000",
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut"
        };
    },

    // 通知を表示
    showNotification: function (message, type = 'info') {
        switch (type) {
            case 'success':
                toastr.success(message);
                break;
            case 'error':
                toastr.error(message);
                break;
            case 'warning':
                toastr.warning(message);
                break;
            default:
                toastr.info(message);
        }
    },

    // フォーム送信
    submitForm: function (url, method, data, form) {
        if (!url) {
            return;
        }

        // BASE_PATHがない場合は追加
        if (url.indexOf(BASE_PATH) !== 0 && url.charAt(0) === '/') {
            url = BASE_PATH + url;
        }

        const hasFileInput = form.find('input[type="file"]').length > 0;
        const isMultipart = (form.attr('enctype') || '').toLowerCase() === 'multipart/form-data';
        const ajaxOptions = {
            url: url,
            type: method,
            cache: false,
            headers: this.buildNoCacheHeaders({
                'X-Requested-With': 'XMLHttpRequest'
            }),
            beforeSend: function () {
                // 送信ボタンを無効化
                form.find('[type="submit"]').prop('disabled', true);

                // バリデーションエラー表示をクリア
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
            },
            success: function (response) {
                if (response.success) {
                    // 成功の場合
                    App.showNotification(response.message || '保存しました', 'success');

                    // モーダルがある場合は閉じる
                    const modal = form.closest('.modal');
                    if (modal.length) {
                        modal.modal('hide');
                    }

                    // リダイレクト指定があればリダイレクト
                    if (response.redirect) {
                        window.location.href = response.redirect;
                        return;
                    }

                    // データテーブルがある場合は再読み込み
                    if ($.fn.DataTable.isDataTable('.datatable')) {
                        $('.datatable').DataTable().ajax.reload();
                    } else {
                        // 現在のページをリロード
                        window.location.reload();
                    }
                } else {
                    // エラーの場合
                    App.showNotification(response.error || 'エラーが発生しました', 'error');

                    // バリデーションエラーがある場合は表示
                    if (response.validation) {
                        for (const field in response.validation) {
                            const errorMsg = response.validation[field];
                            const input = form.find('[name="' + field + '"]');
                            input.addClass('is-invalid');
                            input.next('.invalid-feedback').text(errorMsg);
                        }
                    }
                }
            },
            error: function (xhr, status, error) {
                App.showNotification('エラーが発生しました', 'error');
                console.error(error);
            },
            complete: function () {
                // 送信ボタンを有効化
                form.find('[type="submit"]').prop('disabled', false);
            }
        };

        if (hasFileInput || isMultipart) {
            ajaxOptions.data = new FormData(form[0]);
            ajaxOptions.processData = false;
            ajaxOptions.contentType = false;
        } else {
            ajaxOptions.data = data;
        }

        $.ajax(ajaxOptions);
    },

    // API GET リクエスト
    apiGet: function (endpoint, params = {}) {
        const url = this.buildApiUrl(endpoint, params);

        return fetch(url, {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            });
    },

    // API POST リクエスト
    apiPost: function (endpoint, data = {}) {
        const url = this.buildApiUrl(endpoint);

        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            body: JSON.stringify(data)
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            });
    },

    // API PUT リクエスト
    apiPut: function (endpoint, data = {}) {
        const url = this.buildApiUrl(endpoint);

        return fetch(url, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            body: JSON.stringify(data)
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            });
    },

    // API DELETE リクエスト
    apiDelete: function (endpoint) {
        const url = this.buildApiUrl(endpoint);

        return fetch(url, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            });
    },

    // API URLを構築
    buildApiUrl: function (endpoint, params = {}) {
        // エンドポイントが既にフルURLの場合はそのまま使用
        if (endpoint.startsWith('http')) {
            let url = endpoint;

            // GETパラメータを追加
            if (Object.keys(params).length > 0) {
                const queryString = new URLSearchParams(params).toString();
                url += (url.includes('?') ? '&' : '?') + queryString;
            }

            return url;
        }

        // BASE_PATHから始まる場合は、そのまま使用
        if (endpoint.startsWith(BASE_PATH)) {
            // BASE_PATHの後に/apiがない場合は追加
            if (!endpoint.includes('/api/') && !endpoint.endsWith('/api')) {
                endpoint = endpoint.replace(BASE_PATH, BASE_PATH + '/api');
            }

            let url = endpoint;

            // GETパラメータを追加
            if (Object.keys(params).length > 0) {
                const queryString = new URLSearchParams(params).toString();
                url += (url.includes('?') ? '&' : '?') + queryString;
            }

            return url;
        }

        // / で始まる場合
        if (endpoint.startsWith('/')) {
            // /api で始まる場合は、BASE_PATHを追加
            if (endpoint.startsWith('/api/') || endpoint === '/api') {
                let url = BASE_PATH + endpoint;

                // GETパラメータを追加
                if (Object.keys(params).length > 0) {
                    const queryString = new URLSearchParams(params).toString();
                    url += (url.includes('?') ? '&' : '?') + queryString;
                }

                return url;
            }

            // それ以外は/apiを追加
            let url = BASE_PATH + '/api' + endpoint;

            // GETパラメータを追加
            if (Object.keys(params).length > 0) {
                const queryString = new URLSearchParams(params).toString();
                url += (url.includes('?') ? '&' : '?') + queryString;
            }

            return url;
        }

        // 相対パスの場合（/で始まらない）
        let url = BASE_PATH + '/api/' + endpoint;

        // GETパラメータを追加
        if (Object.keys(params).length > 0) {
            const queryString = new URLSearchParams(params).toString();
            url += (url.includes('?') ? '&' : '?') + queryString;
        }

        return url;
    },

    // 日付フォーマット（YYYY-MM-DD）
    formatDate: function (date) {
        if (!date) return '';

        if (typeof date === 'string') {
            date = new Date(date);
        }

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    },

    // 時間フォーマット（HH:MM）
    formatTime: function (date) {
        if (!date) return '';

        if (typeof date === 'string') {
            date = new Date(date);
        }

        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${hours}:${minutes}`;
    },

    // 日時フォーマット（YYYY-MM-DD HH:MM）
    formatDateTime: function (date) {
        if (!date) return '';

        return this.formatDate(date) + ' ' + this.formatTime(date);
    },

    // 日本語形式の日付フォーマット（YYYY年MM月DD日）
    formatDateJP: function (date) {
        if (!date) return '';

        if (typeof date === 'string') {
            date = new Date(date);
        }

        const year = date.getFullYear();
        const month = date.getMonth() + 1;
        const day = date.getDate();

        return `${year}年${month}月${day}日`;
    },

    // 曜日を取得（日本語）
    getDayOfWeekJP: function (date) {
        if (!date) return '';

        if (typeof date === 'string') {
            date = new Date(date);
        }

        const dayOfWeek = date.getDay();
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];

        return dayNames[dayOfWeek];
    },

    // 文字列を日付オブジェクトに変換
    parseDate: function (dateStr) {
        if (!dateStr) return null;

        // YYYY-MM-DD または YYYY/MM/DD 形式を想定
        const parts = dateStr.split(/[-\/]/);
        if (parts.length !== 3) return null;

        return new Date(parts[0], parts[1] - 1, parts[2]);
    },

    // 文字列を日時オブジェクトに変換
    parseDateTime: function (dateTimeStr) {
        if (!dateTimeStr) return null;

        // YYYY-MM-DD HH:MM:SS または YYYY/MM/DD HH:MM:SS 形式を想定
        const [dateStr, timeStr] = dateTimeStr.split(' ');
        if (!dateStr || !timeStr) return null;

        const dateParts = dateStr.split(/[-\/]/);
        if (dateParts.length !== 3) return null;

        const timeParts = timeStr.split(':');
        if (timeParts.length < 2) return null;

        return new Date(
            dateParts[0],
            dateParts[1] - 1,
            dateParts[2],
            timeParts[0],
            timeParts[1],
            timeParts[2] || 0
        );
    },

    // HTML特殊文字をエスケープ
    escapeHtml: function (text) {
        if (!text) return '';

        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    // 文字列を切り詰めて省略記号を追加
    truncateText: function (text, length = 50) {
        if (!text) return '';

        if (text.length <= length) return text;

        return text.substring(0, length) + '...';
    }
};

// DOMが読み込まれたら初期化
$(document).ready(function () {
    App.init();
});
