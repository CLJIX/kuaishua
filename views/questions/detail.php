<div class="container my-4">

    <!-- 面包屑导航 -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('home') ?>" class="text-decoration-none">首页</a></li>
            <li class="breadcrumb-item"><a href="<?= url('questions', ['action' => 'list']) ?>" class="text-decoration-none">题库</a></li>
            <li class="breadcrumb-item active" aria-current="page">题目详情</li>
        </ol>
    </nav>

    <div class="row">
        <!-- 主内容区域 -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <!-- 题目信息区 -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <!-- 题目类型标签 -->
                            <?php if ($question['question_type'] === 'single'): ?>
                                <span class="badge bg-info fs-6">单选题</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark fs-6">多选题</span>
                            <?php endif; ?>

                            <!-- 难度标签 -->
                            <span class="badge badge-difficulty-<?= (int)$question['difficulty'] ?> fs-6">
                                <?php
                                $diffMap = [1 => '简单', 2 => '中等', 3 => '困难'];
                                echo $diffMap[$question['difficulty']] ?? '未知';
                                ?>
                            </span>

                            <!-- 所属分类 -->
                            <?php if (!empty($question['category_name'])): ?>
                            <span class="badge bg-light text-dark border fs-6">
                                <i class="bi bi-folder"></i> <?= e($question['category_name']) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- 计时器（右上角） -->
                        <div id="timer" class="badge bg-dark fs-6 py-2 px-3">
                            <i class="bi bi-clock"></i> 00:00
                        </div>
                    </div>

                    <!-- 标签列表 -->
                    <?php if (!empty($question['tags'])): ?>
                        <div class="mb-3">
                            <?php foreach ($question['tags'] as $tag): ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary me-1">
                                    <i class="bi bi-tag"></i> <?= e($tag['name']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 题面（支持 Markdown + HTML） -->
                    <div id="question-content" class="question-content md-content fs-5 mb-4 lh-lg"></div>

                    <!--
                        答题表单（id="question-form" 同时作为选项容器和提交表单）
                        - JS initOptionSelection() 通过 #question-form 查找 .option-item
                        - JS initTimer() 通过 #question-form 监听 submit 事件
                    -->
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="<?= url('practice', ['action' => 'submit']) ?>"
                              id="question-form" data-type="<?= e($question['question_type']) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="question_id" value="<?= (int)$question['id'] ?>">
                            <input type="hidden" name="answer" id="answer-input" value="">
                            <input type="hidden" name="time_spent" id="time-spent-input" value="">

                            <!-- 选项区域（交互由 JS initOptionSelection() 驱动） -->
                            <?php foreach ($question['options'] as $option): ?>
                                <div class="option-item" data-label="<?= e($option['option_label']) ?>">
                                    <span class="option-label"><?= e($option['option_label']) ?></span>
                                    <span class="option-text"><?= e($option['option_text']) ?></span>
                                </div>
                            <?php endforeach; ?>

                            <!-- 已答过提示 -->
                            <?php if ($hasAnswered): ?>
                                <div class="alert alert-info d-flex align-items-center mt-3 mb-3">
                                    <i class="bi bi-info-circle me-2 fs-5"></i>
                                    <span>您已答过此题，可以查看答案解析。</span>
                                </div>
                            <?php endif; ?>

                            <!-- 提交按钮 -->
                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                                <i class="bi bi-check2-circle"></i> 提交答案
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- 未登录时：只显示选项，不显示提交按钮 -->
                        <div id="question-form" data-type="<?= e($question['question_type']) ?>">
                            <?php foreach ($question['options'] as $option): ?>
                                <div class="option-item" data-label="<?= e($option['option_label']) ?>">
                                    <span class="option-label"><?= e($option['option_label']) ?></span>
                                    <span class="option-text"><?= e($option['option_text']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- 未登录提示 -->
                        <div class="alert alert-warning d-flex align-items-center mt-3">
                            <i class="bi bi-exclamation-triangle me-2 fs-5"></i>
                            <span>请先<a href="<?= url('login') ?>" class="alert-link">登录</a>后再作答。</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 解析区域（默认折叠，已答过或管理员可见） -->
            <?php if ($hasAnswered || isAdmin()): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 pt-3">
                        <button class="btn btn-link text-decoration-none text-dark d-flex align-items-center w-100"
                                type="button" data-bs-toggle="collapse" data-bs-target="#explanation-panel">
                            <i class="bi bi-lightbulb me-2 text-warning"></i>
                            <strong>题目解析</strong>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </button>
                    </div>
                    <div class="collapse" id="explanation-panel">
                        <div class="card-body pt-0">
                            <?php if (!empty($question['explanation'])): ?>
                                <div id="question-explanation" class="lh-lg md-content"></div>
                            <?php else: ?>
                                <p class="text-muted mb-0">暂无解析</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- 侧边栏 -->
        <div class="col-lg-4">
            <!-- 题目信息卡片 -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3"><i class="bi bi-info-circle"></i> 题目信息</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">题号</span>
                            <strong>#<?= (int)$question['id'] ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">类型</span>
                            <strong><?= $question['question_type'] === 'single' ? '单选题' : '多选题' ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">难度</span>
                            <strong>
                                <?php
                                $diffMap = [1 => '简单', 2 => '中等', 3 => '困难'];
                                echo $diffMap[$question['difficulty']] ?? '未知';
                                ?>
                            </strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">分类</span>
                            <strong><?= e($question['category_name'] ?? '未分类') ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span class="text-muted">选项数</span>
                            <strong><?= count($question['options']) ?> 个</strong>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- 快捷操作 -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3"><i class="bi bi-lightning"></i> 快捷操作</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= url('questions', ['action' => 'list']) ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-list"></i> 返回题目列表
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Markdown + LaTeX 渲染脚本 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var contentEl = document.getElementById('question-content');
    if (contentEl) {
        contentEl.innerHTML = DOMPurify.sanitize(marked.parse(<?= json_encode($question['content'], JSON_UNESCAPED_UNICODE) ?>));
    }
    var explEl = document.getElementById('question-explanation');
    if (explEl) {
        explEl.innerHTML = DOMPurify.sanitize(marked.parse(<?= json_encode($question['explanation'] ?? '', JSON_UNESCAPED_UNICODE) ?>));
    }
    // 渲染 LaTeX 公式
    if (typeof renderMathInElement !== 'undefined') {
        var opts = {
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '$', right: '$', display: false}
            ],
            throwOnError: false
        };
        if (contentEl) renderMathInElement(contentEl, opts);
        if (explEl) renderMathInElement(explEl, opts);
    }
});
</script>
