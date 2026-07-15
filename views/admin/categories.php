<?php
/**
 * 管理后台 - 分类管理页面
 * 支持树形展示、新增、编辑、删除分类
 *
 * @var array      $categories   所有分类（平铺列表）
 * @var array      $tree         树形分类结构
 * @var array|null $editCategory 编辑模式时的分类数据，新增时为 null
 */

// 是否处于编辑模式
$isEditing = ($editCategory !== null);
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
                <i class="bi bi-folder2-open"></i> 分类管理
            </h3>

            <!-- 分类列表 -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">ID</th>
                                    <th>名称</th>
                                    <th>描述</th>
                                    <th style="width:80px">排序</th>
                                    <th style="width:80px">题目数</th>
                                    <th style="width:130px">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                /**
                                 * 递归渲染树形分类（子分类缩进显示）
                                 */
                                function renderCategoryTree($tree, $categories, $editCategory, $level = 0) {
                                    if (empty($tree)) {
                                        // 没有树形结构时回退到平铺列表
                                        return;
                                    }
                                    foreach ($tree as $node) {
                                        $cat = $node['category'] ?? $node;
                                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                                        $prefix = $level > 0 ? '<span class="text-muted">└ </span>' : '';
                                        ?>
                                        <tr>
                                            <td><?= (int)$cat['id'] ?></td>
                                            <td>
                                                <?= $indent ?><?= $prefix ?>
                                                <strong><?= e($cat['name']) ?></strong>
                                            </td>
                                            <td class="small text-muted"><?= e($cat['description'] ?? '') ?></td>
                                            <td><?= (int)($cat['sort_order'] ?? 0) ?></td>
                                            <td><?= (int)($cat['question_count'] ?? 0) ?></td>
                                            <td>
                                                <!-- 编辑链接 -->
                                                <a href="<?= url('admin', ['action' => 'categories', 'id' => $cat['id']]) ?>"
                                                   class="btn btn-sm btn-outline-primary me-1" title="编辑">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <!-- 删除按钮（POST 表单） -->
                                                <form method="POST"
                                                      action="<?= url('admin', ['action' => 'categories', 'sub_action' => 'delete']) ?>"
                                                      class="d-inline"
                                                      onsubmit="return confirm('确定删除分类「<?= e($cat['name']) ?>」吗？');">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="删除">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php
                                        // 递归渲染子分类
                                        if (!empty($node['children'])) {
                                            renderCategoryTree($node['children'], $categories, $editCategory, $level + 1);
                                        }
                                    }
                                }
                                ?>

                                <?php if (!empty($tree)): ?>
                                    <?php renderCategoryTree($tree, $categories, $editCategory); ?>
                                <?php elseif (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?= (int)$cat['id'] ?></td>
                                        <td><strong><?= e($cat['name']) ?></strong></td>
                                        <td class="small text-muted"><?= e($cat['description'] ?? '') ?></td>
                                        <td><?= (int)($cat['sort_order'] ?? 0) ?></td>
                                        <td><?= (int)($cat['question_count'] ?? 0) ?></td>
                                        <td>
                                            <a href="<?= url('admin', ['action' => 'categories', 'id' => $cat['id']]) ?>"
                                               class="btn btn-sm btn-outline-primary me-1" title="编辑">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST"
                                                  action="<?= url('admin', ['action' => 'categories', 'sub_action' => 'delete']) ?>"
                                                  class="d-inline"
                                                  onsubmit="return confirm('确定删除分类「<?= e($cat['name']) ?>」吗？');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="删除">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            暂无分类数据
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
                        <?= $isEditing ? '编辑分类' : '新增分类' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="<?= url('admin', ['action' => 'categories', 'sub_action' => $isEditing ? 'update' : 'create']) ?>">
                        <?= csrfField() ?>

                        <?php if ($isEditing): ?>
                        <input type="hidden" name="id" value="<?= (int)$editCategory['id'] ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <!-- 名称 -->
                            <div class="col-md-4">
                                <label class="form-label">分类名称</label>
                                <input type="text" name="name" class="form-control"
                                       placeholder="输入分类名称" required
                                       value="<?= e($isEditing ? ($editCategory['name'] ?? '') : '') ?>">
                            </div>

                            <!-- 父分类 -->
                            <div class="col-md-3">
                                <label class="form-label">父分类</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">-- 顶级分类 --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <?php
                                        // 编辑时不能选择自身作为父分类
                                        if ($isEditing && (int)$cat['id'] === (int)$editCategory['id']) { continue; }
                                        ?>
                                        <option value="<?= (int)$cat['id'] ?>"
                                            <?= ($isEditing && (int)($editCategory['parent_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                                            <?= e($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 描述 -->
                            <div class="col-md-3">
                                <label class="form-label">描述</label>
                                <input type="text" name="description" class="form-control"
                                       placeholder="分类描述（可选）"
                                       value="<?= e($isEditing ? ($editCategory['description'] ?? '') : '') ?>">
                            </div>

                            <!-- 排序权重 -->
                            <div class="col-md-2">
                                <label class="form-label">排序权重</label>
                                <input type="number" name="sort_order" class="form-control"
                                       placeholder="0"
                                       value="<?= e($isEditing ? (string)($editCategory['sort_order'] ?? 0) : '0') ?>">
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i>
                                <?= $isEditing ? '保存修改' : '创建分类' ?>
                            </button>
                            <?php if ($isEditing): ?>
                            <a href="<?= url('admin', ['action' => 'categories']) ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> 取消编辑
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
