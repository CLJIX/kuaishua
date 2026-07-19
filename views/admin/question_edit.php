<?php
/**
 * 管理后台 - 题目新增/编辑表单
 * 支持单选/多选/判断，动态选项管理，标签多选
 *
 * @var bool  $isEdit     是否编辑模式
 * @var array $question   编辑时的题目详情（含 options 和 tags），新增时为 null
 * @var array $categories 所有分类
 * @var array $tags       所有标签
 */

// 获取题目 ID（编辑模式）
$id = $isEdit ? (int)$question['id'] : 0;

// 获取已选标签 ID 列表
$selectedTagIds = [];
if ($isEdit && !empty($question['tags'])) {
    $selectedTagIds = array_column($question['tags'], 'id');
}

// 获取现有选项（编辑模式）或默认 4 个空选项（新增模式）
$existingOptions = [];
if ($isEdit && !empty($question['options'])) {
    foreach ($question['options'] as $opt) {
        $existingOptions[$opt['option_label']] = $opt['option_text'];
    }
}

// 从选项中计算正确答案（如 "A" 或 "A,C"）
$correctAnswer = '';
if ($isEdit && !empty($question['options'])) {
    $correctLabels = [];
    foreach ($question['options'] as $opt) {
        if (!empty($opt['is_correct'])) {
            $correctLabels[] = $opt['option_label'];
        }
    }
    $correctAnswer = implode(',', $correctLabels);
}

// 默认选项标签
$defaultLabels = ['A', 'B', 'C', 'D', 'E', 'F'];
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
                <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?>"></i>
                <?= $isEdit ? '编辑题目 #' . $id : '添加题目' ?>
            </h3>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST"
                          action="<?= url('admin', ['action' => 'question_edit', 'id' => $id]) ?>">
                        <?= csrfField() ?>

                        <!-- 题目类型 -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">题目类型</label>
                            <select name="question_type" id="question-type" class="form-select" style="max-width:200px">
                                <option value="single"
                                    <?= ($isEdit && ($question['question_type'] ?? '') === 'single') ? 'selected' : (!$isEdit ? 'selected' : '') ?>>
                                    单选题
                                </option>
                                <option value="multiple"
                                    <?= ($isEdit && ($question['question_type'] ?? '') === 'multiple') ? 'selected' : '' ?>>
                                    多选题
                                </option>
                                <option value="judge"
                                    <?= ($isEdit && ($question['question_type'] ?? '') === 'judge') ? 'selected' : '' ?>>
                                    判断题
                                </option>
                                <option value="fill"
                                    <?= ($isEdit && ($question['question_type'] ?? '') === 'fill') ? 'selected' : '' ?>>
                                    填空题
                                </option>
                            </select>
                        </div>

                        <!-- 题面 -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">题面</label>
                            <div id="editor-content" class="md-editor-wrap" style="height:405px">
                                <div class="md-toolbar"></div>
                                <div class="md-body">
                                    <textarea name="content" required placeholder="输入题目内容（支持 Markdown 格式）"><?= e($isEdit ? ($question['content'] ?? '') : '') ?></textarea>
                                    <div class="md-preview md-content"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 难度 -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">难度</label>
                            <select name="difficulty" class="form-select" style="max-width:200px">
                                <option value="1" <?= ($isEdit && (int)($question['difficulty'] ?? 1) === 1) ? 'selected' : (!$isEdit ? 'selected' : '') ?>>
                                    1 - 简单
                                </option>
                                <option value="2" <?= ($isEdit && (int)($question['difficulty'] ?? 1) === 2) ? 'selected' : '' ?>>
                                    2 - 中等
                                </option>
                                <option value="3" <?= ($isEdit && (int)($question['difficulty'] ?? 1) === 3) ? 'selected' : '' ?>>
                                    3 - 困难
                                </option>
                            </select>
                        </div>

                        <!-- 所属分类 -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">所属分类</label>
                            <select name="category_id" class="form-select" style="max-width:300px">
                                <option value="">-- 不选择 --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"
                                    <?= ($isEdit && (int)($question['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 标签（checkbox 多选） -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">标签</label>
                            <div class="row g-2">
                                <?php foreach ($tags as $tag): ?>
                                <div class="col-auto">
                                    <div class="form-check">
                                        <input class="form-check-input tag-checkbox" type="checkbox"
                                               value="<?= (int)$tag['id'] ?>"
                                               id="tag_<?= (int)$tag['id'] ?>"
                                               <?= in_array((int)$tag['id'], $selectedTagIds) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="tag_<?= (int)$tag['id'] ?>">
                                            <?= e($tag['name']) ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- 隐藏字段：逗号分隔的标签 ID，由 JS 同步更新 -->
                            <input type="hidden" name="tags" id="tags-hidden" value="<?= e(implode(',', $selectedTagIds)) ?>">
                            <?php if (empty($tags)): ?>
                            <div class="form-text text-muted">暂无标签，可先到<a href="<?= url('admin', ['action' => 'tags']) ?>">标签管理</a>创建</div>
                            <?php endif; ?>
                        </div>

                        <!-- 选项区域（选择题专用，共用工具栏 + 焦点跟踪预览） -->
                        <div class="mb-3" id="options-section">
                            <label class="form-label fw-bold">选项</label>
                            <div id="options-editor" class="md-options-editor">
                                <div class="md-toolbar"></div>
                                <div class="md-options-body">
                                    <div id="options-container" class="md-options-list">
                                        <?php
                                        // 编辑模式：加载现有选项；新增模式：默认显示 A/B/C/D
                                        $optionLabels = $isEdit ? array_keys($existingOptions) : ['A', 'B', 'C', 'D'];
                                        // 确保至少包含已有选项
                                        if ($isEdit && empty($optionLabels)) {
                                            $optionLabels = ['A', 'B', 'C', 'D'];
                                        }
                                        foreach ($optionLabels as $label):
                                        ?>
                                        <div class="option-row d-flex align-items-start mb-2">
                                            <span class="badge bg-primary me-2 mt-1" style="min-width:30px"><?= e($label) ?></span>
                                            <textarea name="option_<?= e($label) ?>"
                                                      class="form-control"
                                                      rows="3"
                                                      placeholder="选项 <?= e($label) ?> 的内容（支持 Markdown 格式）"><?= e($isEdit ? ($existingOptions[$label] ?? '') : '') ?></textarea>
                                            <button type="button" class="remove-option-btn btn btn-sm btn-outline-danger ms-2"
                                                    title="删除选项">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="md-options-preview md-content"></div>
                                </div>
                                <!-- 添加选项按钮 -->
                                <div class="md-options-footer">
                                    <button type="button" id="add-option-btn" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-plus"></i> 添加选项
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 正确答案（选择题专用） -->
                        <div class="mb-3" id="correct-answer-section">
                            <label class="form-label fw-bold">正确答案</label>
                            <input type="text" name="correct_answer" class="form-control" style="max-width:200px"
                                   placeholder="如 A 或 A,C"
                                   value="<?= e($isEdit ? $correctAnswer : '') ?>">
                            <div class="form-text">
                                单选填写单个字母（如 A），多选填写逗号分隔的字母（如 A,C）
                            </div>
                        </div>

                        <!-- 标准答案（填空题专用） -->
                        <div class="mb-3" id="standard-answer-section" style="display:none">
                            <label class="form-label fw-bold">标准答案</label>
                            <textarea name="standard_answer" class="form-control" rows="2"
                                      placeholder="填空题的标准答案，多个可接受答案用 | 分隔，如：answer1|answer2"><?= e($isEdit ? ($question['standard_answer'] ?? '') : '') ?></textarea>
                            <div class="form-text">
                                支持多个可接受答案，用 <code>|</code> 分隔（不区分大小写）
                            </div>
                        </div>

                        <!-- 解析 -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">解析</label>
                            <div id="editor-explanation" class="md-editor-wrap" style="height:325px">
                                <div class="md-toolbar"></div>
                                <div class="md-body">
                                    <textarea name="explanation" placeholder="题目解析（可选，支持 Markdown 格式）"><?= e($isEdit ? ($question['explanation'] ?? '') : '') ?></textarea>
                                    <div class="md-preview md-content"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i>
                                <?= $isEdit ? '保存修改' : '创建题目' ?>
                            </button>
                            <a href="<?= url('admin', ['action' => 'questions']) ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> 返回列表
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 轻量 Markdown 编辑器（仅 6KB，复用已加载的 marked.js + DOMPurify） -->
<script src="assets/js/md-editor.js"></script>

<!-- OSS 上传组件 + 媒体库弹窗 -->
<script src="assets/js/oss-upload.js"></script>
<?php include __DIR__ . '/_media_modal.php'; ?>

<!-- 选项动态管理 & 编辑器初始化脚本 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ---- 标签 checkbox 同步到隐藏字段 ----
    var tagCheckboxes = document.querySelectorAll('.tag-checkbox');
    var tagsHidden = document.getElementById('tags-hidden');

    function syncTags() {
        var selected = [];
        tagCheckboxes.forEach(function(cb) {
            if (cb.checked) selected.push(cb.value);
        });
        tagsHidden.value = selected.join(',');
    }
    tagCheckboxes.forEach(function(cb) {
        cb.addEventListener('change', syncTags);
    });

    // ---- 题型切换与特殊模式 ----
    var typeSelect = document.getElementById('question-type');
    var container = document.getElementById('options-container');
    var addBtn = document.getElementById('add-option-btn');
    var allLabels = ['A', 'B', 'C', 'D', 'E', 'F'];
    var optionsSection = document.getElementById('options-section');
    var correctAnswerSection = document.getElementById('correct-answer-section');
    var standardAnswerSection = document.getElementById('standard-answer-section');
    var currentMode = typeSelect.value; // 'single', 'multiple', 'judge', 'fill'

    // 判断题模式：固定选项为 A=对 B=错，禁止增删
    function applyJudgeMode() {
        currentMode = 'judge';
        container.innerHTML = '';
        [['A', '对'], ['B', '错']].forEach(function(item) {
            var row = document.createElement('div');
            row.className = 'option-row d-flex align-items-start mb-2';
            row.innerHTML = '<span class="badge bg-primary me-2 mt-1" style="min-width:30px">' + item[0] + '</span>' +
                '<textarea name="option_' + item[0] + '" class="form-control" rows="3">' + item[1] + '</textarea>';
            container.appendChild(row);
        });
        addBtn.style.display = 'none';
        // 显示选择题区域，隐藏填空题区域
        optionsSection.style.display = '';
        correctAnswerSection.style.display = '';
        standardAnswerSection.style.display = 'none';
    }

    // 填空题模式：隐藏选项和正确答案，显示标准答案
    function applyFillMode() {
        currentMode = 'fill';
        optionsSection.style.display = 'none';
        correctAnswerSection.style.display = 'none';
        standardAnswerSection.style.display = '';
    }

    // 恢复普通模式（单选/多选）
    function exitSpecialMode() {
        currentMode = typeSelect.value;
        container.innerHTML = '';
        ['A', 'B', 'C', 'D'].forEach(function(label) {
            var row = document.createElement('div');
            row.className = 'option-row d-flex align-items-start mb-2';
            row.innerHTML = '<span class="badge bg-primary me-2 mt-1" style="min-width:30px">' + label + '</span>' +
                '<textarea name="option_' + label + '" class="form-control" rows="3" placeholder="选项 ' + label + ' 的内容（支持 Markdown 格式）"></textarea>' +
                '<button type="button" class="remove-option-btn btn btn-sm btn-outline-danger ms-2" title="删除选项">' +
                '<i class="bi bi-x-lg"></i></button>';
            container.appendChild(row);
        });
        addBtn.style.display = '';
        // 显示选择题区域，隐藏填空题区域
        optionsSection.style.display = '';
        correctAnswerSection.style.display = '';
        standardAnswerSection.style.display = 'none';
    }

    typeSelect.addEventListener('change', function() {
        if (this.value === 'judge') {
            applyJudgeMode();
        } else if (this.value === 'fill') {
            applyFillMode();
        } else {
            exitSpecialMode();
        }
    });

    // 初始加载时根据题型设置模式
    if (typeSelect.value === 'judge') {
        applyJudgeMode();
    } else if (typeSelect.value === 'fill') {
        applyFillMode();
    }

    // ---- 选项动态管理 ----

    // 添加选项（判断题模式下不允许）
    addBtn.addEventListener('click', function() {
        if (currentMode === 'judge') return;
        var existingLabels = [];
        container.querySelectorAll('.option-row .badge').forEach(function(badge) {
            existingLabels.push(badge.textContent.trim());
        });

        // 找到下一个可用标签
        var nextLabel = null;
        for (var i = 0; i < allLabels.length; i++) {
            if (existingLabels.indexOf(allLabels[i]) === -1) {
                nextLabel = allLabels[i];
                break;
            }
        }
        if (!nextLabel) {
            alert('最多支持 6 个选项（A-F）');
            return;
        }

        var row = document.createElement('div');
        row.className = 'option-row d-flex align-items-start mb-2';
        row.innerHTML = '<span class="badge bg-primary me-2 mt-1" style="min-width:30px">' + nextLabel + '</span>' +
            '<textarea name="option_' + nextLabel + '" class="form-control" rows="3" placeholder="选项 ' + nextLabel + ' 的内容（支持 Markdown 格式）"></textarea>' +
            '<button type="button" class="remove-option-btn btn btn-sm btn-outline-danger ms-2" title="删除选项">' +
            '<i class="bi bi-x-lg"></i></button>';
        container.appendChild(row);
    });

    // 删除选项（事件委托，判断题模式下不允许）
    container.addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-option-btn');
        if (btn) {
            if (currentMode === 'judge') return;
            var rows = container.querySelectorAll('.option-row');
            if (rows.length <= 2) {
                alert('至少需要保留 2 个选项');
                return;
            }
            btn.closest('.option-row').remove();
        }
    });

    // ---- 轻量 Markdown 编辑器初始化 ----
    mdEditorInit('editor-content');
    mdEditorInit('editor-explanation');

    // 选项编辑器初始化（共用工具栏 + 焦点跟踪预览）
    mdOptionsEditorInit('options-editor');

    // ---- 初始化 OSS 上传组件（粘贴上传 + 媒体库弹窗） ----
    if (typeof initOssUpload === 'function') {
        initOssUpload({ questionId: <?= $id ?> });
    }
});
</script>
