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

// 格式化文件大小
function _formatSize($bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
?>
<div class="container-fluid mt-4">
    <div class="row">
        <!-- 侧边栏 -->
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <?php include __DIR__ . '/_sidebar.php'; ?>
        </div>

        <!-- 主内容区 -->
        <div class="col-md-9 col-lg-10">
            <?php include __DIR__ . '/_flash.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="bi bi-images"></i> 媒体库</h4>
                <?php if ($ossConfigured): ?>
                <span class="text-muted">
                    共 <?= $totalCount ?> 个文件 / <?= _formatSize($totalSize) ?> / 本月新增 <?= $monthCount ?> 个
                </span>
                <?php endif; ?>
            </div>

            <?php if (!$ossConfigured): ?>
                <!-- OSS 未配置引导 -->
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

            <!-- 搜索与筛选 -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2">
                    <form method="GET" action="<?= url('admin', ['action' => 'media']) ?>" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">关键词</label>
                            <input type="text" name="keyword" class="form-control form-control-sm"
                                   value="<?= e($filters['keyword'] ?? '') ?>" placeholder="搜索文件名...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">业务类型</label>
                            <select name="biz_type" class="form-select form-select-sm">
                                <option value="">全部</option>
                                <option value="question" <?= ($filters['biz_type'] ?? '') === 'question' ? 'selected' : '' ?>>题目</option>
                                <option value="site" <?= ($filters['biz_type'] ?? '') === 'site' ? 'selected' : '' ?>>站点</option>
                                <option value="general" <?= ($filters['biz_type'] ?? '') === 'general' ? 'selected' : '' ?>>通用</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">开始日期</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                   value="<?= e($filters['date_from'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">结束日期</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                   value="<?= e($filters['date_to'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 d-flex gap-1">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-search"></i> 搜索
                            </button>
                            <a href="<?= url('admin', ['action' => 'media']) ?>" class="btn btn-sm btn-outline-secondary">重置</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 缩略图网格 -->
            <?php if (empty($items)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 暂无媒体文件。在题目编辑器中粘贴图片即可自动上传。
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($items as $item): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card border-0 shadow-sm h-100 media-card" data-id="<?= (int)$item['id'] ?>">
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:140px;overflow:hidden">
                                    <img src="<?= e(ossImageUrl($item['oss_path'], 'thumbnail')) ?>"
                                         alt="<?= e($item['file_name']) ?>"
                                         class="img-fluid"
                                         style="max-height:140px;max-width:100%;object-fit:cover"
                                         loading="lazy">
                                </div>
                                <div class="card-body p-2">
                                    <div class="text-truncate small fw-bold" title="<?= e($item['file_name']) ?>">
                                        <?= e($item['file_name']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.75rem">
                                        <?= _formatSize($item['file_size']) ?> /
                                        <?= date('m-d H:i', strtotime($item['created_at'])) ?>
                                    </div>
                                    <?php if (!empty($item['question_id'])): ?>
                                        <div style="font-size:0.75rem">
                                            <a href="<?= url('admin', ['action' => 'question_edit', 'id' => (int)$item['question_id']]) ?>"
                                               class="text-decoration-none" title="关联题目">
                                                <i class="bi bi-link-45deg"></i> 题目 #<?= (int)$item['question_id'] ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer p-2 bg-white border-top-0 d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill copy-url-btn"
                                            data-url="<?= e($item['cdn_url'] ?: ossImageUrl($item['oss_path'], 'original')) ?>"
                                            title="复制链接">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                    <form method="POST" action="<?= url('admin', ['action' => 'media']) ?>"
                                          class="d-inline" onsubmit="return confirm('确定删除此文件？')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="sub_action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="删除">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 分页 -->
                <?php if ($pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= url('admin', array_merge(['action' => 'media', 'page' => $currentPage - 1], array_filter($filters))) ?>">上一页</a>
                            </li>
                            <?php for ($p = max(1, $currentPage - 3); $p <= min($pages, $currentPage + 3); $p++): ?>
                                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('admin', array_merge(['action' => 'media', 'page' => $p], array_filter($filters))) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $currentPage >= $pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= url('admin', array_merge(['action' => 'media', 'page' => $currentPage + 1], array_filter($filters))) ?>">下一页</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
            <?php endif; // $ossConfigured ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 复制 URL 到剪贴板
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
});
</script>
