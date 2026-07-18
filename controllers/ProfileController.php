<?php
/**
 * 个人中心控制器
 * 处理个人资料修改（邮箱、密码）
 */
require_once __DIR__ . '/../models/User.php';

class ProfileController {
    private UserModel $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    /**
     * 个人中心页面
     * GET  - 渲染个人中心表单
     * POST - 处理邮箱或密码修改
     */
    public function index(): void {
        requireLogin();

        $user = currentUser();
        $dbUser = $this->userModel->findById($user['id']);

        if (isPost()) {
            if (!verifyCsrfToken()) {
                setFlash('danger', '安全验证失败，请重试');
                redirect(url('profile'));
                return;
            }

            $action = postParam('profile_action', '');

            if ($action === 'update_email') {
                $newEmail = trim(postParam('email', ''));
                $password = postParam('password', '');

                // 验证邮箱格式
                if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    setFlash('warning', '请输入有效的邮箱地址');
                    redirect(url('profile'));
                    return;
                }

                // 验证当前密码
                $verifyUser = $this->userModel->login($user['username'], $password);
                if (!$verifyUser) {
                    setFlash('danger', '密码验证失败');
                    redirect(url('profile'));
                    return;
                }

                // 更新邮箱
                if ($newEmail === $dbUser['email']) {
                    setFlash('info', '邮箱未发生变化');
                } elseif ($this->userModel->updateEmail($user['id'], $newEmail)) {
                    // 更新 Session 中的邮箱
                    $_SESSION['user']['email'] = $newEmail;
                    refreshCsrfToken();
                    setFlash('success', '邮箱已更新');
                } else {
                    setFlash('danger', '该邮箱已被其他用户使用');
                }
            } elseif ($action === 'update_password') {
                $currentPassword = postParam('current_password', '');
                $newPassword = postParam('new_password', '');
                $confirmPassword = postParam('confirm_password', '');

                // 验证当前密码
                if (!$this->userModel->login($user['username'], $currentPassword)) {
                    setFlash('danger', '当前密码不正确');
                    redirect(url('profile'));
                    return;
                }

                // 验证新密码
                if (mb_strlen($newPassword) < 6) {
                    setFlash('warning', '新密码至少 6 位');
                    redirect(url('profile'));
                    return;
                }

                if ($newPassword !== $confirmPassword) {
                    setFlash('warning', '两次输入的新密码不一致');
                    redirect(url('profile'));
                    return;
                }

                // 更新密码
                if ($this->userModel->updatePassword($user['id'], $newPassword)) {
                    // 安全措施：密码修改后清除所有「记住我」令牌，强制所有设备重新登录
                    $this->userModel->deleteAllRememberTokens($user['id']);
                    refreshCsrfToken();
                    setFlash('success', '密码已更新，请使用新密码登录');
                } else {
                    setFlash('danger', '密码更新失败，请重试');
                }
            } else {
                setFlash('warning', '无效的操作');
            }

            redirect(url('profile'));
        } else {
            // GET：渲染个人中心页面
            require_once __DIR__ . '/../views/layouts/header.php';
            require_once __DIR__ . '/../views/profile.php';
            require_once __DIR__ . '/../views/layouts/footer.php';
        }
    }
}
