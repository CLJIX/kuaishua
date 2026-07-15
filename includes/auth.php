<?php
/**
 * 认证与权限管理
 * 处理用户登录、登出和权限验证
 *
 * 依赖：
 * - functions.php 中的 startSecureSession()、setFlash()、redirect()、url()、isLoggedIn()
 * - 需要在调用本文件函数前先 require functions.php 并启动 Session
 */

/**
 * 用户登录
 * 验证成功后将用户信息存入 Session，并重新生成 Session ID
 * 重新生成 Session ID 可防止会话固定攻击
 *
 * @param array $user 从数据库查询到的用户记录（需包含 id, username, email, role 字段）
 */
function loginUser(array $user): void {
    // 重新生成 Session ID，防止会话固定攻击
    // 参数 true 表示删除旧的 Session 文件
    session_regenerate_id(true);

    // 只存储必要的用户信息到 Session，避免泄露敏感字段（如密码哈希）
    $_SESSION['user'] = [
        'id'       => $user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $user['role']
    ];
}

/**
 * 用户登出
 * 彻底清除 Session 数据、删除 Session Cookie，并销毁 Session
 * 最后重新启动 Session 以便设置 Flash 消息，然后重定向到首页
 */
function logoutUser(): void {
    // 清空所有 Session 变量
    $_SESSION = [];

    // 删除客户端的 Session Cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,         // 设置为过期时间，触发浏览器删除
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // 销毁服务端的 Session 数据
    session_destroy();

    // 重新启动 Session，以便设置 Flash 消息（Session 已销毁，需要重新初始化）
    startSecureSession();
    setFlash('success', '已成功登出');
    redirect(url('home'));
}

/**
 * 检查当前用户是否为管理员
 *
 * @return bool 是否为管理员角色
 */
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

/**
 * 检查当前用户是否有指定角色
 *
 * @param string $role 角色名称（如 'admin'、'user'）
 * @return bool 是否拥有该角色
 */
function hasRole(string $role): bool {
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === $role;
}
