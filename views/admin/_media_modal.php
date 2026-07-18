<?php
/**
 * 媒体选择器模态弹窗（可复用组件）
 * 由编辑器工具栏"插入图片"按钮触发，供题目/选项/解析编辑器共用
 * 依赖：Bootstrap 5 Modal + oss-upload.js
 */
?>
<!-- 媒体库选择弹窗 -->
<div class="modal fade" id="mediaLibraryModal" tabindex="-1" aria-labelledby="mediaLibraryLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaLibraryLabel">
                    <i class="bi bi-images"></i> 从媒体库选择图片
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <!-- 弹窗内上传按钮 -->
                    <label class="btn btn-sm btn-primary mb-0">
                        <i class="bi bi-cloud-upload"></i> 上传图片
                        <input type="file" id="modal-upload-input" accept="image/*" style="display:none" multiple>
                    </label>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                </div>
            </div>
            <div class="modal-body">
                <!-- 搜索栏 -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <input type="text" id="modal-search-input" class="form-control form-control-sm"
                               placeholder="搜索文件名...">
                    </div>
                    <div class="col-md-3">
                        <span id="modal-upload-status" class="text-muted small"></span>
                    </div>
                </div>

                <!-- 缩略图网格 -->
                <div id="modal-media-grid" class="row g-2" style="min-height:200px">
                    <div class="text-center text-muted py-5">
                        <div class="spinner-border spinner-border-sm"></div> 加载中...
                    </div>
                </div>

                <!-- 分页 -->
                <nav class="mt-3" id="modal-pagination"></nav>
            </div>
            <div class="modal-footer">
                <span id="modal-selected-count" class="me-auto text-muted">未选择</span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" id="modal-confirm-btn" class="btn btn-primary" disabled>
                    <i class="bi bi-check-lg"></i> 确认选择
                </button>
            </div>
        </div>
    </div>
</div>
