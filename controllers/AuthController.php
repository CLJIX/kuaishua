<?php
/**
 * 认证控制器
 * 处理用户登录、注册、登出
 */
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private UserModel $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    /**
     * 登录页
     * GET  - 渲染登录表单
     * POST - 验证凭据并建立会话
     */
    public function login(): void {
        // 已登录用户直接跳转首页
        if (isLoggedIn()) {
            redirect(url('home'));
            return;
        }

        if (isPost()) {
            // CSRF 安全验证
            if (!verifyCsrfToken()) {
                setFlash('danger', '安全验证失败，请重试');
                redirect(url('login'));
                return;
            }

            $username = trim(postParam('username', ''));
            $password = postParam('password', '');

            // 输入非空校验
            if (empty($username) || empty($password)) {
                setFlash('warning', '请输入用户名和密码');
                redirect(url('login'));
                return;
            }

            // 调用模型验证凭据
            $user = $this->userModel->login($username, $password);
            if ($user) {
                // 检查是否勾选了「记住我」
                $remember = (bool) postParam('remember', false);
                loginUser($user, $remember);
                setFlash('success', '欢迎回来，' . e($user['username']) . '！');
                redirect(url('home'));
            } else {
                setFlash('danger', '用户名或密码错误');
                redirect(url('login'));
            }
        } else {
            // 渲染登录页面
            require_once __DIR__ . '/../views/layouts/header.php';
            require_once __DIR__ . '/../views/auth/login.php';
            require_once __DIR__ . '/../views/layouts/footer.php';
        }
    }

    /**
     * 注册页
     * GET  - 渲染注册表单
     * POST - 验证输入并创建账号，成功后自动登录
     */
    public function register(): void {
        // 已登录用户直接跳转首页
        if (isLoggedIn()) {
            redirect(url('home'));
            return;
        }

        if (isPost()) {
            // CSRF 安全验证
            if (!verifyCsrfToken()) {
                setFlash('danger', '安全验证失败，请重试');
                redirect(url('register'));
                return;
            }

            $username = trim(postParam('username', ''));
            $email    = trim(postParam('email', ''));
            $password = postParam('password', '');
            $confirm  = postParam('password_confirm', '');

            // ---- 表单验证 ----
            $errors = [];

            // 用户名：3-50 字符
            if (empty($username)) {
                $errors[] = '请输入用户名';
            } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
                $errors[] = '用户名长度需在 3-50 个字符之间';
            }

            // 邮箱格式
            if (empty($email)) {
                $errors[] = '请输入邮箱地址';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = '邮箱格式不正确';
            }

            // 密码：至少 6 位
            if (empty($password)) {
                $errors[] = '请输入密码';
            } elseif (mb_strlen($password) < 6) {
                $errors[] = '密码长度不能少于 6 位';
            }

            // 确认密码
            if ($password !== $confirm) {
                $errors[] = '两次输入的密码不一致';
            }

            // 用户名唯一性
            if (empty($errors) && $this->userModel->findByUsername($username)) {
                $errors[] = '用户名已被注册';
            }

            // 邮箱唯一性
            if (empty($errors) && $this->userModel->findByEmail($email)) {
                $errors[] = '该邮箱已被注册';
            }

            // 验证不通过，带回错误信息
            if (!empty($errors)) {
                setFlash('danger', implode('<br>', $errors));
                redirect(url('register'));
                return;
            }

            // 调用模型创建账号
            $userId = $this->userModel->register($username, $email, $password);
            if ($userId) {
                // 注册成功后自动登录
                $user = $this->userModel->findById($userId);
                if ($user) {
                    loginUser($user);
                    setFlash('success', '注册成功，欢迎加入小题快刷！');
                    redirect(url('home'));
                    return;
                }
            }

            // 注册失败（用户名/邮箱冲突等）
            setFlash('danger', '注册失败，请稍后重试');
            redirect(url('register'));
        } else {
            // 渲染注册页面
            require_once __DIR__ . '/../views/layouts/header.php';
            require_once __DIR__ . '/../views/auth/register.php';
            require_once __DIR__ . '/../views/layouts/footer.php';
        }
    }

    /**
     * 登出
     * 清除会话并重定向到首页
     */
    public function logout(): void {
        logoutUser(); // 内部已处理 Session 销毁、Flash 提示和重定向
    }
}
