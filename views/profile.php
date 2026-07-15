<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- 页面标题 -->
            <h3 class="mb-4"><i class="bi bi-person-gear"></i> 个人中心</h3>

            <!-- 用户信息卡片 -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> 基本信息</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-3 text-muted">用户名</div>
                        <div class="col-sm-9"><?= e($dbUser['username']) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-3 text-muted">角色</div>
                        <div class="col-sm-9">
                            <?php if ($dbUser['role'] === 'admin'): ?>
                                <span class="badge bg-danger">管理员</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">普通用户</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-3 text-muted">注册时间</div>
                        <div class="col-sm-9"><?= e($dbUser['created_at']) ?></div>
                    </div>
                </div>
            </div>

            <!-- 修改邮箱卡片 -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-envelope"></i> 修改邮箱</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('profile') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="profile_action" value="update_email">

                        <!-- 当前邮箱（只读） -->
                        <div class="mb-3">
                            <label class="form-label text-muted">当前邮箱</label>
                            <input type="text" class="form-control" value="<?= e($dbUser['email']) ?>" readonly disabled>
                        </div>

                        <!-- 新邮箱 -->
                        <div class="mb-3">
                            <label for="email" class="form-label">新邮箱</label>
                            <input type="email"
                                   class="form-control"
                                   id="email"
                                   name="email"
                                   placeholder="请输入新邮箱地址"
                                   required>
                        </div>

                        <!-- 当前密码（验证身份） -->
                        <div class="mb-3">
                            <label for="password" class="form-label">当前密码</label>
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   placeholder="请输入当前密码以验证身份"
                                   required>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> 更新邮箱
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 修改密码卡片 -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-lock"></i> 修改密码</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('profile') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="profile_action" value="update_password">

                        <!-- 当前密码 -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">当前密码</label>
                            <input type="password"
                                   class="form-control"
                                   id="current_password"
                                   name="current_password"
                                   placeholder="请输入当前密码"
                                   required>
                        </div>

                        <!-- 新密码 -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">新密码</label>
                            <input type="password"
                                   class="form-control"
                                   id="new_password"
                                   name="new_password"
                                   placeholder="请输入新密码（至少 6 位）"
                                   minlength="6"
                                   required>
                        </div>

                        <!-- 确认新密码 -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认新密码</label>
                            <input type="password"
                                   class="form-control"
                                   id="confirm_password"
                                   name="confirm_password"
                                   placeholder="请再次输入新密码"
                                   required>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key"></i> 更新密码
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
