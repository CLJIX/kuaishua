<?php
/**
 * 管理后台 - 用户管理二次密码验证页面
 * 进入用户管理前需验证管理员登录密码
 *
 * 无额外变量，仅使用公共函数
 */
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- 左侧管理侧边栏 -->
        <div class="col-md-3 mb-3">
            <?php require_once __DIR__ . '/_sidebar.php'; ?>
        </div>

        <!-- 右侧内容区 -->
        <div class="col-md-9 d-flex align-items-center justify-content-center" style="min-height: 60vh;">
            <?php include __DIR__ . '/_flash.php'; ?>
            <div class="card border-0 shadow-sm" style="max-width: 420px; width: 100%;">
                <div class="card-body p-4">
                    <h4 class="card-title text-center mb-4">
                        <i class="bi bi-shield-lock"></i> 安全验证
                    </h4>
                    <p class="text-muted text-center mb-4">进入用户管理需要再次验证您的登录密码</p>

                    <form method="post" action="<?= url('admin', ['action' => 'users', 'sub_action' => 'verify']) ?>">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label for="password" class="form-label">管理员密码</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="请输入您的登录密码" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> 验证并进入
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="<?= url('admin') ?>" class="text-muted text-decoration-none">
                            <i class="bi bi-arrow-left"></i> 返回管理首页
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
