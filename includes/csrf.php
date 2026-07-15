<?php
/**
 * CSRF Token 管理
 * 防范跨站请求伪造攻击
 *
 * 使用方式：
 * - 在表单中调用 csrfField() 输出隐藏字段
 * - 在处理 POST 请求时调用 verifyCsrfToken() 验证
 * - 关键操作完成后可调用 refreshCsrfToken() 刷新令牌
 *
 * 依赖：functions.php 中的 e() 函数用于 HTML 转义
 */

/**
 * 生成 CSRF Token 并存入 Session
 * 如果 Session 中已存在 Token 则直接返回，不会重复生成
 *
 * @return string 当前有效的 CSRF Token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        // 使用密码学安全的随机字节生成 64 位十六进制 Token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 获取 CSRF Token 的隐藏表单字段 HTML
 * 直接嵌入到 <form> 标签内部即可
 *
 * @return string HTML 隐藏输入字段
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * 验证 CSRF Token
 * 从 POST 数据中取出 Token，与 Session 中的 Token 进行比对
 * 使用 hash_equals 进行时间安全的比较，防止时序攻击
 *
 * @return bool 验证是否通过
 */
function verifyCsrfToken(): bool {
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    // 任一为空则验证失败
    if (empty($token) || empty($sessionToken)) {
        return false;
    }

    // 时间安全比较，防止通过响应时间差异推断 Token 内容
    $valid = hash_equals($sessionToken, $token);
    // 验证后刷新 Token（一次性使用）
    refreshCsrfToken();
    return $valid;
}

/**
 * 刷新 CSRF Token（在关键操作后调用）
 * 例如用户登录、权限变更等操作后应刷新 Token
 */
function refreshCsrfToken(): void {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
