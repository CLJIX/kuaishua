<?php
/**
 * 管理后台 - 用户管理主页面
 * 支持用户列表、批量新建、修改角色、修改密码、删除
 *
 * @var array $users         所有用户列表
 * @var int   $currentUserId 当前登录管理员的用户ID
 */
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- 左侧管理侧边栏 -->
        <div class="col-md-3 mb-3">
            <?php require_once __DIR__ . '/_sidebar.php'; ?>
        </div>

        <!-- 右侧内容区 -->
        <div class="col-md-9">
            <?php include __DIR__ . '/_flash.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">
                    <i class="bi bi-people"></i> 用户管理
                </h3>
                <!-- 退出验证按钮 -->
                <form method="post" action="<?= url('admin', ['action' => 'users', 'sub_action' => 'logout_verify']) ?>" class="d-inline">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-right"></i> 退出验证
                    </button>
                </form>
            </div>

            <!-- 用户列表 -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> 用户列表</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">ID</th>
                                    <th>用户名</th>
                                    <th>邮箱</th>
                                    <th width="100">角色</th>
                                    <th>注册时间</th>
                                    <th width="260">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">暂无用户数据</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                        <tr class="<?= $u['id'] == $currentUserId ? 'table-info' : '' ?>">
                                            <td><?= e((string)$u['id']) ?></td>
                                            <td>
                                                <?= e($u['username']) ?>
                                                <?php if ($u['id'] == $currentUserId): ?>
                                                    <span class="badge bg-info ms-1">当前</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($u['email']) ?></td>
                                            <td>
                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger">管理员</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">普通用户</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($u['created_at'] ?? '') ?></td>
                                            <td>
                                                <!-- 角色切换（不能修改自己） -->
                                                <?php if ($u['id'] != $currentUserId): ?>
                                                <form method="post" action="<?= url('admin', ['action' => 'users', 'sub_action' => 'update_role']) ?>" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="id" value="<?= e((string)$u['id']) ?>">
                                                    <select name="role" class="form-select form-select-sm d-inline-block" style="width:100px;"
                                                            onchange="this.form.submit()">
                                                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>普通用户</option>
                                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>管理员</option>
                                                    </select>
                                                </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">不可修改</span>
                                                <?php endif; ?>

                                                <!-- 修改密码按钮 -->
                                                <button type="button" class="btn btn-outline-primary btn-sm ms-1"
                                                        data-bs-toggle="modal" data-bs-target="#passwordModal"
                                                        data-user-id="<?= e((string)$u['id']) ?>"
                                                        data-username="<?= e($u['username']) ?>">
                                                    <i class="bi bi-key"></i>
                                                </button>

                                                <!-- 删除（不能删除自己） -->
                                                <?php if ($u['id'] != $currentUserId): ?>
                                                <form method="post" action="<?= url('admin', ['action' => 'users', 'sub_action' => 'delete']) ?>" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="id" value="<?= e((string)$u['id']) ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm btn-delete ms-1"
                                                            onclick="return confirm('确定删除用户 <?= e($u['username']) ?> 吗？')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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

            <!-- 批量新建用户 -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person-plus"></i> 批量新建用户</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= url('admin', ['action' => 'users', 'sub_action' => 'create']) ?>" id="batchCreateForm">
                        <?= csrfField() ?>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="batchTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">#</th>
                                        <th>用户名</th>
                                        <th>邮箱</th>
                                        <th>密码（至少6位）</th>
                                        <th width="100">角色</th>
                                        <th width="50">操作</th>
                                    </tr>
                                </thead>
                                <tbody id="batchBody">
                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                    <tr>
                                        <td class="row-index"><?= $i + 1 ?></td>
                                        <td><input type="text" class="form-control form-control-sm" name="users[<?= $i ?>][username]" placeholder="用户名"></td>
                                        <td><input type="email" class="form-control form-control-sm" name="users[<?= $i ?>][email]" placeholder="邮箱"></td>
                                        <td><input type="password" class="form-control form-control-sm" name="users[<?= $i ?>][password]" placeholder="密码"></td>
                                        <td>
                                            <select class="form-select form-select-sm" name="users[<?= $i ?>][role]">
                                                <option value="user">普通用户</option>
                                                <option value="admin">管理员</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row" title="删除行">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="addRowBtn">
                                <i class="bi bi-plus"></i> 添加一行
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> 批量创建
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 修改密码 Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= url('admin', ['action' => 'users', 'sub_action' => 'update_password']) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="passwordUserId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">
                        <i class="bi bi-key"></i> 修改密码 - <span id="passwordUsername"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">新密码</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password"
                               placeholder="请输入新密码（至少6位）" minlength="6" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确认修改</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ---- 修改密码 Modal：填充用户信息 ----
    var passwordModal = document.getElementById('passwordModal');
    if (passwordModal) {
        passwordModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('passwordUserId').value = button.getAttribute('data-user-id');
            document.getElementById('passwordUsername').textContent = button.getAttribute('data-username');
        });
    }

    // ---- 批量新建：动态增删行 ----
    var batchBody = document.getElementById('batchBody');
    var addRowBtn = document.getElementById('addRowBtn');
    var maxRows = 10;

    function updateRowIndices() {
        var rows = batchBody.querySelectorAll('tr');
        rows.forEach(function(row, idx) {
            row.querySelector('.row-index').textContent = idx + 1;
            // 更新 name 属性中的索引
            var inputs = row.querySelectorAll('input, select');
            inputs.forEach(function(input) {
                var name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/users\[\d+\]/, 'users[' + idx + ']'));
                }
            });
        });
        // 控制添加按钮状态
        if (addRowBtn) {
            addRowBtn.disabled = rows.length >= maxRows;
        }
    }

    if (addRowBtn) {
        addRowBtn.addEventListener('click', function() {
            var rows = batchBody.querySelectorAll('tr');
            if (rows.length >= maxRows) return;
            var idx = rows.length;
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="row-index">' + (idx + 1) + '</td>' +
                '<td><input type="text" class="form-control form-control-sm" name="users[' + idx + '][username]" placeholder="用户名"></td>' +
                '<td><input type="email" class="form-control form-control-sm" name="users[' + idx + '][email]" placeholder="邮箱"></td>' +
                '<td><input type="password" class="form-control form-control-sm" name="users[' + idx + '][password]" placeholder="密码"></td>' +
                '<td><select class="form-select form-select-sm" name="users[' + idx + '][role]">' +
                '<option value="user">普通用户</option><option value="admin">管理员</option></select></td>' +
                '<td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-row" title="删除行"><i class="bi bi-x"></i></button></td>';
            batchBody.appendChild(tr);
            updateRowIndices();
        });
    }

    // 删除行（事件委托）
    if (batchBody) {
        batchBody.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-remove-row');
            if (btn) {
                var rows = batchBody.querySelectorAll('tr');
                if (rows.length <= 1) return; // 至少保留一行
                btn.closest('tr').remove();
                updateRowIndices();
            }
        });
    }
});
</script>
