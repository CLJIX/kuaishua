<?php
/**
 * 管理后台 Flash 消息渲染片段
 * 在右侧内容区顶部显示通知（不影响侧边栏位置）
 * 依赖 header.php 中预设的 $flash 变量
 */
if (!empty($flash)):
?>
<div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= $flash['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
