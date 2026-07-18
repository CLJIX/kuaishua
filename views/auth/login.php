<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <!-- 登录卡片 -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-lightning-charge-fill text-primary" style="font-size: 2.5rem;"></i>
                        <h4 class="mt-2">登录<?= e(siteName()) ?></h4>
                        <p class="text-muted">登录后即可开始刷题</p>
                    </div>

                    <form method="POST" action="<?= url('login') ?>">
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

                        <!-- 密码 -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock"></i> 密码
                            </label>
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   placeholder="请输入密码"
                                   required>
                        </div>

                        <!-- 记住我 -->
                        <div class="mb-3 form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   id="remember"
                                   name="remember"
                                   value="1">
                            <label class="form-check-label" for="remember">
                                记住我（3天内免登录）
                            </label>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> 登录
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 底部链接 -->
                <div class="card-footer bg-white text-center py-3 border-0">
                    <span class="text-muted">还没有账号？</span>
                    <a href="<?= url('register') ?>" class="text-decoration-none">立即注册</a>
                </div>
            </div>
        </div>
    </div>
</div>
