<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">日報テンプレート</h3>
                    <div>
                        <a href="<?= BASE_PATH ?>/daily-report/template/edit/<?= $template['id'] ?>" class="btn btn-sm btn-primary">編集</a>
                        <a href="<?= BASE_PATH ?>/daily-report/template/edit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>新規テンプレート
                        </a>
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-secondary ms-2">
                            <i class="fas fa-arrow-left me-2"></i>戻る
                        </a>
                    </div>
                </div>

                <!-- テンプレート一覧 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">テンプレート一覧</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>タイトル</th>
                                        <th>公開状態</th>
                                        <th>作成日時</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($templates)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">テンプレートがありません</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($templates as $template): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($template['title']) ?></td>
                                                <td>
                                                    <?php if ($template['is_public']): ?>
                                                        <span class="badge bg-success">公開</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">個人用</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('Y/m/d H:i', strtotime($template['created_at'])) ?></td>
                                                <td>
                                                    <a href="<?= BASE_PATH ?>/daily-report/template/edit/<?= $template['id'] ?>" class="btn btn-sm btn-primary">編集</a>
                                                    <a href="<?= BASE_PATH ?>/daily-report/create?template_id=<?= $template['id'] ?>" class="btn btn-sm btn-secondary">使用</a>
                                                    <?php if ($template['user_id'] == $this->auth->id() || $this->auth->isAdmin()): ?>
                                                        <button type="button" class="btn btn-sm btn-danger delete-template" data-id="<?= $template['id'] ?>">削除</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- テンプレートの説明 -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">テンプレートについて</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">個人用テンプレート</h5>
                                        <p class="card-text">
                                            個人用テンプレートはあなただけが使用できるテンプレートです。
                                            日々の業務報告や特定のプロジェクト報告など、自分専用のテンプレートを作成できます。
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">公開テンプレート</h5>
                                        <p class="card-text">
                                            公開テンプレートは組織内の全メンバーが使用できるテンプレートです。
                                            部署の標準フォーマットや特定の業務フローに沿った報告書など、共有したいテンプレートを作成できます。
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            効果的なテンプレートを作成するには、項目や見出しを明確にし、必要な情報が漏れなく報告できるように構成しましょう。
                            例えば、「本日の業務内容」「達成したこと」「課題・問題点」「明日の予定」などのセクションを設けると、報告がしやすくなります。
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // テンプレート削除ボタンのイベント処理
        $('.delete-template').on('click', function() {
            if (confirm('本当にこのテンプレートを削除しますか？')) {
                const templateId = $(this).data('id');

                // 削除API呼び出し
                $.ajax({
                    url: `${BASE_PATH}/api/daily-report/template/${templateId}`,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert(response.error || 'エラーが発生しました');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('ネットワークエラーが発生しました');
                    }
                });
            }
        });
    });
</script>