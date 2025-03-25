<?php
// views/organization/view.php
$pageTitle = $organization['name'] . ' - 組織詳細';
?>
<div class="container-fluid" data-page-type="view">
    <input type="hidden" id="organization-id" value="<?php echo $organization['id']; ?>">

    <div class="row mb-4">
        <div class="col">
            <h1 class="h3"><?php echo htmlspecialchars($organization['name']); ?></h1>
        </div>
        <!--
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/organizations/edit/<?php echo $organization['id']; ?>" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> 編集
            </a>
            <?php if (empty($children)): ?>
                <button type="button" class="btn btn-danger btn-delete me-2" data-url="<?php echo BASE_PATH; ?>/api/organizations/<?php echo $organization['id']; ?>" data-confirm="組織「<?php echo htmlspecialchars($organization['name']); ?>」を削除しますか？">
                    <i class="fas fa-trash"></i> 削除
                </button>
            <?php endif; ?>
            <a href="<?php echo BASE_PATH; ?>/organizations" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
            -->
        <div class="col-auto">
            <?php if ($this->auth->isAdmin()): // 管理者のみ編集・削除ボタンを表示 
            ?>
                <a href="<?php echo BASE_PATH; ?>/organizations/edit/<?php echo $organization['id']; ?>" class="btn btn-primary me-2">
                    <i class="fas fa-edit"></i> 編集
                </a>
                <?php if (empty($children)): ?>
                    <button type="button" class="btn btn-danger btn-delete me-2" data-url="<?php echo BASE_PATH; ?>/api/organizations/<?php echo $organization['id']; ?>" data-confirm="組織「<?php echo htmlspecialchars($organization['name']); ?>」を削除しますか？">
                        <i class="fas fa-trash"></i> 削除
                    </button>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?php echo BASE_PATH; ?>/organizations" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">組織情報</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">組織名</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($organization['name']); ?></div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">組織コード</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($organization['code']); ?></div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">親組織</div>
                        <div class="col-md-8">
                            <?php if ($parent): ?>
                                <a href="<?php echo BASE_PATH; ?>/organizations/view/<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </a>
                            <?php else: ?>
                                なし
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($organization['description'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">説明</div>
                            <div class="col-md-8"><?php echo nl2br(htmlspecialchars($organization['description'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($children)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">子組織</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($children as $child): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo BASE_PATH; ?>/organizations/view/<?php echo $child['id']; ?>">
                                        <?php echo htmlspecialchars($child['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">所属ユーザー</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($users)): ?>
                        <div class="table-responsive">
                            <table id="users-table" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ユーザー名</th>
                                        <th>氏名</th>
                                        <th>メールアドレス</th>
                                        <th>状態</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['display_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <span class="badge bg-success">有効</span>
                                                <?php elseif ($user['status'] === 'inactive'): ?>
                                                    <span class="badge bg-warning">無効</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">停止</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/users/view/<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="mb-0">所属ユーザーはいません</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>