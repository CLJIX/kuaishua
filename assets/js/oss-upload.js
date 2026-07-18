/**
 * OSS 统一上传组件
 * 提供：文件上传、媒体库弹窗选择、粘贴自动上传
 * 依赖：Bootstrap 5 (Modal), 页面已有 csrf_token 隐藏字段
 */
(function () {
    'use strict';

    var UPLOAD_URL = 'index.php?page=admin&action=upload_image';
    var MEDIA_API  = 'index.php?page=admin&action=media';

    // 获取 CSRF token
    function getCsrfToken() {
        var el = document.querySelector('input[name="csrf_token"]');
        return el ? el.value : '';
    }

    // =====================================================
    // OssUploader：上传文件到 OSS
    // =====================================================
    window.OssUploader = {
        /**
         * 上传单个文件
         * @param {File} file
         * @param {Object} options {questionId, bizType, onProgress}
         * @returns {Promise<{success, url, media_id, file_name, error}>}
         */
        upload: function (file, options) {
            options = options || {};
            var formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', getCsrfToken());
            if (options.questionId) formData.append('question_id', options.questionId);
            formData.append('biz_type', options.bizType || 'general');

            return new Promise(function (resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', UPLOAD_URL);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                if (options.onProgress && xhr.upload) {
                    xhr.upload.onprogress = function (e) {
                        if (e.lengthComputable) {
                            options.onProgress(Math.round(e.loaded / e.total * 100));
                        }
                    };
                }

                xhr.onload = function () {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        resolve(data);
                    } catch (err) {
                        resolve({ success: false, error: '响应解析失败' });
                    }
                };
                xhr.onerror = function () {
                    resolve({ success: false, error: '网络请求失败' });
                };
                xhr.send(formData);
            });
        }
    };

    // =====================================================
    // MediaLibraryModal：媒体库选择弹窗
    // =====================================================
    var _modalCallback = null;
    var _selectedItems = [];
    var _currentPage = 1;

    window.MediaLibraryModal = {
        /**
         * 打开媒体库弹窗
         * @param {Function} callback 选中后回调，参数为 [{url, media_id, file_name}, ...]
         */
        open: function (callback) {
            _modalCallback = callback;
            _selectedItems = [];
            _currentPage = 1;
            this._updateConfirmBtn();
            this._loadPage(1);

            var modalEl = document.getElementById('mediaLibraryModal');
            if (modalEl) {
                var modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        },

        _loadPage: function (page, keyword) {
            _currentPage = page;
            var grid = document.getElementById('modal-media-grid');
            if (!grid) return;
            grid.innerHTML = '<div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm"></div> 加载中...</div>';

            var params = 'page=' + page;
            if (keyword) params += '&keyword=' + encodeURIComponent(keyword);

            fetch(MEDIA_API + '&' + params, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                // OSS 未配置时在弹窗内显示引导提示
                if (data.oss_configured === false) {
                    var grid = document.getElementById('modal-media-grid');
                    if (grid) {
                        grid.innerHTML =
                            '<div class="col-12 text-center py-5">' +
                            '<i class="bi bi-exclamation-triangle text-warning fs-1"></i>' +
                            '<p class="mt-2 mb-3 text-muted">OSS 对象存储尚未配置，媒体库无法使用。</p>' +
                            '<a href="index.php?page=admin&amp;action=oss_settings" class="btn btn-warning btn-sm">' +
                            '<i class="bi bi-gear-fill"></i> 前往配置 OSS</a>' +
                            '</div>';
                    }
                    var nav = document.getElementById('modal-pagination');
                    if (nav) nav.innerHTML = '';
                    return;
                }
                _renderGrid(data.items || []);
                _renderPagination(data);
            })
            .catch(function () {
                grid.innerHTML = '<div class="text-center text-danger py-5">加载失败，请重试</div>';
            });
        },

        _updateConfirmBtn: function () {
            var btn = document.getElementById('modal-confirm-btn');
            var countEl = document.getElementById('modal-selected-count');
            if (btn) btn.disabled = _selectedItems.length === 0;
            if (countEl) {
                countEl.textContent = _selectedItems.length > 0
                    ? '已选择 ' + _selectedItems.length + ' 个'
                    : '未选择';
            }
        }
    };

    function _renderGrid(items) {
        var grid = document.getElementById('modal-media-grid');
        if (!grid) return;

        if (items.length === 0) {
            grid.innerHTML = '<div class="text-center text-muted py-5">暂无媒体文件</div>';
            return;
        }

        var html = '';
        items.forEach(function (item) {
            var isSelected = _selectedItems.some(function (s) { return s.media_id === item.id; });
            html += '<div class="col-4 col-md-3 col-lg-2">' +
                '<div class="media-modal-item border rounded p-1 cursor-pointer position-relative' +
                    (isSelected ? ' border-primary border-2' : '') + '" ' +
                    'data-id="' + item.id + '" ' +
                    'data-url="' + (item.cdn_url || item.preview_url || '') + '" ' +
                    'data-filename="' + (item.file_name || '') + '">' +
                    '<img src="' + (item.thumbnail_url || '') + '" ' +
                        'class="img-fluid w-100" style="height:100px;object-fit:cover" loading="lazy">' +
                    '<div class="text-truncate small mt-1" style="font-size:0.7rem">' + (item.file_name || '') + '</div>' +
                    (isSelected ? '<div class="position-absolute top-0 end-0 bg-primary text-white rounded-circle p-1" style="width:20px;height:20px;font-size:10px;line-height:1"><i class="bi bi-check"></i></div>' : '') +
                '</div></div>';
        });
        grid.innerHTML = html;

        // 绑定选择事件
        grid.querySelectorAll('.media-modal-item').forEach(function (el) {
            el.addEventListener('click', function () {
                var id = parseInt(this.dataset.id);
                var idx = _selectedItems.findIndex(function (s) { return s.media_id === id; });
                if (idx >= 0) {
                    _selectedItems.splice(idx, 1);
                    this.classList.remove('border-primary', 'border-2');
                    var badge = this.querySelector('.position-absolute');
                    if (badge) badge.remove();
                } else {
                    _selectedItems.push({
                        media_id: id,
                        url: this.dataset.url,
                        file_name: this.dataset.filename
                    });
                    this.classList.add('border-primary', 'border-2');
                    var checkDiv = document.createElement('div');
                    checkDiv.className = 'position-absolute top-0 end-0 bg-primary text-white rounded-circle p-1';
                    checkDiv.style.cssText = 'width:20px;height:20px;font-size:10px;line-height:1';
                    checkDiv.innerHTML = '<i class="bi bi-check"></i>';
                    this.appendChild(checkDiv);
                }
                MediaLibraryModal._updateConfirmBtn();
            });
        });
    }

    function _renderPagination(data) {
        var nav = document.getElementById('modal-pagination');
        if (!nav || data.pages <= 1) { if (nav) nav.innerHTML = ''; return; }

        var html = '<ul class="pagination pagination-sm justify-content-center mb-0">';
        html += '<li class="page-item' + (data.current_page <= 1 ? ' disabled' : '') + '">';
        html += '<a class="page-link" href="#" data-page="' + (data.current_page - 1) + '">上一页</a></li>';

        var start = Math.max(1, data.current_page - 2);
        var end = Math.min(data.pages, data.current_page + 2);
        for (var p = start; p <= end; p++) {
            html += '<li class="page-item' + (p === data.current_page ? ' active' : '') + '">';
            html += '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }

        html += '<li class="page-item' + (data.current_page >= data.pages ? ' disabled' : '') + '">';
        html += '<a class="page-link" href="#" data-page="' + (data.current_page + 1) + '">下一页</a></li>';
        html += '</ul>';
        nav.innerHTML = html;

        nav.querySelectorAll('.page-link').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                var page = parseInt(this.dataset.page);
                if (page > 0 && page <= data.pages) {
                    var keyword = (document.getElementById('modal-search-input') || {}).value || '';
                    MediaLibraryModal._loadPage(page, keyword);
                }
            });
        });
    }

    // 初始化弹窗事件（在 DOMContentLoaded 后调用）
    function _initModalEvents() {
        // 确认选择
        var confirmBtn = document.getElementById('modal-confirm-btn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (_modalCallback && _selectedItems.length > 0) {
                    _modalCallback(_selectedItems);
                }
                var modalEl = document.getElementById('mediaLibraryModal');
                if (modalEl) bootstrap.Modal.getInstance(modalEl).hide();
            });
        }

        // 搜索（防抖 300ms）
        var searchInput = document.getElementById('modal-search-input');
        if (searchInput) {
            var _searchTimer = null;
            searchInput.addEventListener('input', function () {
                clearTimeout(_searchTimer);
                var val = this.value;
                _searchTimer = setTimeout(function () {
                    MediaLibraryModal._loadPage(1, val);
                }, 300);
            });
        }

        // 弹窗内上传
        var uploadInput = document.getElementById('modal-upload-input');
        var statusEl = document.getElementById('modal-upload-status');
        if (uploadInput) {
            uploadInput.addEventListener('change', function () {
                var files = this.files;
                if (!files || files.length === 0) return;
                var idx = 0;
                function uploadNext() {
                    if (idx >= files.length) {
                        if (statusEl) statusEl.textContent = '上传完成';
                        // 刷新列表
                        MediaLibraryModal._loadPage(1, (document.getElementById('modal-search-input') || {}).value || '');
                        return;
                    }
                    if (statusEl) statusEl.textContent = '上传中 ' + (idx + 1) + '/' + files.length + '...';
                    OssUploader.upload(files[idx], { bizType: 'general' }).then(function (result) {
                        if (!result.success) {
                            var errMsg = '上传失败：' + (result.error || '未知错误');
                            if (result.debug && result.debug.server_canonical_request) {
                                errMsg += '\n\n===== 服务端 CanonicalRequest =====\n' + result.debug.server_canonical_request;
                            }
                            if (result.debug && result.debug.client_canonical_request) {
                                errMsg += '\n\n===== 客户端 CanonicalRequest =====\n' + result.debug.client_canonical_request;
                            }
                            console.error('OSS上传失败debug:', result.debug || result);
                            if (statusEl) statusEl.innerHTML = '<pre class="text-danger small mb-0" style="white-space:pre-wrap">' + errMsg.replace(/</g, '&lt;') + '</pre>';
                            return;
                        }
                        idx++;
                        uploadNext();
                    });
                }
                uploadNext();
                this.value = ''; // 清空以支持重复选择
            });
        }
    }

    // =====================================================
    // 粘贴上传 Hook
    // =====================================================
    function _initPasteUpload() {
        document.addEventListener('paste', function (e) {
            // 检测剪贴板中的图片
            var items = (e.clipboardData || {}).items;
            if (!items) return;

            var imageFiles = [];
            for (var i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    imageFiles.push(items[i].getAsFile());
                }
            }
            if (imageFiles.length === 0) return;

            // 找到当前焦点所在的 textarea
            var activeEl = document.activeElement;
            if (!activeEl || activeEl.tagName !== 'TEXTAREA') return;

            e.preventDefault();

            var ta = activeEl;
            var questionId = _getQuestionId(ta);

            // 上传每张图片
            imageFiles.forEach(function (file) {
                // 插入占位文本
                var placeholder = '![上传中...]()';
                _insertAtCursor(ta, placeholder);
                ta.dispatchEvent(new Event('input', { bubbles: true }));

                OssUploader.upload(file, {
                    questionId: questionId,
                    bizType: 'question'
                }).then(function (result) {
                    // 替换占位符
                    if (result.success) {
                        ta.value = ta.value.replace('![上传中...]()', '![' + (result.file_name || 'image') + '](' + result.url + ')');
                    } else {
                        ta.value = ta.value.replace('![上传中...]()', '');
                        var msg = '图片上传失败：' + (result.error || '未知错误');
                        if (result.debug && result.debug.server_canonical_request) {
                            msg += '\n\n===== 服务端 CanonicalRequest =====\n' + result.debug.server_canonical_request;
                        }
                        if (result.debug && result.debug.client_canonical_request) {
                            msg += '\n\n===== 客户端 CanonicalRequest =====\n' + result.debug.client_canonical_request;
                        }
                        console.error('OSS上传失败debug:', result.debug || result);
                        alert(msg);
                    }
                    ta.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
        });
    }

    function _insertAtCursor(ta, text) {
        var start = ta.selectionStart;
        var val = ta.value;
        ta.value = val.substring(0, start) + text + val.substring(ta.selectionEnd);
        ta.selectionStart = ta.selectionEnd = start + text.length;
        ta.focus();
    }

    function _getQuestionId(ta) {
        // 尝试从表单中找到 question_id
        var wrap = ta.closest('.md-editor-wrap') || ta.closest('form');
        if (!wrap) return null;
        var idInput = wrap.querySelector('[name="id"]') || document.querySelector('input[name="id"]');
        // 从 URL 中取
        var match = window.location.search.match(/[?&]id=(\d+)/);
        return match ? match[1] : null;
    }

    // =====================================================
    // 初始化入口
    // =====================================================
    window.initOssUpload = function (options) {
        options = options || {};
        // 直接初始化（此函数通常在 DOMContentLoaded 回调中调用，DOM 已就绪）
        _initModalEvents();
        _initPasteUpload();
    };
})();
