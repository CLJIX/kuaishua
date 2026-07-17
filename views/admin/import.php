<?php
/**
 * 管理后台 - CSV 批量导入页面
 * 上传 CSV 文件批量导入题目
 *
 * @var array|null $importResult 导入结果 ['total', 'success', 'failed', 'errors']，可能为 null
 */
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
                <i class="bi bi-upload"></i> CSV 批量导入
            </h3>

            <div class="row g-4">
                <!-- 上传表单 -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> 上传文件</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST"
                                  action="<?= url('admin', ['action' => 'import']) ?>"
                                  enctype="multipart/form-data">
                                <?= csrfField() ?>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">选择 CSV 文件</label>
                                    <input type="file" name="csv_file" class="form-control"
                                           accept=".csv,.txt" required>
                                    <div class="form-text">仅支持 .csv 或 .txt 格式，文件编码建议使用 UTF-8</div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i> 开始导入
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- CSV 格式说明 -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> CSV 格式说明</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">每行一道题目，共 <strong>11 列</strong>（逗号分隔）：</p>
                            <ol class="small mb-3">
                                <li>题目类型（<code>single</code> 单选 / <code>multiple</code> 多选）</li>
                                <li>题面</li>
                                <li>选项 A</li>
                                <li>选项 B</li>
                                <li>选项 C</li>
                                <li>选项 D</li>
                                <li>正确答案（单选如 <code>A</code>；多选须用英文逗号分隔，如 <code>A,B,C</code>）</li>
                                <li>解析</li>
                                <li>难度（1 简单 / 2 中等 / 3 困难）</li>
                                <li>分类名称（可留空）</li>
                                <li>标签（多个标签用英文逗号分隔，<strong>整个字段须用双引号包裹</strong>，如 <code>"HTML,CSS,JS"</code>；可留空）</li>
                            </ol>
                            <div class="alert alert-warning py-2 px-3 small mb-3">
                                <strong><i class="bi bi-exclamation-triangle"></i> 重要提示：</strong>
                                <ul class="mb-0 ps-3">
                                    <li>多选题的正确答案<strong>必须用英文逗号分隔</strong>各选项字母，如 <code>A,B,C</code></li>
                                    <li>若题面、选项、解析或标签中包含逗号，该字段<strong>必须用双引号包裹</strong></li>
                                    <li>多个标签用英文逗号分隔，且<strong>整个标签字段必须用双引号包裹</strong>，如 <code>"HTML,CSS,JavaScript"</code></li>
                                </ul>
                            </div>
                            <div class="bg-light p-2 rounded small">
                                <strong>示例行：</strong><br>
                                <code>single,PHP中哪个函数用于输出字符串?,echo,print,var_dump,printf,A,echo是最常用的输出函数,1,PHP基础,"PHP,基础"</code>
                            </div>
                            <hr>
                            <a href="assets/csv_template.csv" download="题目导入模板.csv" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-download"></i> 下载 CSV 导入模板
                            </a>
                            <p class="form-text mt-1 mb-0">模板包含多道示例题目（含单选/多选/判断/HTML题面/多标签等），可直接编辑后上传导入</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 导入结果展示 -->
            <?php if ($importResult !== null): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> 导入结果</h5>
                </div>
                <div class="card-body">
                    <!-- 统计信息 -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1"><?= (int)$importResult['total'] ?></h4>
                                <span class="text-muted">总计</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-success"><?= (int)$importResult['success'] ?></h4>
                                <span class="text-muted">成功</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-danger"><?= (int)$importResult['failed'] ?></h4>
                                <span class="text-muted">失败</span>
                            </div>
                        </div>
                    </div>

                    <!-- 错误详情 -->
                    <?php if (!empty($importResult['errors'])): ?>
                    <div class="mt-3">
                        <h6 class="text-danger">
                            <i class="bi bi-exclamation-triangle"></i> 错误详情
                        </h6>
                        <div class="bg-light p-3 rounded small" style="max-height:300px;overflow-y:auto">
                            <ul class="mb-0">
                                <?php foreach ($importResult['errors'] as $error): ?>
                                <li class="text-danger"><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
