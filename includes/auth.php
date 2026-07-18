<?php
/**
 * 认证与权限管理
 * 处理用户登录、登出和权限验证
 *
 * 依赖：
 * - functions.php 中的 startSecureSession()、setFlash()、redirect()、url()、isLoggedIn()、isHttps()
 * - 需要在调用本文件函数前先 require functions.php 并启动 Session
 */

// 记住我 Cookie 名称
define('REMEMBER_COOKIE_NAME', 'xtks_remember');
// 记住我 Cookie 有效期：3天
define('REMEMBER_COOKIE_LIFETIME', 3 * 24 * 60 * 60);

/**
 * 用户登录
 * 验证成功后将用户信息存入 Session，并重新生成 Session ID
 * 重新生成 Session ID 可防止会话固定攻击
 * 若 $remember 为 true，则同时设置一个有效期为3天的签名 Cookie
 *
 * @param array $user    从数据库查询到的用户记录（需包含 id, username, email, role 字段）
 * @param bool  $remember 是否设置「记住我」Cookie（默认 false）
 */
function loginUser(array $user, bool $remember = false): void {
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

    // 如果勾选了「记住我」，创建并设置持久化 Cookie
    if ($remember) {
        require_once __DIR__ . '/../models/User.php';
        $userModel = new UserModel();
        $token = $userModel->createRememberToken((int) $user['id']);
        if ($token !== '') {
            setcookie(
                REMEMBER_COOKIE_NAME,
                $token,
                [
                    'expires'  => time() + REMEMBER_COOKIE_LIFETIME,
                    'path'     => '/',
                    'httponly' => true,
                    'secure'   => isHttps(),
                    'samesite' => 'Lax',
                ]
            );
            $_COOKIE[REMEMBER_COOKIE_NAME] = $token; // 当前请求可用
        }
    }
}

/**
 * 尝试从「记住我」Cookie 恢复登录状态
 * 每次页面加载时调用：若 Session 已过期但 Cookie 有效，自动恢复登录
 * 令牌验证通过后重新生成 Session ID 并刷新 Cookie 令牌（防止重放攻击）
 */
function tryRestoreFromCookie(): void {
    // 已登录则不需要恢复
    if (isLoggedIn()) {
        return;
    }

    $token = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    if (strlen($token) !== 64) {
        return;
    }

    require_once __DIR__ . '/../models/User.php';
    $userModel = new UserModel();

    // 通过令牌查找用户
    $user = $userModel->findByRememberToken($token);
    if (!$user) {
        // 令牌无效或已过期，清除 Cookie
        setcookie(REMEMBER_COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => isHttps(),
            'samesite' => 'Lax',
        ]);
        return;
    }

    // 令牌有效：删除旧令牌，重新生成 Session ID
    $userModel->deleteRememberToken($token);
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'       => $user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $user['role']
    ];

    // 生成新令牌并刷新 Cookie（令牌轮换，防止重放攻击）
    $newToken = $userModel->createRememberToken((int) $user['id']);
    if ($newToken !== '') {
        setcookie(
            REMEMBER_COOKIE_NAME,
            $newToken,
            [
                'expires'  => time() + REMEMBER_COOKIE_LIFETIME,
                'path'     => '/',
                'httponly' => true,
                'secure'   => isHttps(),
                'samesite' => 'Lax',
            ]
        );
        $_COOKIE[REMEMBER_COOKIE_NAME] = $newToken;
    }
}

/**
 * 用户登出
 * 彻底清除 Session 数据、删除 Session Cookie、销毁 Session
 * 同时清除「记住我」Cookie 并删除数据库中的令牌记录
 * 最后重新启动 Session 以便设置 Flash 消息，然后重定向到首页
 */
function logoutUser(): void {
    // 清除「记住我」Cookie 和数据库令牌
    $token = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    if (strlen($token) === 64) {
        require_once __DIR__ . '/../models/User.php';
        $userModel = new UserModel();
        $userModel->deleteRememberToken($token);
    }
    setcookie(REMEMBER_COOKIE_NAME, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'secure'   => isHttps(),
        'samesite' => 'Lax',
    ]);

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
