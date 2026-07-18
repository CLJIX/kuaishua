<?php
/**
 * 管理后台 - 题目管理列表
 * 支持搜索、筛选、分页，以及编辑和删除操作
 *
 * @var array $result     分页结果 ['items', 'total', 'pages', 'current_page']
 * @var array $categories 所有分类
 * @var array $filters    当前筛选条件 ['keyword', 'category_id', 'difficulty']
 */

// 难度映射
$difficultyMap = [1 => '简单', 2 => '中等', 3 => '困难'];
$difficultyColors = [1 => 'success', 2 => 'warning', 3 => 'danger'];
$typeMap = ['single' => '单选', 'multiple' => '多选', 'judge' => '判断', 'fill' => '填空'];
// 实例化题目模型，用于加载选项数据（显示正确答案）
$_questionModel = new QuestionModel();
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">
                    <i class="bi bi-list-task"></i> 题目管理
                </h3>
                <a href="<?= url('admin', ['action' => 'question_edit']) ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> 添加题目
                </a>
            </div>

            <!-- 搜索筛选栏 -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" action="<?= url('admin', ['action' => 'questions']) ?>" class="row g-2 align-items-end">
                        <input type="hidden" name="page" value="admin">
                        <input type="hidden" name="action" value="questions">
                        <!-- 搜索关键词 -->
                        <div class="col-md-4">
                            <label class="form-label small">关键词</label>
                            <input type="text" name="keyword" class="form-control"
                                   placeholder="搜索题面..."
                                   value="<?= e($filters['keyword'] ?? '') ?>">
                        </div>
                        <!-- 分类筛选 -->
                        <div class="col-md-3">
                            <label class="form-label small">分类</label>
                            <select name="category_id" class="form-select">
                                <option value="">全部分类</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"
                                    <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- 难度筛选 -->
                        <div class="col-md-2">
                            <label class="form-label small">难度</label>
                            <select name="difficulty" class="form-select">
                                <option value="">全部难度</option>
                                <?php foreach ($difficultyMap as $level => $label): ?>
                                <option value="<?= $level ?>"
                                    <?= ($filters['difficulty'] ?? '') == $level ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- 按钮 -->
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary me-1">
                                <i class="bi bi-search"></i> 筛选
                            </button>
                            <a href="<?= url('admin', ['action' => 'questions']) ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> 重置
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 题目表格 -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">ID</th>
                                    <th>题面</th>
                                    <th style="width:80px">类型</th>
                                    <th style="width:100px">正确答案</th>
                                    <th style="width:80px">难度</th>
                                    <th style="width:120px">分类</th>
                                    <th style="width:130px">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($result['items'])): ?>
                                    <?php foreach ($result['items'] as $item): ?>
                                    <tr>
                                        <td><?= (int)$item['id'] ?></td>
                                        <td>
                                            <?= e(mb_substr(strip_tags($item['content']), 0, 60)) ?>
                                            <?= mb_strlen(strip_tags($item['content'])) > 60 ? '...' : '' ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= e($typeMap[$item['question_type']] ?? $item['question_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($item['question_type'] === 'fill'): ?>
                                                <span class="badge bg-success"><?= e(mb_substr($item['standard_answer'] ?? '', 0, 20)) ?></span>
                                            <?php else: ?>
                                                <?php
                                                // 加载该题的正确选项标签
                                                $_options = $_questionModel->getOptions((int)$item['id']);
                                                $_correctLabels = [];
                                                foreach ($_options as $_opt) {
                                                    if ($_opt['is_correct']) {
                                                        $_correctLabels[] = $_opt['option_label'];
                                                    }
                                                }
                                                ?>
                                                <span class="badge bg-success"><?= e(implode(', ', $_correctLabels)) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php $d = (int)($item['difficulty'] ?? 1); ?>
                                            <span class="badge bg-<?= $difficultyColors[$d] ?? 'secondary' ?>">
                                                <?= e($difficultyMap[$d] ?? '未知') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= e($item['category_name'] ?? '-') ?>
                                        </td>
                                        <td>
                                            <!-- 编辑按钮 -->
                                            <a href="<?= url('admin', ['action' => 'question_edit', 'id' => $item['id']]) ?>"
                                               class="btn btn-sm btn-outline-primary me-1" title="编辑">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <!-- 删除按钮（POST 表单） -->
                                            <form method="POST"
                                                  action="<?= url('admin', ['action' => 'question_delete']) ?>"
                                                  class="d-inline"
                                                  onsubmit="return confirm('确定删除该题目吗？');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="删除">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            暂无题目数据
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 分页组件 -->
                <?php if (($result['pages'] ?? 1) > 1): ?>
                <div class="card-footer bg-white">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php
                            $currentPage = (int)($result['current_page'] ?? 1);
                            $totalPages  = (int)($result['pages'] ?? 1);
                            ?>
                            <!-- 上一页 -->
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="<?= url('admin', ['action' => 'questions', 'page' => $currentPage - 1] + array_filter($filters)) ?>">
                                    &laquo;
                                </a>
                            </li>
                            <!-- 页码 -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="<?= url('admin', ['action' => 'questions', 'page' => $i] + array_filter($filters)) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            <!-- 下一页 -->
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="<?= url('admin', ['action' => 'questions', 'page' => $currentPage + 1] + array_filter($filters)) ?>">
                                    &raquo;
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

            <!-- 统计信息 -->
            <div class="text-muted small mt-2">
                共 <?= (int)($result['total'] ?? 0) ?> 道题目，
                第 <?= (int)($result['current_page'] ?? 1) ?> / <?= (int)($result['pages'] ?? 1) ?> 页
            </div>
        </div>
    </div>
</div>
