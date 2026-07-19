<?php
/**
 * 媒体库图片详情弹窗（共用）
 */
?>
<div class="modal fade" id="mediaDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-image"></i> 图片详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-4">
                <div class="row g-4">
                    <!-- 左侧：图片预览区 -->
                    <div class="col-md-7">
                        <div class="bg-light rounded-3 d-flex align-items-center justify-content-center p-3" style="min-height:300px">
                            <img id="detail-image" src="" alt="" class="img-fluid rounded shadow-sm" style="max-height:500px;object-fit:contain">
                        </div>
                    </div>
                    <!-- 右侧：元数据信息 -->
                    <div class="col-md-5">
                        <div class="detail-meta-list">
                            <div class="detail-meta-item">
                                <span class="detail-meta-label"><i class="bi bi-file-earmark"></i> 文件名</span>
                                <span class="detail-meta-value text-break" id="detail-file-name"></span>
                            </div>
                            <div class="detail-meta-item">
                                <span class="detail-meta-label"><i class="bi bi-hdd"></i> 文件大小</span>
                                <span class="detail-meta-value" id="detail-file-size"></span>
                            </div>
                            <div class="detail-meta-item">
                                <span class="detail-meta-label"><i class="bi bi-filetype-raw"></i> MIME 类型</span>
                                <span class="detail-meta-value" id="detail-mime-type"></span>
                            </div>
                            <div class="detail-meta-item">
                                <span class="detail-meta-label"><i class="bi bi-person"></i> 上传者</span>
                                <span class="detail-meta-value" id="detail-uploader"></span>
                            </div>
                            <div class="detail-meta-item">
                                <span class="detail-meta-label"><i class="bi bi-clock"></i> 上传时间</span>
                                <span class="detail-meta-value" id="detail-created-at"></span>
                            </div>
                            <div class="detail-meta-item">
                                <span class="detail-meta-label"><i class="bi bi-tag"></i> 业务类型</span>
                                <span class="detail-meta-value" id="detail-biz-type"></span>
                            </div>
                            <div class="detail-meta-item">
                                <span class="detail-meta-label"><i class="bi bi-link-45deg"></i> 关联题目</span>
                                <span class="detail-meta-value" id="detail-question"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <a id="detail-edit-link" href="#" target="_blank" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil-square"></i> 编辑关联题目
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> 关闭
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.detail-meta-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.detail-meta-item {
    display: flex;
    flex-direction: column;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
}
.detail-meta-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.detail-meta-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.2rem;
}
.detail-meta-label i {
    margin-right: 0.25rem;
}
.detail-meta-value {
    font-size: 0.9rem;
    color: #212529;
    word-break: break-all;
}
</style>
