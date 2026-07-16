<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小题快刷 - 在线刷题平台</title>
    <!-- Bootstrap 5 CSS（本地托管） -->
    <link href="assets/css/vendor/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons（本地托管） -->
    <link href="assets/css/vendor/bootstrap-icons.css" rel="stylesheet">
    <!-- Markdown 渲染：marked.js + DOMPurify（本地托管，国内加载稳定） -->
    <script src="assets/js/vendor/marked.min.js"></script>
    <script src="assets/js/vendor/purify.min.js"></script>
    <!-- LaTeX 公式渲染：KaTeX（本地托管） -->
    <link rel="stylesheet" href="assets/vendor/katex/katex.min.css">
    <script src="assets/vendor/katex/katex.min.js"></script>
    <script src="assets/vendor/katex/auto-render.min.js"></script>
    <!-- 自定义样式 -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?= url('home') ?>">
            <i class="bi bi-lightning-charge-fill"></i> 小题快刷
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= url('home') ?>">首页</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('questions', ['action' => 'list']) ?>">题库</a></li>
                <?php if (isAdmin()): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('admin') ?>">管理后台</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('profile') ?>">
                        <i class="bi bi-person-circle"></i> <?= e(currentUser()['username']) ?>
                    </a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('logout') ?>">登出</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('login') ?>">登录</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light btn-sm ms-2" href="<?= url('register') ?>">注册</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash 消息（非管理后台页面在顶部显示，管理后台页面在右侧内容区显示） -->
<?php
$flash = getFlash();
$_hasFlash = $flash !== null;
$_isAdminPage = (getParam('page', '') === 'admin');
if ($_hasFlash && !$_isAdminPage):
?>
<div class="container mt-3">
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
