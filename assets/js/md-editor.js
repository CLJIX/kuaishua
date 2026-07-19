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
        { icon: 'bi-code-square',        tip: '代码块',   insert: '```\n\n```', block: true },
        { icon: 'bi-table',              tip: '表格',     insert: '| 列1 | 列2 | 列3 |\n| --- | --- | --- |\n| 内容 | 内容 | 内容 |', block: true },
        { icon: 'bi-quote',              tip: '引用',     prefix: '> ' },
        { icon: 'bi-dash',               tip: '分隔线',   insert: '---', block: true },
        { sep: true },
        { icon: 'bi-braces',             tip: '行内公式', wrap: ['$', '$'], placeholder: 'E=mc^2' },
        { icon: 'bi-calculator',         tip: '块级公式', insert: '$$\nE=mc^2\n$$', block: true },
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

    /* ===== 向 textarea 插入行内包裹语法（wrap）或行首前缀语法（prefix） ===== */
    function insertText(ta, before, after, placeholder) {
        var start = ta.selectionStart;
        var end = ta.selectionEnd;
        var val = ta.value;
        var sel = val.substring(start, end) || placeholder || '';

        // 行首前缀语法（prefix）：确保从新行开始
        if (!after && before && start > 0 && val[start - 1] !== '\n') {
            before = '\n' + before;
        }

        var text = before + sel + (after || '');
        ta.value = val.substring(0, start) + text + val.substring(end);
        // 选中插入的文字（方便用户直接替换）
        ta.selectionStart = start + before.length;
        ta.selectionEnd = start + before.length + sel.length;
        ta.focus();
        ta.dispatchEvent(new Event('input', { bubbles: true }));
    }

    /* ===== 插入块级元素（代码块、表格、分隔线、块级公式等） ===== */
    // 自动检测光标前后字符，确保块级语法独立成行（前后补换行）
    function insertBlock(ta, content) {
        var start = ta.selectionStart;
        var end = ta.selectionEnd;
        var val = ta.value;
        var prefix = '';
        var suffix = '';

        // 前方：若光标不在文本起始且前一字符非换行，补一个换行
        if (start > 0 && val[start - 1] !== '\n') {
            prefix = '\n';
        }
        // 额外空行：若前方已有内容且不是连续两个换行，再补一个换行形成空行
        if (start > 0 && val[start - 1] === '\n' && start > 1 && val[start - 2] !== '\n') {
            prefix = '\n';
        }

        // 后方：若光标不在文本末尾且后一字符非换行，补一个换行
        if (end < val.length && val[end] !== '\n') {
            suffix = '\n';
        }

        var text = prefix + content + suffix;
        ta.value = val.substring(0, start) + text + val.substring(end);
        // 光标定位到内容末尾
        var cursorPos = start + text.length;
        ta.selectionStart = ta.selectionEnd = cursorPos;
        ta.focus();
        ta.dispatchEvent(new Event('input', { bubbles: true }));
    }

    /* ===== 在光标处插入普通行内文本（链接、图片等） ===== */
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
                    insertText(ta, def.wrap[0], def.wrap[1], def.placeholder || '');
                } else if (def.prefix) {
                    insertText(ta, def.prefix, '', '');
                } else if (def.insert) {
                    if (def.block) {
                        insertBlock(ta, def.insert);
                    } else {
                        insertAtCursor(ta, def.insert);
                    }
                }
                // 工具栏插入后立即刷新预览
                forceUpdate();
            });
        }
    };
    /* ===== 选项编辑器（共用工具栏 + 焦点跟踪 + 实时预览） ===== */
    var OPTION_BTNS = [
        { icon: 'bi-type-bold',          tip: '粗体',   wrap: ['**', '**'] },
        { icon: 'bi-type-italic',        tip: '斜体',   wrap: ['*', '*'] },
        { icon: 'bi-type-strikethrough', tip: '删除线', wrap: ['~~', '~~'] },
        { sep: true },
        { icon: 'bi-code',               tip: '行内代码', wrap: ['`', '`'] },
        { icon: 'bi-braces',             tip: '行内公式', wrap: ['$', '$'], placeholder: 'E=mc^2' },
        { sep: true },
        { icon: 'bi-link-45deg',         tip: '链接',   insert: '[链接文字](https://)' },
        { icon: 'bi-image',              tip: '图片',   action: 'mediaLibrary' },
        { sep: true },
        { icon: 'bi-layout-split',       tip: '切换预览', action: 'togglePreview' },
        { icon: 'bi-arrows-fullscreen',  tip: '全屏',     action: 'fullscreen' }
    ];

    function buildOptionToolbar() {
        var html = '';
        OPTION_BTNS.forEach(function (btn, i) {
            if (btn.sep) {
                html += '<span class="md-sep"></span>';
            } else if (btn.action) {
                html += '<button type="button" class="md-btn" data-oaction="' + btn.action + '" title="' + btn.tip + '"><i class="bi ' + btn.icon + '"></i></button>';
            } else {
                html += '<button type="button" class="md-btn" data-oidx="' + i + '" title="' + btn.tip + '"><i class="bi ' + btn.icon + '"></i></button>';
            }
        });
        return html;
    }

    /**
     * 初始化选项编辑器（共用工具栏 + 全选项同步预览 + 同步滚动）
     * @param {string} containerId  选项容器 ID（内含 .md-toolbar 和 .md-options-preview）
     */
    window.mdOptionsEditorInit = function (containerId) {
        var container = document.getElementById(containerId);
        if (!container || container._mdOptEdInited) return;
        container._mdOptEdInited = true;

        var toolbar = container.querySelector('.md-toolbar');
        var optionsList = container.querySelector('.md-options-list');
        var preview = container.querySelector('.md-options-preview');
        var activeTa = null; // 当前焦点所在的 textarea（用于工具栏操作定位）
        var _timer = null;
        var _scrolling = false; // 防止同步滚动循环触发

        if (toolbar) toolbar.innerHTML = buildOptionToolbar();

        // 获取容器内所有选项 textarea
        function getTextareas() {
            return container.querySelectorAll('textarea[name^="option_"]');
        }

        // 从 textarea name 提取选项标签（如 option_A -> A）
        function getLabel(ta) {
            var match = ta.name.match(/^option_(.+)$/);
            return match ? match[1] : '';
        }

        // 焦点跟踪：记录当前活动 textarea（工具栏操作需要）
        container.addEventListener('focusin', function (e) {
            if (e.target.tagName === 'TEXTAREA' && e.target.name && e.target.name.indexOf('option_') === 0) {
                activeTa = e.target;
            }
        });

        // 输入时更新全部预览（防抖 80ms）
        container.addEventListener('input', function (e) {
            if (e.target.tagName === 'TEXTAREA' && e.target.name && e.target.name.indexOf('option_') === 0) {
                clearTimeout(_timer);
                _timer = setTimeout(updateAllPreview, 80);
            }
        });

        // 渲染所有选项的预览
        function updateAllPreview() {
            if (!preview) return;
            var tas = getTextareas();
            if (tas.length === 0) {
                preview.innerHTML = '<span style="color:#adb5bd">选项预览</span>';
                return;
            }
            var html = '';
            tas.forEach(function (ta) {
                var label = getLabel(ta);
                var content = ta.value.trim();
                html += '<div class="md-option-preview-item">';
                html += '<div class="md-option-preview-label"><span class="badge bg-primary">' + label + '</span></div>';
                if (content) {
                    try {
                        html += '<div class="md-option-preview-content">' + DOMPurify.sanitize(marked.parse(ta.value)) + '</div>';
                    } catch (err) {
                        html += '<div class="md-option-preview-content"><pre style="white-space:pre-wrap;color:#dc3545;font-size:0.85em">' +
                            ta.value.replace(/</g, '&lt;') + '</pre></div>';
                    }
                } else {
                    html += '<div class="md-option-preview-content text-muted" style="font-size:0.85em">（空）</div>';
                }
                html += '</div>';
            });
            preview.innerHTML = html;

            // KaTeX 公式渲染
            if (typeof renderMathInElement !== 'undefined') {
                try {
                    renderMathInElement(preview, {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '$', right: '$', display: false}
                        ],
                        throwOnError: false
                    });
                } catch (e2) { /* KaTeX 失败不影响主体 */ }
            }
        }

        // 同步滚动：左侧选项列表 -> 右侧预览
        if (optionsList && preview) {
            optionsList.addEventListener('scroll', function () {
                if (_scrolling) return;
                _scrolling = true;
                var pct = optionsList.scrollTop / (optionsList.scrollHeight - optionsList.clientHeight || 1);
                preview.scrollTop = pct * (preview.scrollHeight - preview.clientHeight || 1);
                requestAnimationFrame(function () { _scrolling = false; });
            });
        }

        // 切换预览显示/隐藏
        function togglePreview(btn) {
            if (!preview) return;
            preview.classList.toggle('d-none');
            var icon = btn.querySelector('i');
            if (preview.classList.contains('d-none')) {
                icon.className = 'bi bi-eye';
                btn.title = '显示预览';
            } else {
                icon.className = 'bi bi-layout-split';
                btn.title = '切换预览';
                updateAllPreview();
            }
        }

        // 切换全屏
        function toggleFullscreen(btn) {
            container.classList.toggle('md-fullscreen');
            var icon = btn.querySelector('i');
            if (container.classList.contains('md-fullscreen')) {
                icon.className = 'bi bi-fullscreen-exit';
                btn.title = '退出全屏';
                document.body.style.overflow = 'hidden';
            } else {
                icon.className = 'bi bi-arrows-fullscreen';
                btn.title = '全屏';
                document.body.style.overflow = '';
            }
        }

        // 工具栏交互
        if (toolbar) {
            toolbar.addEventListener('mousedown', function (e) {
                if (e.target.closest('.md-btn')) e.preventDefault();
            });

            toolbar.addEventListener('click', function (e) {
                var btn = e.target.closest('.md-btn');
                if (!btn) return;

                // 特殊操作
                if (btn.dataset.oaction === 'togglePreview') {
                    togglePreview(btn);
                    return;
                }
                if (btn.dataset.oaction === 'fullscreen') {
                    toggleFullscreen(btn);
                    return;
                }
                if (btn.dataset.oaction === 'mediaLibrary') {
                    var ta = activeTa || getTextareas()[0];
                    if (!ta) return;
                    if (typeof MediaLibraryModal !== 'undefined') {
                        MediaLibraryModal.open(function (items) {
                            items.forEach(function (item) {
                                insertAtCursor(ta, '![' + (item.file_name || 'image') + '](' + item.url + ')');
                            });
                            updateAllPreview();
                        });
                    } else {
                        insertAtCursor(ta, '![描述](https://)');
                        updateAllPreview();
                    }
                    return;
                }

                // 语法插入：作用于当前活动 textarea
                var ta = activeTa || getTextareas()[0];
                if (!ta) return;

                var idx = parseInt(btn.dataset.oidx, 10);
                var def = OPTION_BTNS[idx];
                if (!def) return;

                if (def.wrap) {
                    insertText(ta, def.wrap[0], def.wrap[1], def.placeholder || '');
                } else if (def.insert) {
                    insertAtCursor(ta, def.insert);
                }
                updateAllPreview();
            });
        }

        // 初始预览
        updateAllPreview();
    };
})();
