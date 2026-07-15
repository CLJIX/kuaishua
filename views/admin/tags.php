<?php
/**
 * 管理后台 - 标签管理页面
 * 支持标签列表、新增、编辑、删除
 *
 * @var array      $tags    所有标签数组
 * @var array|null $editTag 编辑模式时的标签数据，新增时为 null
 */

// 是否处于编辑模式
$isEditing = ($editTag !== null);
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- 左侧管理侧边栏 -->
        <div class="col-md-3 mb-3">
            <?php include __DIR__ . '/_sidebar.php'; ?>
        </div>

        <!-- 右侧内容区 -->
        <div class="col-md-9">
            <?php include __DIR__ . '/_flash.php'; ?>
            <h3 class="mb-4">
                <i class="bi bi-tags"></i> 标签管理
            </h3>

            <!-- 标签列表 -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">ID</th>
                                    <th>名称</th>
                                    <th style="width:120px">关联题目数</th>
                                    <th style="width:130px">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tags)): ?>
                                    <?php foreach ($tags as $tag): ?>
                                    <tr>
                                        <td><?= (int)$tag['id'] ?></td>
                                        <td>
                                            <span class="badge bg-info text-dark fs-6">
                                                <?= e($tag['name']) ?>
                                            </span>
                                        </td>
                                        <td><?= (int)($tag['question_count'] ?? 0) ?></td>
                                        <td>
                                            <!-- 编辑链接 -->
                                            <a href="<?= url('admin', ['action' => 'tags', 'id' => $tag['id']]) ?>"
                                               class="btn btn-sm btn-outline-primary me-1" title="编辑">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <!-- 删除按钮（POST 表单） -->
                                            <form method="POST"
                                                  action="<?= url('admin', ['action' => 'tags', 'sub_action' => 'delete']) ?>"
                                                  class="d-inline"
                                                  onsubmit="return confirm('确定删除标签「<?= e($tag['name']) ?>」吗？');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int)$tag['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="删除">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            暂无标签数据
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 新增/编辑表单 -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-<?= $isEditing ? 'pencil-square' : 'plus-circle' ?>"></i>
                        <?= $isEditing ? '编辑标签' : '新增标签' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="<?= url('admin', ['action' => 'tags', 'sub_action' => $isEditing ? 'update' : 'create']) ?>">
                        <?= csrfField() ?>

                        <?php if ($isEditing): ?>
                        <input type="hidden" name="id" value="<?= (int)$editTag['id'] ?>">
                        <?php endif; ?>

                        <div class="row g-3 align-items-end">
                            <!-- 标签名称 -->
                            <div class="col-md-6">
                                <label class="form-label">标签名称</label>
                                <input type="text" name="name" class="form-control"
                                       placeholder="输入标签名称" required
                                       value="<?= e($isEditing ? ($editTag['name'] ?? '') : '') ?>">
                            </div>

                            <!-- 提交按钮 -->
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-check-lg"></i>
                                    <?= $isEditing ? '保存修改' : '创建标签' ?>
                                </button>
                                <?php if ($isEditing): ?>
                                <a href="<?= url('admin', ['action' => 'tags']) ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i> 取消编辑
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
