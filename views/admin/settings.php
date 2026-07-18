<?php
/**
 * 管理后台 - 配置管理
 * 支持修改站点名称、Logo、是否允许注册等全局配置
 *
 * 可用变量：
 * - $settings (array): 当前配置键值对
 */
$siteName      = $settings['site_name']      ?? '小题快刷';
$siteLogo      = $settings['site_logo']      ?? '';
$allowRegister = ($settings['allow_register'] ?? '1') === '1';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <!-- 侧边栏 -->
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <?php include __DIR__ . '/_sidebar.php'; ?>
        </div>

        <!-- 主内容区 -->
        <div class="col-md-9 col-lg-10">
            <?php include __DIR__ . '/_flash.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class="bi bi-gear"></i> 配置管理</h4>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST" action="<?= url('admin', ['action' => 'settings', 'sub_action' => 'update']) ?>" class="col-lg-8" enctype="multipart/form-data">
                        <?= csrfField() ?>

                        <!-- 站点名称 -->
                        <div class="mb-3">
                            <label for="site_name" class="form-label">
                                <i class="bi bi-type"></i> 站点名称
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="site_name"
                                   name="site_name"
                                   value="<?= e($siteName) ?>"
                                   placeholder="请输入站点名称"
                                   required>
                        </div>

                        <!-- 站点 Logo -->
                        <div class="mb-3">
                            <label for="site_logo" class="form-label">
                                <i class="bi bi-image"></i> 站点 Logo（URL 或上传文件）
                            </label>
                            <input type="text"
                                   class="form-control mb-2"
                                   id="site_logo"
                                   name="site_logo"
                                   value="<?= e($siteLogo) ?>"
                                   placeholder="请输入 Logo URL 或留空">

                            <!-- 上传文件 -->
                            <input type="file"
                                   class="form-control"
                                   id="site_logo_file"
                                   name="site_logo_file"
                                   accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml">
                            <div class="form-text">
                                支持 PNG、JPG、JPEG、GIF、WEBP、SVG，文件大小不超过 2MB。
                                上传文件会覆盖上方输入框中的 URL。
                            </div>
                            <?php if ($siteLogo !== ''): ?>
                                <div class="mt-2">
                                    <span class="text-muted">当前预览：</span>
                                    <img src="<?= e($siteLogo) ?>" alt="站点 Logo" style="max-height: 40px; max-width: 160px;" class="border p-1 rounded">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 是否允许注册 -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="allow_register"
                                       name="allow_register"
                                       value="1"
                                       <?= $allowRegister ? 'checked' : '' ?>>
                                <label class="form-check-label" for="allow_register">
                                    允许新用户注册
                                </label>
                            </div>
                            <div class="form-text">关闭后，前台将不再显示注册入口，且直接访问注册页面会被拒绝。</div>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> 保存配置
                            </button>
                            <a href="<?= url('admin') ?>" class="btn btn-outline-secondary">返回概览</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- OSS 对象存储配置入口 -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h5><i class="bi bi-cloud"></i> OSS 对象存储</h5>
                    <p class="text-muted mb-3">配置阿里云 OSS 实现图片云端存储，支持编辑器粘贴上传和媒体库管理。</p>
                    <a href="<?= url('admin', ['action' => 'oss_settings']) ?>" class="btn btn-outline-primary">
                        <i class="bi bi-gear"></i> 前往 OSS 配置
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
