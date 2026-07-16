<?php
/**
 * 公共辅助函数库
 * 提供 Session 管理、输入过滤、重定向、Flash 消息等常用功能
 */

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
