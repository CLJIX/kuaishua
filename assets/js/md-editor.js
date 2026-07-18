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
        { icon: 'bi-image',              tip: '图片',     action: 'mediaLibrary' },
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
            } else if (btn.action) {
                html += '<button type="button" class="md-btn" data-action="' + btn.action + '" title="' + btn.tip + '"><i class="bi ' + btn.icon + '"></i></button>';
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
        // 触发 input 事件以更新预览（使用 bubbles 确保事件可冒泡）
        ta.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function insertAtCursor(ta, text) {
        var start = ta.selectionStart;
        var val = ta.value;
        ta.value = val.substring(0, start) + text + val.substring(ta.selectionEnd);
        ta.selectionStart = ta.selectionEnd = start + text.length;
        ta.focus();
        ta.dispatchEvent(new Event('input', { bubbles: true }));
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

        // 预览更新函数（带错误捕获 + 防抖）
        var _timer = null;
        var _composing = false; // IME 组合中标记
        var _safetyTimer = null; // 防止 _composing 卡死的安全定时器

        function doUpdate() {
            try {
                var raw = ta.value;
                if (raw.trim()) {
                    var html = marked.parse(raw);
                    preview.innerHTML = DOMPurify.sanitize(html);
                    // 渲染 LaTeX 公式
                    if (typeof renderMathInElement !== 'undefined') {
                        try {
                            renderMathInElement(preview, {
                                delimiters: [
                                    {left: '$$', right: '$$', display: true},
                                    {left: '$', right: '$', display: false}
                                ],
                                throwOnError: false
                            });
                        } catch (katexErr) {
                            // KaTeX 渲染失败不影响主体内容
                        }
                    }
                } else {
                    preview.innerHTML = '<span style="color:#adb5bd">预览区域</span>';
                }
            } catch (err) {
                // 渲染失败时显示原始文本，避免预览区空白
                preview.innerHTML = '<pre style="white-space:pre-wrap;color:#dc3545;font-size:0.85em">' +
                    (ta.value || '').replace(/</g, '&lt;') + '</pre>';
            }
        }

        function scheduleUpdate() {
            if (_composing) return;
            clearTimeout(_timer);
            _timer = setTimeout(doUpdate, 80);
        }

        function forceUpdate() {
            clearTimeout(_timer);
            _composing = false; // 强制更新时无条件重置 IME 标记
            doUpdate();
        }

        // 安全机制：如果 IME 组合超过 5 秒未结束，强制重置（防止卡死）
        function resetComposingSafety() {
            clearTimeout(_safetyTimer);
            _safetyTimer = setTimeout(function() {
                if (_composing) {
                    _composing = false;
                    forceUpdate();
                }
            }, 5000);
        }

        // 初始渲染
        doUpdate();

        // ---- 事件监听（覆盖各种输入场景）----

        // 常规输入（打字、删除、粘贴、拖放等）
        ta.addEventListener('input', scheduleUpdate);

        // IME 中文输入法：组合期间跳过渲染，组合结束后立即更新
        ta.addEventListener('compositionstart', function () {
            _composing = true;
            resetComposingSafety();
        });
        ta.addEventListener('compositionend', function () {
            _composing = false;
            clearTimeout(_safetyTimer);
            forceUpdate();
        });

        // 粘贴兜底（部分浏览器 paste 后 input 事件延迟）
        ta.addEventListener('paste', function () {
            setTimeout(forceUpdate, 0);
        });

        // keyup 兜底（某些浏览器特殊键不触发 input）
        ta.addEventListener('keyup', scheduleUpdate);

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
                forceUpdate();
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
            } else if (action === 'mediaLibrary') {
                // 打开媒体库弹窗选择图片
                if (typeof MediaLibraryModal !== 'undefined') {
                    MediaLibraryModal.open(function (items) {
                        items.forEach(function (item) {
                            insertAtCursor(ta, '![' + (item.file_name || 'image') + '](' + item.url + ')\n');
                        });
                        forceUpdate();
                    });
                } else {
                    // 降级：媒体库未加载时插入占位符
                    insertAtCursor(ta, '![描述](https://)');
                    forceUpdate();
                }
            }
        }

        // 工具栏交互：mousedown 阻止 textarea 失焦（保留光标位置）
        if (toolbar) {
            toolbar.addEventListener('mousedown', function (e) {
                if (e.target.closest('.md-btn')) {
                    e.preventDefault(); // 阻止 textarea 失去焦点
                    // 工具栏操作时强制重置 IME 标记（防止 compositionend 未触发导致卡死）
                    _composing = false;
                    clearTimeout(_safetyTimer);
                }
            });

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
                // 工具栏插入后立即刷新预览
                forceUpdate();
            });
        }
    };
})();
