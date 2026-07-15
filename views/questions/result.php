<div class="container my-4">

    <!-- 面包屑导航 -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('home') ?>" class="text-decoration-none">首页</a></li>
            <li class="breadcrumb-item"><a href="<?= url('questions', ['action' => 'list']) ?>" class="text-decoration-none">题库</a></li>
            <li class="breadcrumb-item active" aria-current="page">答题结果</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- 结果大卡片 -->
            <div class="card border-0 shadow-sm mb-4 <?= $isCorrect ? 'result-correct' : ($isPartial ? 'result-partial' : 'result-wrong') ?>">
                <div class="card-body text-center py-5">
                    <?php if ($isCorrect): ?>
                        <!-- 答对 -->
                        <div class="result-icon mb-3">
                            <i class="bi bi-check-circle-fill text-white" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="text-white mb-1">回答正确！</h2>
                        <p class="text-white-50 mb-0">太棒了，继续保持！</p>
                    <?php elseif ($isPartial): ?>
                        <!-- 半对 -->
                        <div class="result-icon mb-3">
                            <i class="bi bi-dash-circle-fill text-white" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="text-white mb-1">部分正确</h2>
                        <p class="text-white-50 mb-0">选对了部分选项，但还没有选全哦。</p>
                    <?php else: ?>
                        <!-- 答错 -->
                        <div class="result-icon mb-3">
                            <i class="bi bi-x-circle-fill text-white" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="text-white mb-1">回答错误</h2>
                        <p class="text-white-50 mb-0">别灰心，看看解析找出原因吧。</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 答案对比卡片 -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-bar-chart"></i> 答题详情</h5>
                    <div class="row g-3">
                        <!-- 你的答案 -->
                        <div class="col-md-4">
                            <div class="text-center p-3 rounded bg-light">
                                <small class="text-muted d-block mb-1">你的答案</small>
                                <strong class="fs-4 <?= $isCorrect ? 'text-success' : ($isPartial ? 'text-warning' : 'text-danger') ?>">
                                    <?= e($userAnswer) ?: '未作答' ?>
                                </strong>
                            </div>
                        </div>
                        <!-- 正确答案 -->
                        <div class="col-md-4">
                            <div class="text-center p-3 rounded bg-light">
                                <small class="text-muted d-block mb-1">正确答案</small>
                                <strong class="fs-4 text-success"><?= e($correctAnswer) ?></strong>
                            </div>
                        </div>
                        <!-- 用时 -->
                        <div class="col-md-4">
                            <div class="text-center p-3 rounded bg-light">
                                <small class="text-muted d-block mb-1">用时</small>
                                <strong class="fs-4 text-primary">
                                    <?= sprintf('%02d:%02d', intdiv((int)$timeSpent, 60), (int)$timeSpent % 60) ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 漏选提示（仅半对时显示） -->
            <?php if ($isPartial): ?>
            <div class="card border-0 shadow-sm mb-4 result-partial-hint">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-info-circle"></i> 漏选提示</h5>
                    <?php
                    $userAnswers = explode(',', $userAnswer);
                    $missedOptions = [];
                    foreach ($question['options'] as $opt) {
                        if ($opt['is_correct'] && !in_array($opt['option_label'], $userAnswers)) {
                            $missedOptions[] = $opt;
                        }
                    }
                    ?>
                    <p class="mb-2">你选对了部分答案，但遗漏了以下正确选项：</p>
                    <div class="row g-2">
                        <?php foreach ($missedOptions as $opt): ?>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 rounded border missed-option">
                                <span class="option-label me-3"><?= e($opt['option_label']) ?></span>
                                <span class="flex-fill"><?= e($opt['option_text']) ?></span>
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 各选项状态卡片 -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-list-check"></i> 选项详情</h5>
                    <?php foreach ($question['options'] as $option): ?>
                        <?php
                        // 判断该选项是否为正确答案之一
                        $isCorrectOption = $option['is_correct'];
                        // 判断用户是否选择了该选项
                        $userSelected = in_array($option['option_label'], explode(',', $userAnswer));
                        // 确定选项样式
                        $optionClass = '';
                        if ($isCorrectOption) {
                            $optionClass = 'correct';
                        } elseif ($userSelected && !$isCorrectOption) {
                            $optionClass = 'wrong';
                        }
                        ?>
                        <div class="option-result-item <?= $optionClass ?> d-flex align-items-center p-3 mb-2 rounded border">
                            <!-- 选项标签 -->
                            <span class="option-label me-3"><?= e($option['option_label']) ?></span>
                            <!-- 选项文字 -->
                            <span class="flex-fill"><?= e($option['option_text']) ?></span>
                            <!-- 状态图标 -->
                            <?php if ($isCorrectOption): ?>
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <small class="text-success ms-1">正确</small>
                            <?php elseif ($userSelected): ?>
                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                <small class="text-danger ms-1">你选的</small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 解析区域 -->
            <?php if (!empty($question['explanation'])): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-lightbulb text-warning"></i> 题目解析
                        </h5>
                        <div class="lh-lg">
                            <?= purify($question['explanation']) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 操作按钮 -->
            <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                <a href="<?= url('questions', ['action' => 'list']) ?>" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-right-circle"></i> 继续刷题
                </a>
                <a href="<?= url('questions', ['action' => 'detail', 'id' => $question['id']]) ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-repeat"></i> 再练一题
                </a>
            </div>

        </div>
    </div>

</div>
