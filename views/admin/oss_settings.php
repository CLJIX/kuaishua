<?php
/**
 * 管理后台 - OSS 配置管理
 *
 * 可用变量：
 * - $settings (array): 当前 OSS 配置（含掩码后的 AK/SK）
 */
$akDisplay  = $settings['oss_ak_display']   ?? '';
$skDisplay  = $settings['oss_sk_display']   ?? '';
$bucket     = $settings['oss_bucket']       ?? '';
$endpoint   = $settings['oss_endpoint']     ?? '';
$region     = $settings['oss_region']       ?? '';
$cdnDomain  = $settings['oss_cdn_domain']   ?? '';
$configured = $settings['oss_configured']    ?? false;
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
                <h4 class="mb-0"><i class="bi bi-cloud"></i> OSS 配置管理</h4>
                <span class="badge <?= $configured ? 'bg-success' : 'bg-secondary' ?> fs-6">
                    <?= $configured ? '已配置' : '未配置' ?>
                </span>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST" action="<?= url('admin', ['action' => 'oss_settings', 'sub_action' => 'update']) ?>" class="col-lg-8">
                        <?= csrfField() ?>

                        <!-- AccessKey ID -->
                        <div class="mb-3">
                            <label for="oss_access_key_id" class="form-label">
                                <i class="bi bi-key"></i> AccessKey ID
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control"
                                       id="oss_access_key_id"
                                       name="oss_access_key_id"
                                       value="<?= e($akDisplay) ?>"
                                       placeholder="请输入 AccessKey ID"
                                       autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="oss_access_key_id">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">留空或保留掩码值将不修改此配置项。</div>
                        </div>

                        <!-- AccessKey Secret -->
                        <div class="mb-3">
                            <label for="oss_access_key_secret" class="form-label">
                                <i class="bi bi-shield-lock"></i> AccessKey Secret
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control"
                                       id="oss_access_key_secret"
                                       name="oss_access_key_secret"
                                       value="<?= e($skDisplay) ?>"
                                       placeholder="请输入 AccessKey Secret"
                                       autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="oss_access_key_secret">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">加密存储，保存后不以明文返回。</div>
                        </div>

                        <hr class="my-4">

                        <!-- Bucket -->
                        <div class="mb-3">
                            <label for="oss_bucket" class="form-label">
                                <i class="bi bi-box"></i> Bucket 名称
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="oss_bucket"
                                   name="oss_bucket"
                                   value="<?= e($bucket) ?>"
                                   placeholder="如 my-bucket">
                        </div>

                        <!-- Endpoint -->
                        <div class="mb-3">
                            <label for="oss_endpoint" class="form-label">
                                <i class="bi bi-hdd-network"></i> Endpoint
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="oss_endpoint"
                                   name="oss_endpoint"
                                   value="<?= e($endpoint) ?>"
                                   placeholder="如 oss-cn-hangzhou.aliyuncs.com">
                            <div class="form-text">不包含 https:// 前缀，填写 OSS 外网访问域名。</div>
                        </div>

                        <!-- Region -->
                        <div class="mb-3">
                            <label for="oss_region" class="form-label">
                                <i class="bi bi-geo-alt"></i> Region
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="oss_region"
                                   name="oss_region"
                                   value="<?= e($region) ?>"
                                   placeholder="如 cn-hangzhou">
                            <div class="form-text">V4 签名所需，通常为 Endpoint 中 oss- 后面的部分（如 cn-hangzhou）。</div>
                        </div>

                        <!-- CDN 域名（可选） -->
                        <div class="mb-4">
                            <label for="oss_cdn_domain" class="form-label">
                                <i class="bi bi-lightning"></i> CDN 加速域名（可选）
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="oss_cdn_domain"
                                   name="oss_cdn_domain"
                                   value="<?= e($cdnDomain) ?>"
                                   placeholder="如 cdn.example.com">
                            <div class="form-text">配置后将通过此域名访问图片，不填则使用 OSS 直访 URL。</div>
                        </div>

                        <!-- Nginx 安全提醒 -->
                        <div class="alert alert-warning d-flex align-items-start" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                            <div>
                                <strong>Nginx 安全配置提醒</strong><br>
                                请确认站点 Nginx 配置已添加禁止访问隐藏文件的规则：
                                <pre class="mb-0 mt-2 bg-light p-2 rounded"><code>location ~ /\. {
    deny all;
    return 404;
}</code></pre>
                            </div>
                        </div>

                        <!-- 按钮组 -->
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> 保存配置
                            </button>
                            <button type="button" id="test-connection-btn" class="btn btn-outline-info" <?= !$configured ? 'disabled' : '' ?>>
                                <i class="bi bi-wifi"></i> 测试连接
                            </button>
                            <a href="<?= url('admin', ['action' => 'settings']) ?>" class="btn btn-outline-secondary">返回配置管理</a>
                        </div>

                        <!-- 测试结果展示区 -->
                        <div id="test-result" class="mt-3" style="display:none"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 密码显示/隐藏切换
    document.querySelectorAll('.toggle-pwd').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = document.getElementById(this.dataset.target);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            var icon = this.querySelector('i');
            icon.className = 'bi bi-' + (input.type === 'password' ? 'eye' : 'eye-slash');
        });
    });

    // 测试连接
    var testBtn = document.getElementById('test-connection-btn');
    var resultDiv = document.getElementById('test-result');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 测试中...';
            resultDiv.style.display = 'none';

            var formData = new FormData();
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            formData.append('sub_action', 'test');

            fetch('<?= url('admin', ['action' => 'oss_settings', 'sub_action' => 'test']) ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var alertClass = data.success ? 'alert-success' : 'alert-danger';
                var icon = data.success ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                var html = '<div class="alert ' + alertClass + '">' +
                    '<div class="d-flex align-items-start">' +
                    '<i class="bi ' + icon + ' me-2 mt-1"></i>' +
                    '<div>' + (data.message || '未知结果');

                // 失败时展示调试详情
                if (!data.success && data.debug) {
                    var d = data.debug;
                    html += '<hr class="my-2">';
                    html += '<div class="small" style="font-family:monospace;opacity:0.9">';
                    html += '<div>AK: ' + (d.ak_mask || '-') +
                            ' &nbsp;|&nbsp; Bucket: ' + (d.bucket || '-') +
                            ' &nbsp;|&nbsp; Region: ' + (d.region || '-') + '</div>';
                    html += '<div>Endpoint: ' + (d.endpoint || '-') +
                            ' &nbsp;|&nbsp; Host: ' + (d.host || '-') + '</div>';
                    html += '<div>HTTP ' + (d.http_code || '-') +
                            (d.oss_error ? ' &nbsp;|&nbsp; OSS: ' + d.oss_error : '') + '</div>';
                    if (d.raw_body && d.raw_body !== '(empty)') {
                        html += '<details class="mt-1"><summary style="cursor:pointer">原始响应</summary>' +
                                '<pre class="mb-0" style="font-size:0.75rem;max-height:120px;overflow:auto;white-space:pre-wrap">' +
                                d.raw_body.replace(/</g, '&lt;') + '</pre></details>';
                    }
                    if (d.client_canonical_request) {
                        html += '<details class="mt-1"><summary style="cursor:pointer">客户端 CanonicalRequest（对比服务端）</summary>' +
                                '<pre class="mb-0" style="font-size:0.75rem;max-height:120px;overflow:auto;white-space:pre-wrap">' +
                                d.client_canonical_request.replace(/</g, '&lt;') + '</pre>' +
                                '<div class="text-muted" style="font-size:0.7rem">Signature: ' + (d.client_signature || '-') + '</div></details>';
                    }
                    html += '</div>';
                }

                html += '</div></div>';
                if (!data.success) {
                    html += '<div class="text-muted small mt-2 ms-4">如您无法解决请联系技术人员</div>';
                }
                html += '</div>';
                resultDiv.innerHTML = html;
                resultDiv.style.display = '';
            })
            .catch(function(err) {
                resultDiv.innerHTML = '<div class="alert alert-danger">网络请求失败：' + err.message + '</div>';
                resultDiv.style.display = '';
            })
            .finally(function() {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="bi bi-wifi"></i> 测试连接';
            });
        });
    }
});
</script>
