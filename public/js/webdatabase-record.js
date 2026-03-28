/**
 * WEB Database Record - JavaScript
 */
const WebDatabaseRecord = {
    currentPage: 1,
    totalPages: 1,
    searchTerm: '',
    filters: {},
    sortField: null,
    sortOrder: 'asc',
    viewId: null,
    viewType: 'list',
    visibleFieldIds: [],
    analyticsChart: null,

    init: function() {
        const path = window.location.pathname;
        if (path.includes('/webdatabase/records/')) {
            this.initRecordList();
            this.initUserFieldOptions();
            this.initOrganizationFieldOptions();
        } else if (path.includes('/webdatabase/create-record/') || path.includes('/webdatabase/edit/')) {
            this.initRecordForm();
        } else if (path.includes('/webdatabase/view/')) {
            this.initRecordView();
        }
    },

    initRecordList: function() {
        this.setupRecordListEventListeners();
        this.visibleFieldIds = this.getSelectedVisibleFieldIds();

        const selectedViewId = ($('#view-selector').val() || '').trim();
        if (selectedViewId) {
            this.viewId = selectedViewId;
            $('#view-selector').trigger('change');
            return;
        }

        this.viewType = ($('#view-type').val() || 'list').trim();
        this.toggleViewMode();
        if (this.viewType === 'list') {
            this.loadRecords();
        } else {
            this.runAnalytics();
        }
    },

    setupRecordListEventListeners: function() {
        $('#search-records').on('input', () => {
            this.searchTerm = $('#search-records').val();
            this.currentPage = 1;
            if (this.viewType === 'list') {
                this.loadRecords();
            }
        });

        $('#apply-filter-btn').on('click', () => {
            this.filters = {};
            $('.filter-field').each((_, el) => {
                const input = $(el);
                const value = input.val();
                if (!value) {
                    return;
                }
                const fieldId = (input.attr('id') || '').replace('filter-', '');
                if (!fieldId) {
                    return;
                }
                this.filters[fieldId] = value;
            });
            this.currentPage = 1;
            if (this.viewType === 'list') {
                this.loadRecords();
            } else {
                this.runAnalytics();
            }
        });

        $('#reset-filter-btn').on('click', () => {
            $('#filter-form')[0].reset();
            this.filters = {};
            this.currentPage = 1;
            if (this.viewType === 'list') {
                this.loadRecords();
            } else {
                this.runAnalytics();
            }
        });

        $('#view-selector').on('change', () => {
            const selected = $('#view-selector').find('option:selected');
            const id = selected.val() || '';
            this.viewId = id || null;
            if (!id) {
                this.viewType = ($('#view-type').val() || 'list').trim();
                this.toggleViewMode();
                if (this.viewType === 'list') {
                    this.loadRecords();
                } else {
                    this.runAnalytics();
                }
                return;
            }

            let settings = {};
            try {
                settings = JSON.parse(selected.attr('data-settings') || '{}');
            } catch (e) {
                settings = {};
            }
            this.applyViewSettings(settings);

            const selectedText = selected.text() || '';
            $('#view-name').val(selectedText.replace(/\s*\[.*\]\s*$/, '').trim());
            $('#view-scope').val(selected.attr('data-scope') || 'private');
            $('#view-organization').val((selected.attr('data-organization-id') || '').toString());
        });

        $('#view-type').on('change', () => {
            this.viewType = ($('#view-type').val() || 'list').trim();
            this.toggleViewMode();
            if (this.viewType === 'list') {
                this.loadRecords();
            } else {
                this.runAnalytics();
            }
        });

        $('#run-analytics-btn').on('click', () => {
            this.runAnalytics();
        });

        $('.visible-field-check').on('change', () => {
            this.visibleFieldIds = this.getSelectedVisibleFieldIds();
            this.syncTableHeaderFromSelection();
            if (this.viewType === 'list') {
                this.loadRecords();
            }
        });

        $('#save-view-btn').on('click', () => {
            this.saveCurrentView();
        });

        $('#delete-view-btn').on('click', () => {
            this.deleteSelectedView();
        });

        $(document).off('click', '.btn-delete').on('click', '.btn-delete', (e) => {
            e.preventDefault();
            const btn = $(e.currentTarget);
            if (!confirm(btn.data('confirm') || '削除しますか？')) {
                return;
            }
            $.ajax({
                url: btn.data('url'),
                type: 'DELETE',
                success: (response) => {
                    if (response.success) {
                        App.showNotification(response.message || '削除しました', 'success');
                        if (this.viewType === 'list') {
                            this.loadRecords();
                        } else {
                            this.runAnalytics();
                        }
                    } else {
                        App.showNotification(response.error || '削除に失敗しました', 'error');
                    }
                },
                error: () => {
                    App.showNotification('通信エラーが発生しました', 'error');
                }
            });
        });

        $(document).on('click', '.sortable', (e) => {
            const field = $(e.currentTarget).data('field');
            if (!field) {
                return;
            }
            if (this.sortField === field) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortOrder = 'asc';
            }
            this.loadRecords();
        });
    },

    getDatabaseIdFromPath: function() {
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        return pathParts[pathParts.length - 1];
    },

    getSelectedVisibleFieldIds: function() {
        const ids = [];
        $('.visible-field-check:checked').each((_, el) => {
            const id = parseInt($(el).val(), 10);
            if (id) {
                ids.push(id);
            }
        });
        return ids;
    },

    syncTableHeaderFromSelection: function() {
        const head = $('#record-table-head');
        if (!head.length) {
            return;
        }
        head.find('th[data-field-id]').remove();

        const creatorTh = head.find('th').eq(head.find('th').length - 3);
        let insertBefore = creatorTh;
        this.visibleFieldIds.forEach((fieldId) => {
            const label = $(`label[for="visible-field-${fieldId}"]`).text() || `Field ${fieldId}`;
            const th = $(`<th class="sortable" data-field="${fieldId}" data-field-id="${fieldId}"></th>`).text(label.trim());
            th.insertBefore(insertBefore);
        });
    },

    buildListParams: function() {
        const params = {
            page: this.currentPage,
            limit: 20
        };
        if (this.searchTerm) {
            params.search = this.searchTerm;
        }
        if (Object.keys(this.filters).length > 0) {
            params.filter_json = JSON.stringify(this.filters);
        }
        if (this.sortField) {
            params.sort = this.sortField;
            params.order = this.sortOrder;
        }
        if (this.viewId) {
            params.view_id = this.viewId;
        }
        return params;
    },

    loadRecords: function() {
        const databaseId = this.getDatabaseIdFromPath();
        const params = this.buildListParams();

        $.ajax({
            url: `${BASE_PATH}/api/webdatabase/${databaseId}/records`,
            type: 'GET',
            data: params,
            beforeSend: () => {
                const colCount = $('#record-list').closest('table').find('th').length;
                $('#record-list').html(`<tr><td colspan="${colCount}" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`);
            },
            success: (response) => {
                if (!response.success) {
                    App.showNotification(response.error || 'データ取得に失敗しました', 'error');
                    return;
                }

                const visibleIds = Array.isArray(response.data.visible_field_ids) ? response.data.visible_field_ids.map((v) => parseInt(v, 10)).filter(Boolean) : [];
                if (visibleIds.length) {
                    this.visibleFieldIds = visibleIds;
                    $('.visible-field-check').prop('checked', false);
                    visibleIds.forEach((id) => $(`#visible-field-${id}`).prop('checked', true));
                    this.syncTableHeaderFromSelection();
                }

                this.renderRecordList(response.data.records, databaseId, this.visibleFieldIds);
                this.totalPages = response.data.pagination.total_pages;
                this.renderRecordPagination(response.data.pagination);
                this.updateSortIndicators();
            },
            error: () => {
                App.showNotification('データの取得に失敗しました', 'error');
            }
        });
    },

    renderRecordList: function(records, databaseId, visibleFieldIds) {
        const container = $('#record-list');
        container.empty();

        if (!records || records.length === 0) {
            const colSpan = $('#record-list').closest('table').find('th').length;
            container.html(`<tr><td colspan="${colSpan}" class="text-center">レコードがありません。新しいレコードを作成してください。</td></tr>`);
            return;
        }

        const rowTemplate = $('#record-row-template').html();
        records.forEach((record) => {
            let row = rowTemplate
                .replace(/\{\{id\}\}/g, record.id)
                .replace(/\{\{database_id\}\}/g, databaseId)
                .replace(/\{\{title\}\}/g, this.escapeHtml(record.title || `ID: ${record.id}`))
                .replace(/\{\{creator_name\}\}/g, this.escapeHtml(record.creator_name || ''))
                .replace(/\{\{created_at\}\}/g, this.escapeHtml(this.formatDateTime(record.created_at)));

            let fieldValuesHtml = '';
            visibleFieldIds.forEach((fieldId) => {
                const value = record.field_values && Object.prototype.hasOwnProperty.call(record.field_values, fieldId)
                    ? record.field_values[fieldId]
                    : '';
                fieldValuesHtml += `<td>${this.escapeHtml(value || '')}</td>`;
            });

            row = row.replace(/\{\{field_values\}\}/g, fieldValuesHtml);
            container.append(row);
        });
    },

    renderRecordPagination: function(pagination) {
        $('#total-records').text(pagination.total);
        const start = pagination.total === 0 ? 0 : ((pagination.current_page - 1) * pagination.limit + 1);
        const end = Math.min(pagination.current_page * pagination.limit, pagination.total);
        $('#showing-records').text(`${start}-${end}`);

        const paginationContainer = $('#pagination');
        paginationContainer.empty();

        paginationContainer.append(`<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${pagination.current_page - 1}" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>`);

        const maxVisiblePages = 5;
        const startPage = Math.max(1, pagination.current_page - Math.floor(maxVisiblePages / 2));
        const endPage = Math.min(pagination.total_pages, startPage + maxVisiblePages - 1);
        for (let i = startPage; i <= endPage; i++) {
            paginationContainer.append(`<li class="page-item ${i === pagination.current_page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
        }

        paginationContainer.append(`<li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${pagination.current_page + 1}" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>`);

        $('.page-link').off('click').on('click', (e) => {
            e.preventDefault();
            const page = parseInt($(e.currentTarget).data('page'), 10);
            if (!page || page < 1 || page > this.totalPages) {
                return;
            }
            this.currentPage = page;
            this.loadRecords();
        });
    },

    toggleViewMode: function() {
        const analytics = this.viewType === 'aggregate' || this.viewType === 'chart';
        $('#analytics-config-card').toggleClass('d-none', !analytics);
        $('#record-table-wrap').toggleClass('d-none', analytics);
        $('#analytics-panel').toggleClass('d-none', !analytics);
        $('#record-pagination-wrap').toggleClass('d-none', analytics);
        $('#analytics-chart-wrap').toggleClass('d-none', this.viewType !== 'chart');
    },

    getAnalyticsParams: function() {
        const groupFieldId = parseInt($('#aggregate-group-field').val() || '0', 10);
        if (!groupFieldId) {
            return null;
        }
        const params = {
            group_field_id: groupFieldId,
            metric: ($('#aggregate-metric').val() || 'count')
        };
        const metricFieldId = parseInt($('#aggregate-metric-field').val() || '0', 10);
        if (metricFieldId > 0) {
            params.metric_field_id = metricFieldId;
        }
        params.date_grain = ($('#aggregate-date-grain').val() || 'none');
        if (Object.keys(this.filters).length > 0) {
            params.filter_json = JSON.stringify(this.filters);
        }
        return params;
    },

    runAnalytics: function() {
        const databaseId = this.getDatabaseIdFromPath();
        const params = this.getAnalyticsParams();
        if (!params) {
            $('#analytics-list').html('<tr><td colspan="3" class="text-center text-muted">グループ項目を選択してください</td></tr>');
            this.destroyAnalyticsChart();
            return;
        }

        $.ajax({
            url: `${BASE_PATH}/api/webdatabase/${databaseId}/analytics`,
            type: 'GET',
            data: params,
            success: (response) => {
                if (!response.success) {
                    App.showNotification(response.error || '集計に失敗しました', 'error');
                    return;
                }
                this.renderAnalyticsRows(response.data.rows || []);
                if (this.viewType === 'chart') {
                    this.renderAnalyticsChart(response.data.labels || [], response.data.values || []);
                } else {
                    this.destroyAnalyticsChart();
                }
            },
            error: () => {
                App.showNotification('集計データの取得に失敗しました', 'error');
            }
        });
    },

    renderAnalyticsRows: function(rows) {
        const tbody = $('#analytics-list');
        tbody.empty();
        if (!rows.length) {
            tbody.html('<tr><td colspan="3" class="text-center text-muted">集計対象データがありません</td></tr>');
            return;
        }
        rows.forEach((row) => {
            tbody.append(`<tr><td>${this.escapeHtml(row.label || '')}</td><td>${this.escapeHtml(String(row.count || 0))}</td><td>${this.escapeHtml(String(row.value ?? 0))}</td></tr>`);
        });
    },

    renderAnalyticsChart: function(labels, values) {
        const canvas = document.getElementById('analytics-chart');
        if (!canvas || !window.Chart) {
            return;
        }
        this.destroyAnalyticsChart();

        const chartType = ($('#chart-type').val() || 'bar');
        this.analyticsChart = new Chart(canvas.getContext('2d'), {
            type: chartType,
            data: {
                labels,
                datasets: [{
                    label: '集計値',
                    data: values,
                    backgroundColor: ['#2563eb', '#0ea5e9', '#16a34a', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'],
                    borderColor: '#1d4ed8',
                    borderWidth: 1,
                    fill: chartType === 'line' ? false : true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: chartType !== 'bar' }
                },
                scales: chartType === 'pie' ? {} : {
                    y: { beginAtZero: true }
                }
            }
        });
    },

    destroyAnalyticsChart: function() {
        if (this.analyticsChart) {
            this.analyticsChart.destroy();
            this.analyticsChart = null;
        }
    },

    applyViewSettings: function(settings) {
        if (!settings || typeof settings !== 'object') {
            return;
        }

        this.searchTerm = settings.search || '';
        $('#search-records').val(this.searchTerm);

        this.filters = {};
        $('.filter-field').val('');
        const filters = settings.filters || {};
        Object.keys(filters).forEach((fieldId) => {
            const value = filters[fieldId];
            this.filters[fieldId] = value;
            $(`#filter-${fieldId}`).val(value);
        });

        this.sortField = settings.sort || null;
        this.sortOrder = (settings.order || 'asc') === 'desc' ? 'desc' : 'asc';

        this.visibleFieldIds = Array.isArray(settings.visible_fields) ? settings.visible_fields.map((v) => parseInt(v, 10)).filter(Boolean) : this.getSelectedVisibleFieldIds();
        $('.visible-field-check').prop('checked', false);
        this.visibleFieldIds.forEach((id) => $(`#visible-field-${id}`).prop('checked', true));
        this.syncTableHeaderFromSelection();

        const viewType = settings.view_type || 'list';
        $('#view-type').val(viewType);
        this.viewType = viewType;

        const aggregate = settings.aggregate || {};
        $('#aggregate-group-field').val(aggregate.group_field_id || '');
        $('#aggregate-metric').val(aggregate.metric || 'count');
        $('#aggregate-metric-field').val(aggregate.metric_field_id || '');
        $('#aggregate-date-grain').val(aggregate.date_grain || 'none');
        $('#chart-type').val(aggregate.chart_type || 'bar');

        this.toggleViewMode();
        this.currentPage = 1;
        if (this.viewType === 'list') {
            this.loadRecords();
        } else {
            this.runAnalytics();
        }
    },

    collectCurrentSettings: function() {
        const filters = {};
        $('.filter-field').each((_, el) => {
            const input = $(el);
            const value = input.val();
            if (!value) {
                return;
            }
            const fieldId = (input.attr('id') || '').replace('filter-', '');
            if (!fieldId) {
                return;
            }
            filters[fieldId] = value;
        });

        return {
            search: this.searchTerm || '',
            filters,
            sort: this.sortField || null,
            order: this.sortOrder || 'asc',
            view_type: this.viewType || 'list',
            visible_fields: this.getSelectedVisibleFieldIds(),
            aggregate: {
                group_field_id: parseInt($('#aggregate-group-field').val() || '0', 10) || null,
                metric: $('#aggregate-metric').val() || 'count',
                metric_field_id: parseInt($('#aggregate-metric-field').val() || '0', 10) || null,
                date_grain: $('#aggregate-date-grain').val() || 'none',
                chart_type: $('#chart-type').val() || 'bar'
            }
        };
    },

    saveCurrentView: function() {
        const databaseId = this.getDatabaseIdFromPath();
        const name = ($('#view-name').val() || '').trim();
        if (!name) {
            App.showNotification('保存名を入力してください', 'error');
            return;
        }

        const selectedId = $('#view-selector').val() || null;
        const payload = {
            id: selectedId ? parseInt(selectedId, 10) : null,
            name,
            type: this.viewType === 'list' ? 'list' : 'custom',
            scope_type: ($('#view-scope').val() || 'private').trim(),
            organization_id: ($('#view-organization').val() || '').trim() || null,
            settings: this.collectCurrentSettings()
        };

        $.ajax({
            url: `${BASE_PATH}/api/webdatabase/${databaseId}/views`,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: (response) => {
                if (response.success) {
                    App.showNotification(response.message || 'ビューを保存しました', 'success');
                    window.location.reload();
                } else {
                    App.showNotification(response.error || 'ビュー保存に失敗しました', 'error');
                }
            },
            error: () => {
                App.showNotification('通信エラーが発生しました', 'error');
            }
        });
    },

    deleteSelectedView: function() {
        const selectedId = $('#view-selector').val() || '';
        if (!selectedId) {
            App.showNotification('削除対象のビューを選択してください', 'error');
            return;
        }
        if (!confirm('このビューを削除しますか？')) {
            return;
        }

        $.ajax({
            url: `${BASE_PATH}/api/webdatabase/views/${selectedId}`,
            type: 'DELETE',
            success: (response) => {
                if (response.success) {
                    App.showNotification(response.message || 'ビューを削除しました', 'success');
                    window.location.reload();
                } else {
                    App.showNotification(response.error || 'ビュー削除に失敗しました', 'error');
                }
            },
            error: () => {
                App.showNotification('通信エラーが発生しました', 'error');
            }
        });
    },

    updateSortIndicators: function() {
        $('.sortable').removeClass('sorting-asc sorting-desc');
        if (!this.sortField) {
            return;
        }
        const th = $(`.sortable[data-field="${this.sortField}"]`);
        th.addClass(this.sortOrder === 'asc' ? 'sorting-asc' : 'sorting-desc');
    },

    initRecordForm: function() {
        this.initUserFieldOptions();
        this.initOrganizationFieldOptions();
        this.initRelationFields();
        this.initChildTableFields();
        this.bindCalcFields();

        $('#record-form').on('submit', (e) => {
            e.preventDefault();
            this.serializeChildTables();

            const form = $('#record-form');
            const formData = new FormData(form[0]);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: () => {
                    $('button[type="submit"]').prop('disabled', true);
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').text('');
                },
                success: (response) => {
                    if (response.success) {
                        App.showNotification(response.message || '保存しました', 'success');
                        if (response.redirect) {
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 400);
                        }
                        return;
                    }

                    App.showNotification(response.error || 'エラーが発生しました', 'error');
                    if (response.validation) {
                        Object.keys(response.validation).forEach((fieldKey) => {
                            let input;
                            if (fieldKey.startsWith('fields.')) {
                                const fieldId = fieldKey.split('.')[1];
                                input = $(`[name="fields[${fieldId}]"]`);
                            } else {
                                input = $(`[name="${fieldKey}"]`);
                            }
                            if (input.length) {
                                input.addClass('is-invalid');
                                input.next('.invalid-feedback').text(response.validation[fieldKey]);
                            }
                        });
                    }
                },
                error: () => {
                    App.showNotification('エラーが発生しました', 'error');
                },
                complete: () => {
                    $('button[type="submit"]').prop('disabled', false);
                }
            });
        });
    },

    initRecordView: function() {
        this.initUserFieldDisplay();
        this.initOrganizationFieldDisplay();
    },

    initUserFieldOptions: function() {
        $.ajax({
            url: BASE_PATH + '/api/active-users',
            type: 'GET',
            success: (response) => {
                if (!response.success) {
                    return;
                }
                const users = response.data || [];
                $('.user-select').each((_, el) => {
                    const select = $(el);
                    const selectedValue = select.data('selected');
                    users.forEach((user) => {
                        const option = $('<option></option>').val(user.id).text(user.display_name);
                        if (String(selectedValue) === String(user.id)) {
                            option.prop('selected', true);
                        }
                        select.append(option);
                    });
                });
            }
        });
    },

    initOrganizationFieldOptions: function() {
        $.ajax({
            url: BASE_PATH + '/api/organizations',
            type: 'GET',
            success: (response) => {
                if (!response.success) {
                    return;
                }
                const organizations = response.data.organizations || [];
                $('.organization-select').each((_, el) => {
                    const select = $(el);
                    const selectedValue = select.data('selected');
                    organizations.forEach((org) => {
                        const option = $('<option></option>').val(org.id).text(org.name);
                        if (String(selectedValue) === String(org.id)) {
                            option.prop('selected', true);
                        }
                        select.append(option);
                    });
                });
            }
        });
    },

    initUserFieldDisplay: function() {
        $('.user-display').each((_, el) => {
            const span = $(el);
            const userId = span.data('user-id');
            if (!userId) {
                span.text('未選択');
                return;
            }
            $.ajax({
                url: `${BASE_PATH}/api/users/${userId}`,
                type: 'GET',
                success: (response) => {
                    span.text(response.success ? response.data.user.display_name : 'ユーザーが見つかりません');
                },
                error: () => {
                    span.text('ユーザー情報の取得に失敗しました');
                }
            });
        });
    },

    initOrganizationFieldDisplay: function() {
        $('.organization-display').each((_, el) => {
            const span = $(el);
            const orgId = span.data('org-id');
            if (!orgId) {
                span.text('未選択');
                return;
            }
            $.ajax({
                url: `${BASE_PATH}/api/organizations/${orgId}`,
                type: 'GET',
                success: (response) => {
                    span.text(response.success ? response.data.name : '組織が見つかりません');
                },
                error: () => {
                    span.text('組織情報の取得に失敗しました');
                }
            });
        });
    },

    initRelationFields: function() {
        $('.relation-field-container').each((_, el) => {
            const container = $(el);
            const relationDb = parseInt(container.data('relation-db') || '0', 10);
            const filterFieldId = parseInt(container.data('filter-field-id') || '0', 10);
            const select = container.find('.relation-select');
            if (!relationDb || !select.length) {
                return;
            }

            const loadOptions = () => {
                this.loadRelationOptions(container, select, relationDb, filterFieldId);
            };
            loadOptions();

            if (filterFieldId > 0) {
                $(`#field-${filterFieldId}`).on('change input', () => {
                    loadOptions();
                });
            }
        });
    },

    loadRelationOptions: function(container, select, relationDb, filterFieldId) {
        const params = {};
        if (filterFieldId > 0) {
            const sourceValue = $(`#field-${filterFieldId}`).val();
            if (sourceValue) {
                params.filter_field_id = filterFieldId;
                params.filter_value = sourceValue;
            }
        }

        let selectedIds = [];
        const selectedRaw = select.attr('data-selected') || '[]';
        try {
            selectedIds = JSON.parse(selectedRaw);
        } catch (e) {
            selectedIds = [];
        }
        if (!Array.isArray(selectedIds)) {
            selectedIds = [selectedIds];
        }
        const selectedSet = new Set((selectedIds || []).map((id) => String(id)));

        if ($.fn.select2 && select.data('select2')) {
            select.select2('destroy');
        }
        select.empty();
        select.append('<option value="">レコードを選択</option>');

        $.ajax({
            url: `${BASE_PATH}/api/webdatabase/relation-targets/${relationDb}`,
            type: 'GET',
            data: params,
            success: (response) => {
                if (!response.success || !Array.isArray(response.data)) {
                    return;
                }
                response.data.forEach((record) => {
                    const option = $('<option></option>')
                        .val(record.id)
                        .text(record.title || `ID:${record.id}`);
                    if (selectedSet.has(String(record.id))) {
                        option.prop('selected', true);
                    }
                    select.append(option);
                });

                if ($.fn.select2) {
                    select.select2({
                        placeholder: 'レコードを選択',
                        allowClear: true,
                        width: '100%'
                    });
                }
            }
        });
    },

    initChildTableFields: function() {
        $('.child-table-container').each((_, el) => {
            const container = $(el);
            const relationDb = parseInt(container.data('relation-db') || '0', 10);
            if (!relationDb) {
                return;
            }

            $.ajax({
                url: `${BASE_PATH}/api/webdatabase/${relationDb}/fields`,
                type: 'GET',
                success: (response) => {
                    if (!response.success || !Array.isArray(response.data)) {
                        return;
                    }
                    const childFields = response.data.filter((field) => !['lookup', 'calc', 'auto_number'].includes(field.type)).slice(0, 6);
                    container.data('child-fields', childFields);
                    this.renderChildTableHeader(container, childFields);
                    this.bindChildTableEvents(container, childFields);
                    this.loadChildTableInitialRows(container, childFields);
                    this.updateChildSummary(container);
                }
            });
        });
    },

    renderChildTableHeader: function(container, childFields) {
        const thead = container.find('thead');
        let html = '<tr>';
        html += '<th style="width:40px;"></th>';
        childFields.forEach((field) => {
            html += `<th>${this.escapeHtml(field.name)}</th>`;
        });
        html += '<th style="width:80px;">操作</th></tr>';
        thead.html(html);
    },

    bindChildTableEvents: function(container, childFields) {
        const tbody = container.find('tbody')[0];
        if (window.Sortable && tbody && !container.data('sortable-init')) {
            Sortable.create(tbody, {
                handle: '.child-row-handle',
                animation: 120,
                onEnd: () => {
                    this.serializeChildTables();
                    this.updateChildSummary(container);
                }
            });
            container.data('sortable-init', true);
        }

        container.find('.add-child-row-btn').on('click', () => {
            this.appendChildRow(container, childFields, { record_id: 0, record_data: {} });
            this.serializeChildTables();
            this.updateChildSummary(container);
        });

        container.on('click', '.remove-child-row-btn', (e) => {
            e.preventDefault();
            $(e.currentTarget).closest('tr').remove();
            this.serializeChildTables();
            this.updateChildSummary(container);
        });

        container.on('input change', 'input, select, textarea', () => {
            this.serializeChildTables();
            this.updateChildSummary(container);
        });
    },

    loadChildTableInitialRows: function(container, childFields) {
        let initialRows = [];
        try {
            initialRows = JSON.parse(container.attr('data-initial') || '[]');
        } catch (e) {
            initialRows = [];
        }
        if (!Array.isArray(initialRows) || initialRows.length === 0) {
            this.appendChildRow(container, childFields, { record_id: 0, record_data: {} });
            this.serializeChildTables();
            return;
        }

        initialRows.forEach((row) => {
            this.appendChildRow(container, childFields, row);
        });
        this.serializeChildTables();
    },

    appendChildRow: function(container, childFields, row) {
        const tbody = container.find('tbody');
        const tr = $('<tr></tr>');
        tr.attr('data-record-id', row.target_record_id || row.record_id || 0);
        tr.append('<td class="text-center text-muted child-row-handle" style="cursor: move;"><i class="fas fa-grip-vertical"></i></td>');

        childFields.forEach((field) => {
            const recordData = row.record_data || {};
            const value = recordData[field.id] !== undefined ? recordData[field.id] : '';
            const td = $('<td></td>');
            if (['text', 'number', 'date', 'datetime', 'url', 'email', 'phone', 'currency', 'percent'].includes(field.type)) {
                const type = (field.type === 'currency' || field.type === 'percent') ? 'number' : (field.type === 'datetime' ? 'datetime-local' : field.type);
                td.append(`<input type="${type}" class="form-control form-control-sm child-input" data-child-field-id="${field.id}" value="${this.escapeHtml(value)}">`);
            } else if (field.type === 'textarea') {
                td.append(`<textarea class="form-control form-control-sm child-input" data-child-field-id="${field.id}" rows="1">${this.escapeHtml(value)}</textarea>`);
            } else if (['select', 'radio'].includes(field.type)) {
                const select = $(`<select class="form-select form-select-sm child-input" data-child-field-id="${field.id}"></select>`);
                select.append('<option value="">選択</option>');
                let options = [];
                try {
                    options = JSON.parse(field.options || '[]');
                } catch (e) {
                    options = [];
                }
                options.forEach((opt) => {
                    const option = $('<option></option>').val(opt.value).text(opt.label);
                    if (String(value) === String(opt.value)) {
                        option.prop('selected', true);
                    }
                    select.append(option);
                });
                td.append(select);
            } else {
                td.append(`<input type="text" class="form-control form-control-sm child-input" data-child-field-id="${field.id}" value="${this.escapeHtml(value)}">`);
            }
            tr.append(td);
        });

        tr.append('<td><button type="button" class="btn btn-sm btn-outline-danger remove-child-row-btn"><i class="fas fa-times"></i></button></td>');
        tbody.append(tr);
    },

    serializeChildTables: function() {
        $('.child-table-container').each((_, el) => {
            const container = $(el);
            const hidden = container.find('.child-table-json');
            const rows = [];
            container.find('tbody tr').each((idx, rowEl) => {
                const row = $(rowEl);
                const rowData = {
                    record_id: parseInt(row.attr('data-record-id') || '0', 10) || 0,
                    row_order: idx + 1,
                    fields: {}
                };
                row.find('.child-input').each((__, inputEl) => {
                    const input = $(inputEl);
                    const fieldId = parseInt(input.data('child-field-id') || '0', 10);
                    if (!fieldId) {
                        return;
                    }
                    rowData.fields[fieldId] = input.val();
                });
                const hasValue = Object.values(rowData.fields).some((v) => v !== null && String(v).trim() !== '');
                if (hasValue || rowData.record_id > 0) {
                    rows.push(rowData);
                }
            });
            hidden.val(JSON.stringify(rows));
        });
    },

    updateChildSummary: function(container) {
        const sumFieldId = parseInt(container.data('summary-field-id') || '0', 10);
        let total = 0;
        if (sumFieldId > 0) {
            container.find(`.child-input[data-child-field-id="${sumFieldId}"]`).each((_, inputEl) => {
                const value = parseFloat($(inputEl).val() || '0');
                if (!Number.isNaN(value)) {
                    total += value;
                }
            });
        }
        container.find('.child-summary-value').text(total.toLocaleString());
    },

    bindCalcFields: function() {
        $('.calc-field').each((_, el) => {
            const calcInput = $(el);
            const formula = calcInput.data('formula');
            if (!formula) {
                return;
            }
            const updateCalc = () => {
                let expr = String(formula);
                expr = expr.replace(/\{(\d+)\}/g, (_, fieldId) => {
                    const value = parseFloat($(`#field-${fieldId}`).val() || '0');
                    return Number.isNaN(value) ? '0' : String(value);
                });
                try {
                    // eslint-disable-next-line no-new-func
                    const result = Function(`return (${expr});`)();
                    calcInput.val(Number.isFinite(result) ? result : '');
                } catch (e) {
                    calcInput.val('');
                }
            };
            $('input,select,textarea').on('input change', updateCalc);
            updateCalc();
        });
    },

    formatDateTime: function(datetime) {
        if (!datetime) {
            return '';
        }
        const date = new Date(datetime);
        if (Number.isNaN(date.getTime())) {
            return datetime;
        }
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}/${month}/${day} ${hours}:${minutes}`;
    },

    escapeHtml: function(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
};

$(document).ready(function() {
    WebDatabaseRecord.init();
});
