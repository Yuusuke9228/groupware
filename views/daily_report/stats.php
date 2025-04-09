<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">日報統計</h3>
                    <div>
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>戻る
                        </a>
                    </div>
                </div>

                <!-- 期間フィルター -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">期間設定</h5>
                    </div>
                    <div class="card-body">
                        <form id="statsFilterForm" method="GET" action="<?= BASE_PATH ?>/daily-report/stats">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="start_date" class="form-label">開始日</label>
                                    <input type="date" class="form-control stats-date-filter" id="start_date" name="start_date" value="<?= $startDate ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_date" class="form-label">終了日</label>
                                    <input type="date" class="form-control stats-date-filter" id="end_date" name="end_date" value="<?= $endDate ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i>適用
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 要約情報 -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">日報総数</h5>
                                <h1 class="display-4"><?= number_format($stats['total_reports']) ?></h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">平均文字数</h5>
                                <h1 class="display-4"><?= isset($stats['avg_length']) ? number_format($stats['avg_length']) : '-' ?></h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">いいね数</h5>
                                <h1 class="display-4"><?= number_format($stats['total_likes']) ?></h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">コメント数</h5>
                                <h1 class="display-4"><?= number_format($stats['total_comments']) ?></h1>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- グラフ - 月別日報数 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">月別日報作成数</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyReportsChart" height="300"></canvas>
                    </div>
                </div>

                <!-- タグ利用状況 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">タグ利用状況</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tags)): ?>
                            <p class="text-center">タグの利用実績がありません</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>タグ名</th>
                                            <th>使用回数</th>
                                            <th>割合</th>
                                            <th>グラフ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // タグの合計使用回数を計算
                                        $totalTagUsage = array_sum(array_column($tags, 'reports_count'));
                                        ?>
                                        <?php foreach ($tags as $tag): ?>
                                            <?php
                                            // 使用割合を計算
                                            $percentage = $totalTagUsage > 0 ? ($tag['reports_count'] / $totalTagUsage) * 100 : 0;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($tag['name']) ?></td>
                                                <td><?= number_format($tag['reports_count']) ?></td>
                                                <td><?= number_format($percentage, 1) ?>%</td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // グラフ描画用のデータをJavaScriptの変数として定義
    window.monthlyStats = <?= json_encode($stats['monthly_stats']) ?>;
</script>