<div class="container my-4">

    <!-- 欢迎区域 -->
    <div class="bg-primary text-white rounded-3 p-4 p-md-5 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-2">
                    <i class="bi bi-lightning-charge-fill"></i> 欢迎来到<?= e(siteName()) ?>
                </h2>
                <p class="mb-0 fs-5">
                    平台共有 <strong><?= (int)$totalQuestions ?></strong> 道精选题目，涵盖多个知识领域，助你高效备战！
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?= url('questions', ['action' => 'list']) ?>" class="btn btn-light btn-lg">
                    <i class="bi bi-play-circle"></i> 开始刷题
                </a>
            </div>
        </div>
    </div>

    <!-- 已登录用户：个人答题统计 -->
    <?php if (isLoggedIn() && $userStats): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-list-check text-primary fs-3"></i>
                    <h3 class="mt-2 mb-0"><?= (int)$userStats['total'] ?></h3>
                    <small class="text-muted">总答题数</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-check-circle text-success fs-3"></i>
                    <h3 class="mt-2 mb-0"><?= (int)$userStats['correct'] ?></h3>
                    <small class="text-muted">答对数</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-x-circle text-danger fs-3"></i>
                    <h3 class="mt-2 mb-0"><?= (int)$userStats['wrong'] ?></h3>
                    <small class="text-muted">答错数</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-bullseye text-warning fs-3"></i>
                    <h3 class="mt-2 mb-0"><?= e($userStats['accuracy']) ?>%</h3>
                    <small class="text-muted">正确率</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 未登录用户：引导提示 -->
    <?php if (!isLoggedIn()): ?>
    <div class="card border-0 shadow-sm bg-light mb-4">
        <div class="card-body text-center py-4">
            <i class="bi bi-person-check text-primary fs-1"></i>
            <h5 class="mt-2">登录后可追踪你的答题进度</h5>
            <p class="text-muted">注册账号即可查看个人统计、收藏错题，让刷题更高效</p>
            <a href="<?= url('login') ?>" class="btn btn-primary me-2">
                <i class="bi bi-box-arrow-in-right"></i> 登录
            </a>
            <a href="<?= url('register') ?>" class="btn btn-outline-primary">
                <i class="bi bi-person-plus"></i> 注册
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- 分类列表 -->
    <h4 class="mb-3">
        <i class="bi bi-grid"></i> 题目分类
    </h4>

    <?php if (!empty($categories)): ?>
    <div class="row g-3">
        <?php foreach ($categories as $category): ?>
        <div class="col-sm-6 col-lg-4">
            <a href="<?= url('questions', ['action' => 'list', 'category_id' => $category['id']]) ?>"
               class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 category-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-primary rounded-pill me-2">
                                <i class="bi bi-folder"></i>
                            </span>
                            <h5 class="card-title mb-0 text-dark">
                                <?= e($category['name']) ?>
                            </h5>
                        </div>
                        <p class="card-text text-muted small">
                            <?= e($category['description']) ?>
                        </p>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0">
                        <span class="text-primary small">
                            进入刷题 <i class="bi bi-arrow-right"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-inbox fs-1"></i>
        <p class="mt-2">暂无分类，请稍后再来</p>
    </div>
    <?php endif; ?>

</div>
