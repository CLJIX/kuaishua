/**
 * 小题快刷 - 前端交互脚本
 */

document.addEventListener('DOMContentLoaded', function() {

    // ===== 自动隐藏 Flash 消息（3秒后淡出）=====
    var alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 3000);
    });

    // ===== 选项选择交互 =====
    initOptionSelection();

    // ===== 刷题计时器 =====
    initTimer();

    // ===== 表单验证增强 =====
    initFormValidation();

    // ===== 删除确认 =====
    initDeleteConfirmation();

    // ===== 管理后台动态选项 =====
    initDynamicOptions();

    // ===== 列表页 Markdown 预览渲染 =====
    initListPreview();
});

/**
 * 选项选择交互
 * - 单选题：点击选择一个选项，取消其他选择
 * - 多选题：点击切换选择状态，可同时选多个
 * - 点击选项区域（包括文字）都能触发选择
 * - 将选中的值同步到隐藏的 input 字段
 */
function initOptionSelection() {
    const questionForm = document.getElementById('question-form');
    if (!questionForm) return;

    const questionType = questionForm.dataset.type; // 'single', 'multiple', 'judge', 'fill'

    // 填空题：跳过选项绑定，监听输入框同步
    if (questionType === 'fill') {
        const fillInput = document.getElementById('fill-answer-input');
        const answerInput = document.getElementById('answer-input');
        if (fillInput && answerInput) {
            // 实时同步
            fillInput.addEventListener('input', function() {
                answerInput.value = this.value;
            });
            // 提交前兜底同步（防止 input 事件未触发）
            questionForm.addEventListener('submit', function() {
                answerInput.value = fillInput.value;
            });
        }
        return;
    }

    const options = questionForm.querySelectorAll('.option-item');

    options.forEach(function(option) {
        option.addEventListener('click', function() {
            const label = this.dataset.label; // A, B, C, D

            if (questionType === 'single' || questionType === 'judge') {
                // 单选/判断：清除其他选择
                options.forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                // 设置隐藏字段值
                const answerInput = document.getElementById('answer-input');
                if (answerInput) answerInput.value = label;
            } else {
                // 多选：切换选择状态
                this.classList.toggle('selected');
                // 收集所有选中的选项
                const selected = [];
                options.forEach(o => {
                    if (o.classList.contains('selected')) {
                        selected.push(o.dataset.label);
                    }
                });
                selected.sort();
                const answerInput = document.getElementById('answer-input');
                if (answerInput) answerInput.value = selected.join(',');
            }
        });
    });
}

/**
 * 刷题计时器
 * 从页面加载开始计时，提交表单时自动填入用时
 */
function initTimer() {
    const timerEl = document.getElementById('timer');
    const timeSpentInput = document.getElementById('time-spent-input');
    if (!timerEl) return;

    let seconds = 0;
    const interval = setInterval(function() {
        seconds++;
        const min = Math.floor(seconds / 60).toString().padStart(2, '0');
        const sec = (seconds % 60).toString().padStart(2, '0');
        timerEl.textContent = min + ':' + sec;
    }, 1000);

    // 表单提交时停止计时并记录用时
    const form = document.getElementById('question-form');
    if (form && timeSpentInput) {
        form.addEventListener('submit', function() {
            clearInterval(interval);
            timeSpentInput.value = seconds;
        });
    }
}

/**
 * Bootstrap 表单验证增强
 * 给 .needs-validation 的表单添加 Bootstrap 验证样式
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * 删除操作确认弹窗
 * 给 .btn-delete 按钮添加确认对话框
 */
function initDeleteConfirmation() {
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(event) {
            if (!confirm('确定要删除吗？此操作不可撤销。')) {
                event.preventDefault();
            }
        });
    });
}

/**
 * 管理后台 - 动态选项管理
 * 允许管理员在题目编辑表单中动态添加/删除选项
 */
function initDynamicOptions() {
    const addOptionBtn = document.getElementById('add-option-btn');
    if (!addOptionBtn) return;

    const optionsContainer = document.getElementById('options-container');
    const optionLabels = ['A', 'B', 'C', 'D', 'E', 'F'];

    addOptionBtn.addEventListener('click', function() {
        const currentOptions = optionsContainer.querySelectorAll('.option-row');
        const nextIndex = currentOptions.length;

        if (nextIndex >= optionLabels.length) {
            alert('最多支持 ' + optionLabels.length + ' 个选项');
            return;
        }

        const label = optionLabels[nextIndex];
        const row = document.createElement('div');
        row.className = 'option-row mb-2 d-flex align-items-center gap-2';
        row.innerHTML = '<span class="badge bg-primary fs-6">' + label + '</span>' +
            '<input type="text" class="form-control" name="option_' + label + '" placeholder="选项 ' + label + ' 内容">' +
            '<button type="button" class="btn btn-outline-danger btn-sm remove-option-btn">&times;</button>';

        optionsContainer.appendChild(row);

        // 绑定删除事件
        row.querySelector('.remove-option-btn').addEventListener('click', function() {
            row.remove();
        });
    });

    // 已有选项的删除按钮
    document.querySelectorAll('.remove-option-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.option-row').remove();
        });
    });
}

/**
 * 列表页 Markdown 预览渲染
 * 查找所有 .md-excerpt-preview[data-raw] 元素，使用 marked.js + DOMPurify 渲染
 * 同时渲染 KaTeX 公式（如果可用）
 */
function initListPreview() {
    var previews = document.querySelectorAll('.md-excerpt-preview[data-raw]');
    if (!previews.length) return;

    var katexOpts = {
        delimiters: [
            {left: '$$', right: '$$', display: true},
            {left: '$', right: '$', display: false}
        ],
        throwOnError: false
    };

    previews.forEach(function(el) {
        var raw = el.getAttribute('data-raw');
        if (!raw) return;
        try {
            el.innerHTML = DOMPurify.sanitize(marked.parse(raw));
            if (typeof renderMathInElement !== 'undefined') {
                renderMathInElement(el, katexOpts);
            }
        } catch (err) {
            // 渲染失败时保留纯文本
        }
    });
}
