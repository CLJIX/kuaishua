/**
 * 轻量 Markdown 编辑器（工具栏 + 实时预览）
 * 依赖：marked.js + DOMPurify（全局已加载）
 * 用法：页面中写好 .md-editor-wrap 结构，然后调用 mdEditorInit()
 */
(function () {
    'use strict';

    /* ===== 工具栏按钮定义 ===== */
    var BUTTONS = [
        { icon: 'bi-type-bold',          tip: '粗体',     wrap: ['**', '**'] },
        { icon: 'bi-type-italic',        tip: '斜体',     wrap: ['*', '*'] },
        { icon: 'bi-type-strikethrough', tip: '删除线',   wrap: ['~~', '~~'] },
        { sep: true },
        { icon: 'bi-type-h1',            tip: '一级标题', prefix: '# ' },
        { icon: 'bi-type-h2',            tip: '二级标题', prefix: '## ' },
        { icon: 'bi-type-h3',            tip: '三级标题', prefix: '### ' },
        { sep: true },
        { icon: 'bi-list-ul',            tip: '无序列表', prefix: '- ' },
        { icon: 'bi-list-ol',            tip: '有序列表', prefix: '1. ' },
        { icon: 'bi-check-square',       tip: '任务列表', prefix: '- [ ] ' },
        { sep: true },
        { icon: 'bi-link-45deg',         tip: '链接',     insert: '[链接文字](https://)' },
        { icon: 'bi-image',              tip: '图片',     insert: '![描述](https://)' },
        { icon: 'bi-code',               tip: '行内代码', wrap: ['`', '`'] },
        { icon: 'bi-code-square',        tip: '代码块',   insert: '```\n\n```' },
        { icon: 'bi-table',              tip: '表格',     insert: '| 列1 | 列2 | 列3 |\n| --- | --- | --- |\n| 内容 | 内容 | 内容 |' },
        { icon: 'bi-quote',              tip: '引用',     prefix: '> ' },
        { icon: 'bi-dash',               tip: '分隔线',   insert: '\n---\n' },
        { sep: true },
        { icon: 'bi-sigma',              tip: '行内公式', wrap: ['$', '$'],           placeholder: 'E=mc^2' },
        { icon: 'bi-calculator',         tip: '块级公式', insert: '$$\nE=mc^2\n$$' },
        { sep: true },
        { icon: 'bi-layout-split',       tip: '切换预览', action: 'toggle' },
        { icon: 'bi-arrows-fullscreen',  tip: '全屏',     action: 'fullscreen' }
    ];

    /* ===== 生成工具栏 HTML ===== */
    function buildToolbar() {
        var html = '';
        BUTTONS.forEach(function (btn, i) {
            if (btn.sep) {
                html += '<span class="md-sep"></span>';
            } else if (btn.action === 'toggle') {
                html += '<button type="button" class="md-btn" data-action="toggle" title="' + btn.tip + '"><i class="bi ' + btn.icon + '"></i></button>';
            } else {
                html += '<button type="button" class="md-btn" data-idx="' + i + '" title="' + btn.tip + '"><i class="bi ' + btn.icon + '"></i></button>';
            }
        });
        return html;
    }

    /* ===== 向 textarea 插入内容 ===== */
    function insertText(ta, before, after, placeholder) {
        var start = ta.selectionStart;
        var end = ta.selectionEnd;
        var val = ta.value;
        var sel = val.substring(start, end) || placeholder || '';

        // 行首语法确保在新行
        if (before && start > 0 && val[start - 1] !== '\n') {
            before = '\n' + before;
        }

        var text = before + sel + (after || '');
        ta.value = val.substring(0, start) + text + val.substring(end);
        // 选中插入的文字
        ta.selectionStart = start + before.length;
        ta.selectionEnd = start + before.length + sel.length;
        ta.focus();
        // 触发 input 事件以更新预览
        ta.dispatchEvent(new Event('input'));
    }

    function insertAtCursor(ta, text) {
        var start = ta.selectionStart;
        var val = ta.value;
        ta.value = val.substring(0, start) + text + val.substring(ta.selectionEnd);
        ta.selectionStart = ta.selectionEnd = start + text.length;
        ta.focus();
        ta.dispatchEvent(new Event('input'));
    }

    /* ===== 初始化编辑器 ===== */
    window.mdEditorInit = function (wrapId, previewSelector) {
        var wrap = document.getElementById(wrapId);
        if (!wrap) return;

        var ta = wrap.querySelector('textarea');
        var preview = wrap.querySelector(previewSelector || '.md-preview');
        if (!ta || !preview) return;

        // 注入工具栏
        var toolbar = wrap.querySelector('.md-toolbar');
        if (toolbar) {
            toolbar.innerHTML = buildToolbar();
        }

        // 预览更新函数
        function updatePreview() {
            var raw = ta.value;
            if (raw.trim()) {
                preview.innerHTML = DOMPurify.sanitize(marked.parse(raw));
                // 渲染 LaTeX 公式
                if (typeof renderMathInElement !== 'undefined') {
                    renderMathInElement(preview, {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '$', right: '$', display: false}
                        ],
                        throwOnError: false
                    });
                }
            } else {
                preview.innerHTML = '<span style="color:#adb5bd">预览区域</span>';
            }
        }

        // 初始渲染
        updatePreview();

        // 实时预览
        ta.addEventListener('input', updatePreview);

        // 同步滚动
        ta.addEventListener('scroll', function () {
            if (preview.classList.contains('d-none')) return;
            var pct = ta.scrollTop / (ta.scrollHeight - ta.clientHeight || 1);
            preview.scrollTop = pct * (preview.scrollHeight - preview.clientHeight || 1);
        });

        // Tab 缩进
        ta.addEventListener('keydown', function (e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                var s = ta.selectionStart;
                ta.value = ta.value.substring(0, s) + '    ' + ta.value.substring(ta.selectionEnd);
                ta.selectionStart = ta.selectionEnd = s + 4;
                updatePreview();
            }
        });

        // 处理特殊操作
        function handleAction(action, btn) {
            if (action === 'toggle') {
                preview.classList.toggle('d-none');
                ta.style.flex = preview.classList.contains('d-none') ? '1' : '';
            } else if (action === 'fullscreen') {
                wrap.classList.toggle('md-fullscreen');
                // 切换图标
                var icon = btn.querySelector('i');
                if (wrap.classList.contains('md-fullscreen')) {
                    icon.className = 'bi bi-fullscreen-exit';
                    btn.title = '退出全屏';
                    document.body.style.overflow = 'hidden';
                } else {
                    icon.className = 'bi bi-arrows-fullscreen';
                    btn.title = '全屏';
                    document.body.style.overflow = '';
                }
            }
        }

        // 工具栏点击
        if (toolbar) {
            toolbar.addEventListener('click', function (e) {
                var btn = e.target.closest('.md-btn');
                if (!btn) return;

                // 特殊操作按钮
                if (btn.dataset.action) {
                    handleAction(btn.dataset.action, btn);
                    return;
                }

                var idx = parseInt(btn.dataset.idx, 10);
                var def = BUTTONS[idx];
                if (!def) return;

                if (def.wrap) {
                    insertText(ta, def.wrap[0], def.wrap[1], '');
                } else if (def.prefix) {
                    insertText(ta, def.prefix, '', '');
                } else if (def.insert) {
                    insertAtCursor(ta, def.insert);
                }
            });
        }
    };
})();
