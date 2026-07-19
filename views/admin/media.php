<?php
/**
 * 管理后台 - 媒体库
 *
 * 可用变量：
 * - $result        (array): 分页查询结果 ['items', 'total', 'pages', 'current_page']
 * - $stats         (array): 统计信息 ['total_count', 'total_size', 'month_count']
 * - $filters       (array): 当前筛选条件
 * - $ossConfigured (bool):  OSS 是否已配置
 */
$items       = $result['items']        ?? [];
$total       = $result['total']        ?? 0;
$pages       = $result['pages']        ?? 0;
$currentPage = $result['current_page'] ?? 1;

$totalCount = $stats['total_count'] ?? 0;
$totalSize  = $stats['total_size']  ?? 0;
$monthCount = $stats['month_count'] ?? 0;

if (!function_exists('_formatSize')) {
    function _formatSize($bytes): string {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <?php include __DIR__ . '/_sidebar.php'; ?>
        </div>

        <div class="col-md-9 col-lg-10">
            <?php include __DIR__ . '/_flash.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="bi bi-images"></i> 媒体库</h4>
                <?php if ($ossConfigured): ?>
                <span class="text-muted" id="media-stats">
                    共 <?= $totalCount ?> 个文件 / <?= _formatSize($totalSize) ?> / 本月新增 <?= $monthCount ?> 个
                </span>
                <?php endif; ?>
            </div>

            <?php if (!$ossConfigured): ?>
                <div class="alert alert-warning shadow-sm" role="alert">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0 mt-1"></i>
                        <div>
                            <h6 class="alert-heading mb-1">OSS 对象存储尚未配置</h6>
                            <p class="mb-2">媒体库需要配置阿里云 OSS 后才能正常使用。配置完成后，在题目编辑器中粘贴或选择的图片将自动上传到 OSS。</p>
                            <hr>
                            <div class="d-flex gap-2">
                                <a href="<?= url('admin', ['action' => 'oss_settings']) ?>" class="btn btn-warning btn-sm">
                                    <i class="bi bi-gear-fill"></i> 前往配置 OSS
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="alert">知道了</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>

            <div id="media-grid-area">
                <?php include __DIR__ . '/_media_grid.php'; ?>
            </div>

            <?php endif; // $ossConfigured ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_media_detail_modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var gridArea = document.getElementById('media-grid-area');

    function loadPage(page) {
        fetch('index.php?page=admin&action=media&pg=' + page, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            if (gridArea) gridArea.innerHTML = html;
            bindGridEvents();
        })
        .catch(function() {
            if (gridArea) gridArea.innerHTML = '<div class="alert alert-danger">加载失败，请重试</div>';
        });
    }

    function bindGridEvents() {
        document.querySelectorAll('.copy-url-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var url = this.dataset.url;
                navigator.clipboard.writeText(url).then(function() {
                    btn.innerHTML = '<i class="bi bi-check"></i>';
                    setTimeout(function() { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
                }).catch(function() {
                    prompt('复制链接：', url);
                });
            });
        });

        document.querySelectorAll('.media-card').forEach(function(card) {
            card.addEventListener('click', function(e) {
                if (e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
                openDetail(this);
            });
        });

        document.querySelectorAll('#media-grid-area .pagination .page-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var page = this.dataset.page;
                if (page) loadPage(parseInt(page, 10));
            });
        });
    }

    function _formatDetailSize(bytes) {
        bytes = parseInt(bytes, 10) || 0;
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(1) + ' GB';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    function openDetail(card) {
        var originalUrl = card.dataset.originalUrl || '';
        if (!originalUrl) return;
        var modalEl = document.getElementById('mediaDetailModal');
        if (!modalEl) return;
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

        document.getElementById('detail-image').src = originalUrl;
        document.getElementById('detail-file-name').textContent = card.dataset.fileName || '';
        document.getElementById('detail-file-size').textContent = _formatDetailSize(card.dataset.fileSize);
        document.getElementById('detail-mime-type').textContent = card.dataset.mimeType || '';
        document.getElementById('detail-uploader').textContent = card.dataset.uploader || '未知';
        document.getElementById('detail-created-at').textContent = card.dataset.createdAt || '';
        document.getElementById('detail-biz-type').textContent = card.dataset.bizType || '';

        var questionId = card.dataset.questionId || '';
        var questionTitle = card.dataset.questionTitle || '';
        var questionEl = document.getElementById('detail-question');
        var editLink = document.getElementById('detail-edit-link');
        if (questionId) {
            var title = questionTitle || '题目 #' + questionId;
            questionEl.innerHTML = '<a href="index.php?page=admin&amp;action=question_edit&amp;id=' + questionId + '" target="_blank">' + title + '</a>';
            editLink.href = 'index.php?page=admin&action=question_edit&id=' + questionId;
            editLink.style.display = '';
        } else {
            questionEl.textContent = '未关联题目';
            editLink.style.display = 'none';
        }

        modal.show();
    }

    bindGridEvents();
});
</script>
