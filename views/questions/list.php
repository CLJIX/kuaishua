<div class="container my-4">

    <!-- 页面标题 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-list-ul"></i> 题目列表</h3>
        <span class="text-muted">共 <?= (int)$result['total'] ?> 道题目</span>
    </div>

    <!-- 筛选栏 -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="questions">
                <input type="hidden" name="action" value="list">
                <div class="row g-2 align-items-end">
                    <!-- 分类筛选 -->
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small text-muted">分类</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">全部分类</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"
                                    <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 题目类型筛选 -->
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small text-muted">类型</label>
                        <select name="question_type" class="form-select form-select-sm">
                            <option value="">全部类型</option>
                            <option value="single" <?= ($filters['question_type'] ?? '') === 'single' ? 'selected' : '' ?>>单选题</option>
                            <option value="multiple" <?= ($filters['question_type'] ?? '') === 'multiple' ? 'selected' : '' ?>>多选题</option>
                        </select>
                    </div>

                    <!-- 难度筛选 -->
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small text-muted">难度</label>
                        <select name="difficulty" class="form-select form-select-sm">
                            <option value="">全部难度</option>
                            <option value="1" <?= ($filters['difficulty'] ?? '') == '1' ? 'selected' : '' ?>>简单</option>
                            <option value="2" <?= ($filters['difficulty'] ?? '') == '2' ? 'selected' : '' ?>>中等</option>
                            <option value="3" <?= ($filters['difficulty'] ?? '') == '3' ? 'selected' : '' ?>>困难</option>
                        </select>
                    </div>

                    <!-- 标签筛选 -->
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small text-muted">标签</label>
                        <select name="tag_id" class="form-select form-select-sm">
                            <option value="">全部标签</option>
                            <?php foreach ($tags as $tag): ?>
                                <option value="<?= (int)$tag['id'] ?>"
                                    <?= ($filters['tag_id'] ?? '') == $tag['id'] ? 'selected' : '' ?>>
                                    <?= e($tag['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 关键词搜索 -->
                    <div class="col-sm-8 col-md-2">
                        <label class="form-label small text-muted">关键词</label>
                        <input type="text" name="keyword" class="form-control form-control-sm"
                               placeholder="搜索题目..."
                               value="<?= e($filters['keyword'] ?? '') ?>">
                    </div>

                    <!-- 操作按钮 -->
                    <div class="col-sm-4 col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                <i class="bi bi-search"></i> 搜索
                            </button>
                            <a href="<?= url('questions', ['action' => 'list']) ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-counterclockwise"></i> 重置
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 题目列表 -->
    <?php if (!empty($result['items'])): ?>
        <div class="list-group mb-4">
            <?php foreach ($result['items'] as $index => $item): ?>
                <a href="<?= url('questions', ['action' => 'detail', 'id' => $item['id']]) ?>"
                   class="list-group-item list-group-item-action border-0 shadow-sm mb-2 rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-start">
                            <!-- 题号 -->
                            <span class="badge bg-primary rounded-pill me-3 mt-1" style="min-width: 2.5rem;">
                                #<?= (int)$item['id'] ?>
                            </span>
                            <div>
                                <!-- 题面预览（截取前100字，去除 Markdown/HTML 语法） -->
                                <h6 class="mb-1"><?= e(mdExcerpt($item['content'], 100)) ?></h6>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <!-- 类型标签 -->
                                    <?php if ($item['question_type'] === 'single'): ?>
                                        <span class="badge bg-info">单选题</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">多选题</span>
                                    <?php endif; ?>

                                    <!-- 难度标签 -->
                                    <span class="badge badge-difficulty-<?= (int)$item['difficulty'] ?>">
                                        <?php
                                        $diffMap = [1 => '简单', 2 => '中等', 3 => '困难'];
                                        echo $diffMap[$item['difficulty']] ?? '未知';
                                        ?>
                                    </span>

                                    <!-- 所属分类 -->
                                    <?php if (!empty($item['category_name'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-folder"></i> <?= e($item['category_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- 右侧箭头 -->
                        <i class="bi bi-chevron-right text-muted mt-2"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- 分页组件 -->
        <?php if ($result['pages'] > 1): ?>
            <nav aria-label="题目分页">
                <ul class="pagination justify-content-center flex-wrap">
                    <!-- 上一页 -->
                    <li class="page-item <?= $result['current_page'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="<?= url('questions', array_merge(['action' => 'list', 'page' => $result['current_page'] - 1], array_filter($filters))) ?>">
                            <i class="bi bi-chevron-left"></i> 上一页
                        </a>
                    </li>

                    <!-- 页码链接 -->
                    <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                        <li class="page-item <?= $p === $result['current_page'] ? 'active' : '' ?>">
                            <a class="page-link"
                               href="<?= url('questions', array_merge(['action' => 'list', 'page' => $p], array_filter($filters))) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- 下一页 -->
                    <li class="page-item <?= $result['current_page'] >= $result['pages'] ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="<?= url('questions', array_merge(['action' => 'list', 'page' => $result['current_page'] + 1], array_filter($filters))) ?>">
                            下一页 <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <p class="text-center text-muted small">
                第 <?= (int)$result['current_page'] ?> / <?= (int)$result['pages'] ?> 页，共 <?= (int)$result['total'] ?> 题
            </p>
        <?php endif; ?>

    <?php else: ?>
        <!-- 空列表提示 -->
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-2 mb-0">暂无符合条件的题目</p>
            <a href="<?= url('questions', ['action' => 'list']) ?>" class="btn btn-outline-primary btn-sm mt-3">
                <i class="bi bi-arrow-counterclockwise"></i> 清除筛选条件
            </a>
        </div>
    <?php endif; ?>

</div>
