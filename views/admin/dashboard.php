<?php
/**
 * 管理后台首页 - 数据概览
 * 展示题目、分类、标签、用户的统计数据
 *
 * @var int   $totalQuestions  题目总数
 * @var array $categories      所有分类
 * @var array $tags            所有标签
 * @var array $users           所有用户
 * @var int   $categoryCount   分类数量
 * @var int   $tagCount        标签数量
 * @var int   $userCount       用户数量
 */
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- 左侧管理侧边栏 -->
        <div class="col-md-3 mb-3">
            <?php include __DIR__ . '/_sidebar.php'; ?>
        </div>

        <!-- 右侧内容区 -->
        <div class="col-md-9">
            <?php include __DIR__ . '/_flash.php'; ?>
            <h3 class="mb-4">
                <i class="bi bi-bar-chart-line"></i> 数据概览
            </h3>

            <!-- 统计卡片网格 -->
            <div class="row g-3 mb-4">
                <!-- 题目总数 -->
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-file-earmark-text text-primary fs-2"></i>
                            <h2 class="mt-2 mb-1"><?= (int)$totalQuestions ?></h2>
                            <span class="text-muted">题目总数</span>
                        </div>
                    </div>
                </div>
                <!-- 分类数 -->
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-folder2-open text-success fs-2"></i>
                            <h2 class="mt-2 mb-1"><?= (int)$categoryCount ?></h2>
                            <span class="text-muted">分类数</span>
                        </div>
                    </div>
                </div>
                <!-- 标签数 -->
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-tags text-info fs-2"></i>
                            <h2 class="mt-2 mb-1"><?= (int)$tagCount ?></h2>
                            <span class="text-muted">标签数</span>
                        </div>
                    </div>
                </div>
                <!-- 用户数 -->
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-people text-warning fs-2"></i>
                            <h2 class="mt-2 mb-1"><?= (int)$userCount ?></h2>
                            <span class="text-muted">用户数</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 快捷操作 -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> 快捷操作</h5>
                </div>
                <div class="card-body">
                    <a href="<?= url('admin', ['action' => 'question_edit']) ?>" class="btn btn-primary me-2 mb-2">
                        <i class="bi bi-plus-circle"></i> 添加题目
                    </a>
                    <a href="<?= url('admin', ['action' => 'import']) ?>" class="btn btn-outline-primary me-2 mb-2">
                        <i class="bi bi-upload"></i> CSV 导入
                    </a>
                    <a href="<?= url('admin', ['action' => 'categories']) ?>" class="btn btn-outline-success me-2 mb-2">
                        <i class="bi bi-folder2-open"></i> 管理分类
                    </a>
                    <a href="<?= url('admin', ['action' => 'tags']) ?>" class="btn btn-outline-info me-2 mb-2">
                        <i class="bi bi-tags"></i> 管理标签
                    </a>
                    <a href="<?= url('admin', ['action' => 'questions']) ?>" class="btn btn-outline-secondary mb-2">
                        <i class="bi bi-list-task"></i> 查看题目列表
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
