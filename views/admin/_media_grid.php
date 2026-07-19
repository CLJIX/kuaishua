<?php
/**
 * 媒体库网格与分页（独立页 AJAX 局部视图）
 *
 * 可用变量：
 * - $result  (array): 分页查询结果
 * - $stats   (array): 统计信息
 * - $filters (array): 当前筛选条件
 */
$items       = $result['items']        ?? [];
$total       = $result['total']        ?? 0;
$pages       = $result['pages']        ?? 0;
$currentPage = $result['current_page'] ?? 1;

$totalCount = $stats['total_count'] ?? 0;
$totalSize  = $stats['total_size']  ?? 0;
$monthCount = $stats['month_count'] ?? 0;

// 格式化文件大小
if (!function_exists('_formatSizeGrid')) {
    function _formatSizeGrid($bytes): string {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}

if (!function_exists('_questionTitleGrid')) {
    function _questionTitleGrid($content): string {
        if (empty($content)) return '';
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        return mb_substr(trim($text), 0, 30);
    }
}
?>
<div data-total-count="<?= (int)$totalCount ?>" data-total-size="<?= (int)$totalSize ?>" data-month-count="<?= (int)$monthCount ?>">
    <?php if (empty($items)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 暂无媒体文件。在题目编辑器中粘贴图片即可自动上传。
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($items as $item):
                $questionTitle = _questionTitleGrid($item['question_content'] ?? '');
                $displayTitle = $questionTitle ?: '题目 #' . (int)$item['question_id'];
                $originalUrl = e($item['cdn_url'] ?: ossImageUrl($item['oss_path'], 'original'));
            ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 media-card cursor-pointer" style="cursor:pointer" data-id="<?= (int)$item['id'] ?>"
                         data-original-url="<?= $originalUrl ?>"
                         data-file-name="<?= e($item['file_name']) ?>"
                         data-file-size="<?= (int)$item['file_size'] ?>"
                         data-mime-type="<?= e($item['mime_type']) ?>"
                         data-uploader="<?= e($item['uploader_name'] ?? '') ?>"
                         data-created-at="<?= e($item['created_at']) ?>"
                         data-biz-type="<?= e($item['biz_type']) ?>"
                         data-question-id="<?= (int)($item['question_id'] ?? 0) ?>"
                         data-question-title="<?= e($questionTitle) ?>">
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
                                <?= _formatSizeGrid($item['file_size']) ?> /
                                <?= date('m-d H:i', strtotime($item['created_at'])) ?>
                            </div>
                            <?php if (!empty($item['question_id'])): ?>
                                <div class="text-truncate" style="font-size:0.75rem">
                                    <a href="<?= url('admin', ['action' => 'question_edit', 'id' => (int)$item['question_id']]) ?>"
                                       class="text-decoration-none" title="<?= e($questionTitle) ?>">
                                        <i class="bi bi-link-45deg"></i> <?= e($displayTitle) ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-muted" style="font-size:0.75rem">
                                    <i class="bi bi-slash-circle"></i> 未关联题目
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer p-2 bg-white border-top-0 d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-fill copy-url-btn"
                                    data-url="<?= $originalUrl ?>"
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
                        <a class="page-link" href="#" data-page="<?= $currentPage - 1 ?>"<?= $currentPage <= 1 ? ' tabindex="-1"' : '' ?>>上一页</a>
                    </li>
                    <?php for ($p = max(1, $currentPage - 3); $p <= min($pages, $currentPage + 3); $p++): ?>
                        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="#" data-page="<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $currentPage >= $pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="#" data-page="<?= $currentPage + 1 ?>"<?= $currentPage >= $pages ? ' tabindex="-1"' : '' ?>>下一页</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
