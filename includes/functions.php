<?php
/**
 * 公共辅助函数库
 * 提供 Session 管理、输入过滤、重定向、Flash 消息等常用功能
 */

/**
 * 判断当前请求是否为 HTTPS
 * 用于动态设置 Cookie 的 Secure 标志（HTTP 环境下不设 Secure，否则 Cookie 无法设置）
 *
 * @return bool 是否为 HTTPS 请求
 */
function isHttps(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/**
 * 启动安全 Session
 * 设置安全的 Cookie 参数，防止 Session 劫持
 */
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // 设置安全的 Session cookie 参数
        session_set_cookie_params([
            'lifetime' => 3600,      // Session 有效期 1 小时
            'path' => '/',           // 全站可用
            'httponly' => true,      // 禁止 JavaScript 访问 Cookie
            'secure'   => isHttps(), // HTTPS 环境下启用 Secure 标志
            'samesite' => 'Lax'     // 防止 CSRF 的 SameSite 策略
        ]);
        session_start();
    }
}

/**
 * 页面重定向
 * 发送 Location 头并终止脚本执行
 *
 * @param string $url 目标 URL
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * 安全的图片上传辅助函数
 * 对上传文件进行类型、大小、扩展名校验，并重命名为随机文件名后保存
 *
 * @param array  $file    $_FILES 中的文件数组（如 $_FILES['site_logo']）
 * @param string $subdir  保存子目录（如 'logos'，最终保存到 uploads/logos/）
 * @param array  $config  可选配置：allowedMime、allowedExt、maxSize（字节）
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function uploadImage(array $file, string $subdir = '', array $config = []): array {
    $defaultConfig = [
        'allowedMime' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        'allowedExt'  => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'maxSize'     => 2 * 1024 * 1024, // 2MB
    ];
    $config = array_merge($defaultConfig, $config);

    // 检查上传错误
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'path' => '', 'error' => '没有文件被上传'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => '文件大小超过服务器限制（php.ini 中 upload_max_filesize）',
            UPLOAD_ERR_FORM_SIZE  => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL    => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE    => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION  => 'PHP 扩展阻止了文件上传',
        ];
        $message = $errorMessages[$file['error']] ?? '文件上传失败，错误码：' . $file['error'];
        return ['success' => false, 'path' => '', 'error' => $message];
    }

    // 确保是 HTTP 上传的文件
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'path' => '', 'error' => '非法的文件来源'];
    }

    // 校验文件大小
    if ($file['size'] > $config['maxSize']) {
        $maxMb = round($config['maxSize'] / 1024 / 1024, 1);
        return ['success' => false, 'path' => '', 'error' => '文件大小超过限制（最大 ' . $maxMb . 'MB）'];
    }

    // 校验扩展名
    $originalName = $file['name'] ?? '';
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $config['allowedExt'], true)) {
        return ['success' => false, 'path' => '', 'error' => '不允许的文件类型，仅支持：' . implode(', ', $config['allowedExt'])];
    }

    // 校验文件类型（优先 finfo，未启用时回退到 getimagesize + SVG 内容校验）
    $mimeValid = false;
    $detectedMime = 'unknown';

    if (extension_loaded('fileinfo')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detectedMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $mimeValid = in_array($detectedMime, $config['allowedMime'], true);
        }
    }

    if (!$mimeValid) {
        // finfo 不可用时，对图片用 getimagesize 校验，对 SVG 做内容嗅探
        if ($ext === 'svg') {
            $content = file_get_contents($file['tmp_name']);
            $mimeValid = is_string($content)
                && (stripos($content, '<svg') !== false || stripos($content, '<?xml') === 0);
        } else {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo !== false) {
                $detectedMime = image_type_to_mime_type($imageInfo[2]);
                $mimeValid = in_array($detectedMime, $config['allowedMime'], true);
            }
        }
    }

    if (!$mimeValid) {
        return ['success' => false, 'path' => '', 'error' => '文件类型校验失败，检测到：' . $detectedMime];
    }

    // 构造保存目录
    $uploadDir = __DIR__ . '/../uploads' . ($subdir ? '/' . trim($subdir, '/') : '');
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['success' => false, 'path' => '', 'error' => '无法创建上传目录 ' . $uploadDir];
        }
    }

    // 生成随机文件名
    $newName = bin2hex(random_bytes(8)) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $newName;
    $relativePath = 'uploads' . ($subdir ? '/' . trim($subdir, '/') : '') . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'path' => '', 'error' => '保存文件失败'];
    }

    return ['success' => true, 'path' => $relativePath, 'error' => ''];
}

/**
 * 删除上传的图片
 *
 * @param string $relativePath 相对路径（如 uploads/logos/abc.png）
 * @return bool 是否删除成功
 */
function deleteUploadedImage(string $relativePath): bool {
    if (empty($relativePath)) {
        return false;
    }

    $file = realpath(__DIR__ . '/../' . ltrim($relativePath, '/'));
    if ($file === false || !is_file($file)) {
        return false;
    }

    $uploadsDir = realpath(__DIR__ . '/../uploads');
    if ($uploadsDir === false || strpos($file, $uploadsDir) !== 0) {
        return false;
    }

    return unlink($file);
}

/**
 * HTML 特殊字符转义（防 XSS）
 * 将用户输入中的特殊字符转换为 HTML 实体
 *
 * @param string $value 需要转义的字符串
 * @return string 转义后的安全字符串
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * 设置 Flash 消息（一次性消息）
 * 消息在下次请求时通过 getFlash() 获取后自动清除
 *
 * @param string $type    消息类型（success / danger / warning / info）
 * @param string $message 消息内容
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * 获取并清除 Flash 消息
 * 读取后立即从 Session 中删除，确保消息只显示一次
 *
 * @return array|null 包含 type 和 message 的数组，无消息时返回 null
 */
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * 获取当前登录用户信息
 *
 * @return array|null 用户信息数组，未登录时返回 null
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * 检查是否已登录
 *
 * @return bool 是否已登录
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

/**
 * 站点配置缓存
 */
$GLOBALS['_site_settings'] = null;

/**
 * 获取站点配置项
 * 首次调用时从数据库加载全部配置并缓存到当前请求，后续调用直接返回缓存
 *
 * @param string $key     配置键名
 * @param string $default 默认值
 * @return string 配置值
 */
function siteSetting(string $key, string $default = ''): string {
    if ($GLOBALS['_site_settings'] === null) {
        require_once __DIR__ . '/../models/Setting.php';
        $settingModel = new SettingModel();
        $GLOBALS['_site_settings'] = $settingModel->getAll();
    }
    return (string) ($GLOBALS['_site_settings'][$key] ?? $default);
}

/**
 * 获取站点名称（兼容旧版硬编码）
 *
 * @return string
 */
function siteName(): string {
    return siteSetting('site_name', '小题快刷');
}

/**
 * 要求登录，未登录则跳转到登录页
 * 用于需要登录才能访问的页面顶部调用
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', '请先登录');
        redirect('index.php?page=login');
    }
}

/**
 * 要求管理员权限
 * 非管理员用户将被重定向到首页并提示无权访问
 */
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        setFlash('danger', '无权访问该页面');
        redirect('index.php?page=home');
    }
}

/**
 * 生成安全的 URL（基于 index.php?page= 路由）
 * 统一项目内部链接生成方式，便于后续路由调整
 *
 * @param string $page   页面名称
 * @param array  $params 额外的 GET 参数（键值对）
 * @return string 生成的 URL
 */
function url(string $page, array $params = []): string {
    $url = 'index.php?page=' . urlencode($page);
    foreach ($params as $key => $value) {
        $url .= '&' . urlencode($key) . '=' . urlencode($value);
    }
    return $url;
}

/**
 * 获取 GET 参数（带默认值）
 *
 * @param string $key     参数名
 * @param mixed  $default 参数不存在时的默认值
 * @return mixed 参数值或默认值
 */
function getParam(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * 获取 POST 参数（带默认值）
 *
 * @param string $key     参数名
 * @param mixed  $default 参数不存在时的默认值
 * @return mixed 参数值或默认值
 */
function postParam(string $key, $default = null) {
    return $_POST[$key] ?? $default;
}

/**
 * 判断是否为 POST 请求
 *
 * @return bool 是否为 POST 请求
 */
function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * 净化 HTML 内容（允许安全标签，过滤脚本和事件属性）
 * 用于题目内容和解析等需要支持基础 HTML 的字段
 */
function purify(string $html): string {
    // 允许的标签列表
    $allowedTags = '<b><i><u><strong><em><br><p><ul><ol><li><code><pre><span><sub><sup><hr><h1><h2><h3><h4><h5><h6><table><tr><td><th><thead><tbody><blockquote><img>';
    // 先用 strip_tags 去除不允许的标签
    $clean = strip_tags($html, $allowedTags);
    // 移除所有 on* 事件属性（如 onclick, onerror, onload 等）
    $clean = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
    // 移除 javascript: 协议
    $clean = preg_replace('/javascript\s*:/i', '', $clean);
    // 移除 data: 协议（除图片外）
    $clean = preg_replace('/data\s*:(?!image\/)/i', '', $clean);
    return $clean;
}

/**
 * 将内容安全地嵌入到 HTML data 属性中，供客户端 Markdown 渲染
 * 先 HTML 转义，防止 XSS；客户端由 marked.js + DOMPurify 渲染
 *
 * @param string $content 原始内容（可能是 Markdown 或 HTML）
 * @return string 转义后的安全字符串，可直接放入 data-* 属性
 */
function mdAttr(string $content): string {
    return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
}

/**
 * 使用 AES-256-GCM 加密明文（用于加密 OSS AK/SK 后存入数据库）
 *
 * 加密结果格式：base64(iv + tag + ciphertext)
 * - iv:  12 字节随机初始化向量
 * - tag: 16 字节认证标签
 * - ciphertext: 加密后的密文
 *
 * @param string $plaintext 待加密的明文
 * @return string base64 编码的加密结果，失败返回空字符串
 */
function ossEncrypt(string $plaintext): string {
    if ($plaintext === '') return '';
    // 优先从 $_ENV 读取（putenv 可能被服务器禁用，导致 getenv 无法获取）
    $masterKey = $_ENV['OSS_MASTER_KEY'] ?? $_SERVER['OSS_MASTER_KEY'] ?? getenv('OSS_MASTER_KEY');
    if (!$masterKey) {
        error_log('ossEncrypt: OSS_MASTER_KEY 未配置');
        return '';
    }
    $key = base64_decode($masterKey);
    if ($key === false || strlen($key) !== 32) {
        error_log('ossEncrypt: OSS_MASTER_KEY 解码后长度无效');
        return '';
    }
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        error_log('ossEncrypt: openssl_encrypt 失败');
        return '';
    }
    return base64_encode($iv . $tag . $ciphertext);
}

/**
 * 解密 ossEncrypt() 加密的密文
 *
 * @param string $encrypted base64 编码的加密字符串
 * @return string 解密后的明文，失败返回空字符串
 */
function ossDecrypt(string $encrypted): string {
    if ($encrypted === '') return '';
    $masterKey = $_ENV['OSS_MASTER_KEY'] ?? $_SERVER['OSS_MASTER_KEY'] ?? getenv('OSS_MASTER_KEY');
    if (!$masterKey) {
        error_log('ossDecrypt: OSS_MASTER_KEY 未配置');
        return '';
    }
    $key = base64_decode($masterKey);
    if ($key === false || strlen($key) !== 32) {
        error_log('ossDecrypt: OSS_MASTER_KEY 解码后长度无效');
        return '';
    }
    $raw = base64_decode($encrypted);
    if ($raw === false || strlen($raw) < 29) { // 12(iv) + 16(tag) + at least 1 byte
        error_log('ossDecrypt: 密文格式无效');
        return '';
    }
    $iv         = substr($raw, 0, 12);
    $tag        = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);
    $plaintext  = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plaintext === false) {
        error_log('ossDecrypt: 解密失败（密钥不匹配或密文已篡改）');
        return '';
    }
    return $plaintext;
}

/**
 * 获取解密后的 OSS 配置数组
 * 从 site_settings 读取并解密 AK/SK，Bucket/Endpoint 等直接返回
 *
 * @return array ['access_key_id', 'access_key_secret', 'bucket', 'endpoint', 'region', 'cdn_domain']
 *               未配置时返回空数组
 */
function getOssConfig(): array {
    require_once __DIR__ . '/../models/Setting.php';
    $settingModel = new SettingModel();
    $ak = ossDecrypt($settingModel->get('oss_access_key_id'));
    $sk = ossDecrypt($settingModel->get('oss_access_key_secret'));
    if ($ak === '' || $sk === '') return [];
    // 清洗 endpoint：剥除用户可能误输入的 https:// 或 http:// 前缀
    $endpoint = trim($settingModel->get('oss_endpoint'));
    $endpoint = preg_replace('#^https?://#i', '', $endpoint);
    $endpoint = rtrim($endpoint, '/');
    return [
        'access_key_id'     => $ak,
        'access_key_secret' => $sk,
        'bucket'            => trim($settingModel->get('oss_bucket')),
        'endpoint'          => $endpoint,
        'region'            => trim($settingModel->get('oss_region')),
        'cdn_domain'        => trim($settingModel->get('oss_cdn_domain')),
    ];
}

/**
 * 生成 OSS 图片处理 URL
 * 根据预设名称自动拼接阿里云 OSS 图片处理参数
 *
 * @param string $ossPath OSS 对象路径（如 media/2024/01/abc.png）
 * @param string $preset  预设名称: thumbnail | preview | display | original
 * @return string 带图片处理参数的完整 URL
 */
function ossImageUrl(string $ossPath, string $preset = 'display'): string {
    if ($ossPath === '') return '';

    // 预设参数映射
    $presets = [
        'thumbnail' => 'image/resize,w_200,h_200,m_fill/format,webp',
        'preview'   => 'image/resize,w_600/quality,q_80/format,webp',
        'display'   => 'quality,q_90/format,webp',
        'original'  => '',
    ];
    $process = $presets[$preset] ?? $presets['display'];

    // 构造基础 URL
    $config = getOssConfig();
    if (empty($config)) return $ossPath; // OSS 未配置，原样返回

    $cdnDomain = $config['cdn_domain'] ?? '';
    if ($cdnDomain !== '') {
        $baseUrl = 'https://' . rtrim($cdnDomain, '/') . '/' . ltrim($ossPath, '/');
    } else {
        $bucket   = $config['bucket']   ?? '';
        $endpoint = $config['endpoint'] ?? '';
        $baseUrl  = 'https://' . $bucket . '.' . preg_replace('#^https?://#', '', $endpoint) . '/' . ltrim($ossPath, '/');
    }

    // 拼接图片处理参数
    if ($process !== '') {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        return $baseUrl . $separator . 'x-oss-process=' . $process;
    }
    return $baseUrl;
}

/**
 * 去除 Markdown 语法符号，返回纯文本摘要
 * 用于列表页等只需要简短预览的场景
 *
 * @param string $content 原始内容（可能是 Markdown 或 HTML）
 * @param int $length 截取长度（默认 100）
 * @return string 纯文本摘要
 */
function mdExcerpt(string $content, int $length = 100): string {
    // 先去除 HTML 标签
    $text = strip_tags($content);
    // 去除常见 Markdown 语法符号
    $text = preg_replace('/^#{1,6}\s+/m', '', $text);           // 标题 #
    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);       // 粗体 **
    $text = preg_replace('/\*(.+?)\*/', '$1', $text);           // 斜体 *
    $text = preg_replace('/`(.+?)`/', '$1', $text);             // 行内代码 `
    $text = preg_replace('/!\[.*?\]\(.*?\)/', '', $text);       // 图片
    $text = preg_replace('/\[(.+?)\]\(.*?\)/', '$1', $text);    // 链接
    $text = preg_replace('/^[-*+]\s+/m', '', $text);            // 无序列表
    $text = preg_replace('/^\d+\.\s+/m', '', $text);            // 有序列表
    $text = preg_replace('/^>\s+/m', '', $text);                // 引用
    $text = preg_replace('/\s+/', ' ', $text);                  // 多余空白
    return mb_substr(trim($text), 0, $length);
}
