<?php
$firstDayTs = strtotime($monthStart);
$daysInMonth = (int)date('t', $firstDayTs);
$startWeekday = (int)date('w', $firstDayTs);
$prevMonth = date('Y-m', strtotime('-1 month', $firstDayTs));
$nextMonth = date('Y-m', strtotime('+1 month', $firstDayTs));
?>
<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h3 class="mb-0">日報月間</h3>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-outline-secondary btn-sm">ダッシュボード</a>
                        <a href="<?= BASE_PATH ?>/daily-report/week" class="btn btn-outline-secondary btn-sm">週間</a>
                        <a href="<?= BASE_PATH ?>/daily-report/timeline" class="btn btn-outline-secondary btn-sm">タイムライン</a>
                        <a href="<?= BASE_PATH ?>/daily-report/list" class="btn btn-outline-secondary btn-sm">一覧</a>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a class="btn btn-outline-primary btn-sm" href="<?= BASE_PATH ?>/daily-report/month?month=<?= $prevMonth ?>"><i class="fas fa-chevron-left me-1"></i>前月</a>
                    <strong><?= date('Y年m月', $firstDayTs) ?></strong>
                    <a class="btn btn-outline-primary btn-sm" href="<?= BASE_PATH ?>/daily-report/month?month=<?= $nextMonth ?>">翌月<i class="fas fa-chevron-right ms-1"></i></a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center bg-white">
                        <thead class="table-light">
                            <tr>
                                <th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cell = 0;
                            echo '<tr>';
                            for ($i = 0; $i < $startWeekday; $i++, $cell++) {
                                echo '<td class="bg-light"></td>';
                            }
                            for ($day = 1; $day <= $daysInMonth; $day++, $cell++) {
                                if ($cell > 0 && $cell % 7 === 0) {
                                    echo '</tr><tr>';
                                }
                                $date = sprintf('%s-%02d', $month, $day);
                                $count = (int)($dailyCounts[$date] ?? 0);
                                echo '<td style="min-height:100px;">';
                                echo '<div class="d-flex justify-content-between align-items-center">';
                                echo '<strong>' . $day . '</strong>';
                                echo '<a class="small" href="' . BASE_PATH . '/daily-report/create?date=' . $date . '">作成</a>';
                                echo '</div>';
                                if ($count > 0) {
                                    echo '<a class="badge bg-primary text-decoration-none mt-2" href="' . BASE_PATH . '/daily-report/timeline?date=' . $date . '">' . $count . '件</a>';
                                } else {
                                    echo '<div class="small text-muted mt-2">0件</div>';
                                }
                                echo '</td>';
                            }
                            while ($cell % 7 !== 0) {
                                echo '<td class="bg-light"></td>';
                                $cell++;
                            }
                            echo '</tr>';
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
