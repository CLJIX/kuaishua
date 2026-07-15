<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- 注册卡片 -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-lightning-charge-fill text-primary" style="font-size: 2.5rem;"></i>
                        <h4 class="mt-2">注册小题快刷</h4>
                        <p class="text-muted">创建账号，开始高效刷题</p>
                    </div>

                    <form method="POST" action="<?= url('register') ?>">
                        <?= csrfField() ?>

                        <!-- 用户名 -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person"></i> 用户名
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="username"
                                   name="username"
                                   placeholder="请输入用户名"
                                   required
                                   autofocus>
                        </div>

                        <!-- 邮箱 -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope"></i> 邮箱
                            </label>
                            <input type="email"
                                   class="form-control"
                                   id="email"
                                   name="email"
                                   placeholder="请输入邮箱地址"
                                   required>
                        </div>

                        <!-- 密码 -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock"></i> 密码
                            </label>
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   placeholder="请设置密码（至少6位）"
                                   required
                                   minlength="6">
                        </div>

                        <!-- 确认密码 -->
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">
                                <i class="bi bi-lock-fill"></i> 确认密码
                            </label>
                            <input type="password"
                                   class="form-control"
                                   id="password_confirm"
                                   name="password_confirm"
                                   placeholder="请再次输入密码"
                                   required
                                   minlength="6">
                        </div>

                        <!-- 提交按钮 -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus"></i> 注册
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 底部链接 -->
                <div class="card-footer bg-white text-center py-3 border-0">
                    <span class="text-muted">已有账号？</span>
                    <a href="<?= url('login') ?>" class="text-decoration-none">立即登录</a>
                </div>
            </div>
        </div>
    </div>
</div>
