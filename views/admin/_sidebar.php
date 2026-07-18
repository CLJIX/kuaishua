<?php
/**
 * 管理后台侧边栏（公共组件）
 * 所有管理页面共用此侧边栏导航
 * 使用 $currentPage 变量高亮当前页面
 */

// 获取当前 action 参数用于高亮导航项
$currentAction = getParam('action', '');
?>

<div class="admin-sidebar bg-light border-end p-3">
    <h5 class="mb-3">
        <i class="bi bi-speedometer2"></i> 管理后台
    </h5>
    <nav class="nav flex-column">
        <!-- 数据概览 -->
        <a class="nav-link <?= $currentAction === '' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin') ?>">
            <i class="bi bi-bar-chart-line"></i> 数据概览
        </a>
        <!-- 题目管理 -->
        <a class="nav-link <?= $currentAction === 'questions' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin', ['action' => 'questions']) ?>">
            <i class="bi bi-list-task"></i> 题目管理
        </a>
        <!-- 添加题目 -->
        <a class="nav-link <?= $currentAction === 'question_edit' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin', ['action' => 'question_edit']) ?>">
            <i class="bi bi-plus-circle"></i> 添加题目
        </a>
        <!-- CSV 导入 -->
        <a class="nav-link <?= $currentAction === 'import' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin', ['action' => 'import']) ?>">
            <i class="bi bi-upload"></i> CSV 导入
        </a>
        <!-- 分类管理 -->
        <a class="nav-link <?= $currentAction === 'categories' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin', ['action' => 'categories']) ?>">
            <i class="bi bi-folder2-open"></i> 分类管理
        </a>
        <!-- 标签管理 -->
        <a class="nav-link <?= $currentAction === 'tags' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin', ['action' => 'tags']) ?>">
            <i class="bi bi-tags"></i> 标签管理
        </a>
        <!-- 用户管理 -->
        <a class="nav-link <?= $currentAction === 'users' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin', ['action' => 'users']) ?>">
            <i class="bi bi-people"></i> 用户管理
        </a>
        <!-- 媒体库 -->
        <a class="nav-link <?= $currentAction === 'media' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin', ['action' => 'media']) ?>">
            <i class="bi bi-images"></i> 媒体库
        </a>
        <!-- 配置管理 -->
        <a class="nav-link <?= $currentAction === 'settings' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin', ['action' => 'settings']) ?>">
            <i class="bi bi-gear"></i> 配置管理
        </a>
        <!-- OSS 配置 -->
        <a class="nav-link <?= $currentAction === 'oss_settings' ? 'active fw-bold' : '' ?>"
           href="<?= url('admin', ['action' => 'oss_settings']) ?>">
            <i class="bi bi-cloud"></i> OSS 配置
        </a>
    </nav>
</div>
