<?php
/**
 * 管理后台控制器
 * 所有管理操作均需管理员权限
 * 包含：数据概览、题目管理（增删改查）、CSV批量导入、分类管理、标签管理
 */
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../models/User.php';

class AdminController {
    private QuestionModel $questionModel;
    private CategoryModel $categoryModel;
    private TagModel      $tagModel;
    private UserModel     $userModel;

    public function __construct() {
        // 所有管理操作均需管理员权限，非管理员将被重定向
        requireAdmin();

        $this->questionModel = new QuestionModel();
        $this->categoryModel = new CategoryModel();
        $this->tagModel      = new TagModel();
        $this->userModel     = new UserModel();
    }

    // =====================================================
    // 数据概览
    // =====================================================

    /**
     * 管理后台首页 - 数据概览
     * 展示题目总数、分类数、标签数、用户数等统计信息
     */
    public function index(): void {
        $totalQuestions = $this->questionModel->getTotalCount();
        $categories     = $this->categoryModel->getAll();
        $tags           = $this->tagModel->getAll();
        $users          = $this->userModel->getAll();

        $categoryCount = count($categories);
        $tagCount      = count($tags);
        $userCount     = count($users);

        // 渲染管理后台首页视图
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/admin/dashboard.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // =====================================================
    // 题目管理
    // =====================================================

    /**
     * 题目管理列表（支持搜索筛选与分页）
     * GET 参数：keyword, category_id, difficulty, page
     */
    public function questions(): void {
        $filters = [
            'category_id' => getParam('category_id'),
            'difficulty'  => getParam('difficulty'),
            'keyword'     => getParam('keyword'),
        ];

        $page   = max(1, (int) getParam('page', 1));
        $result = $this->questionModel->getList($filters, $page, 20);

        $categories = $this->categoryModel->getAll();

        // 渲染题目管理列表视图
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/admin/questions.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * 新增/编辑题目
     * GET  - 渲染表单（编辑模式时加载现有题目数据）
     * POST - 保存数据（调用 create 或 update）
     */
    public function question_edit(): void {
        $id        = (int) getParam('id', 0);
        $isEdit    = ($id > 0);
        $question  = null;

        // 编辑模式：加载现有数据
        if ($isEdit) {
            $question = $this->questionModel->findById($id);
            if (!$question) {
                setFlash('danger', '题目不存在');
                redirect(url('admin', ['action' => 'questions']));
                return;
            }
        }

        if (isPost()) {
            // CSRF 安全验证
            if (!verifyCsrfToken()) {
                setFlash('danger', '安全验证失败，请重试');
                redirect(url('admin', ['action' => 'question_edit', 'id' => $id]));
                return;
            }

            // ---- 收集题目主数据 ----
            $data = [
                'question_type' => trim(postParam('question_type', 'single')),
                'content'       => trim(postParam('content', '')),
                'explanation'   => trim(postParam('explanation', '')),
                'difficulty'    => (int) postParam('difficulty', 1),
                'category_id'   => postParam('category_id') ? (int) postParam('category_id') : null,
                'tags'          => [],
            ];

            // 填空题：收集标准答案
            if ($data['question_type'] === 'fill') {
                $data['standard_answer'] = trim(postParam('standard_answer', ''));
            }

            // 处理标签（前端提交逗号分隔的标签ID字符串）
            $tagInput = postParam('tags', '');
            if (!empty($tagInput)) {
                $data['tags'] = array_filter(array_map('intval', explode(',', $tagInput)));
            }

            // ---- 收集选项数据 ----
            $optionLabels = ['A', 'B', 'C', 'D', 'E', 'F'];
            $options      = [];

            // 判断题：强制固定选项为 A=对 B=错
            if ($data['question_type'] === 'judge') {
                $correctAnswers = array_map('trim', explode(',', strtoupper(postParam('correct_answer', ''))));
                $options = [
                    ['option_label' => 'A', 'option_text' => '对', 'is_correct' => in_array('A', $correctAnswers, true) ? 1 : 0],
                    ['option_label' => 'B', 'option_text' => '错', 'is_correct' => in_array('B', $correctAnswers, true) ? 1 : 0],
                ];
            } elseif ($data['question_type'] === 'fill') {
                // 填空题：无选项
                $options = [];
            } else {
                foreach ($optionLabels as $label) {
                    $optionText = trim(postParam('option_' . $label, ''));
                    if ($optionText === '') { continue; }

                    // 正确答案：单选为单个字母，多选为逗号分隔
                    $correctAnswers = array_map('trim', explode(',', strtoupper(postParam('correct_answer', ''))));
                    $isCorrect      = in_array($label, $correctAnswers, true) ? 1 : 0;

                    $options[] = [
                        'option_label' => $label,
                        'option_text'  => $optionText,
                        'is_correct'   => $isCorrect,
                    ];
                }
            }

            // ---- 基础验证 ----
            $errors = [];
            if (empty($data['content'])) {
                $errors[] = '题目内容不能为空';
            }
            if ($data['question_type'] === 'fill') {
                if (empty($data['standard_answer'])) {
                    $errors[] = '填空题必须设置标准答案';
                }
            } else {
                if (empty($options)) {
                    $errors[] = '请至少添加一个选项';
                }
                // 检查是否设置了正确答案
                $hasCorrect = false;
                foreach ($options as $opt) {
                    if ($opt['is_correct']) { $hasCorrect = true; break; }
                }
                if (!$hasCorrect) {
                    $errors[] = '请设置正确答案';
                }
            }

            if (!empty($errors)) {
                setFlash('danger', implode('<br>', $errors));
                redirect(url('admin', ['action' => 'question_edit', 'id' => $id]));
                return;
            }

            // ---- 保存 ----
            try {
                if ($isEdit) {
                    $this->questionModel->update($id, $data, $options);
                    setFlash('success', '题目已更新');
                } else {
                    $newId = $this->questionModel->create($data, $options);
                    setFlash('success', '题目已创建（ID: ' . $newId . '）');
                }
                redirect(url('admin', ['action' => 'questions']));
            } catch (Exception $e) {
                setFlash('danger', '保存失败：' . e($e->getMessage()));
                redirect(url('admin', ['action' => 'question_edit', 'id' => $id]));
            }
        } else {
            // 渲染编辑表单视图
            $categories = $this->categoryModel->getAll();
            $tags       = $this->tagModel->getAll();

            require_once __DIR__ . '/../views/layouts/header.php';
            require_once __DIR__ . '/../views/admin/question_edit.php';
            require_once __DIR__ . '/../views/layouts/footer.php';
        }
    }

    /**
     * 删除题目
     * 必须为 POST 请求并通过 CSRF 验证
     */
    public function question_delete(): void {
        if (!isPost() || !verifyCsrfToken()) {
            setFlash('danger', '无效的请求');
            redirect(url('admin', ['action' => 'questions']));
            return;
        }

        $id = (int) postParam('id', 0);
        if ($id <= 0) {
            setFlash('warning', '题目参数无效');
            redirect(url('admin', ['action' => 'questions']));
            return;
        }

        if ($this->questionModel->delete($id)) {
            setFlash('success', '题目已删除');
        } else {
            setFlash('danger', '删除失败，请重试');
        }

        redirect(url('admin', ['action' => 'questions']));
    }

    // =====================================================
    // CSV 批量导入
    // =====================================================

    /**
     * CSV 批量导入题目
     * GET  - 显示上传表单
     * POST - 处理文件上传、解析 CSV、调用 batchImport
     *
     * CSV 格式约定（共11列）：
     * 题目类型(single/multiple), 题面, 选项A, 选项B, 选项C, 选项D,
     * 正确答案(A/B/C/D，多选逗号分隔), 解析, 难度(1-3),
     * 分类名称(可选), 标签(逗号分隔,可选)
     */
    public function import(): void {
        if (isPost()) {
            // CSRF 安全验证
            if (!verifyCsrfToken()) {
                setFlash('danger', '安全验证失败，请重试');
                redirect(url('admin', ['action' => 'import']));
                return;
            }

            // ---- 文件校验 ----
            $file = $_FILES['csv_file'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                setFlash('danger', '请上传 CSV 文件');
                redirect(url('admin', ['action' => 'import']));
                return;
            }

            // 检查文件类型（允许 .csv 和 .txt）
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv', 'txt'], true)) {
                setFlash('danger', '仅支持 .csv 或 .txt 格式文件');
                redirect(url('admin', ['action' => 'import']));
                return;
            }

            // ---- 读取并处理编码 ----
            $filePath = $file['tmp_name'];
            $content  = file_get_contents($filePath);

            // 去除 UTF-8 BOM 头
            if (str_starts_with($content, "\xEF\xBB\xBF")) {
                $content = substr($content, 3);
            }

            // 检测编码：若非 UTF-8 则假定为 GBK，进行转换
            $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312'], true);
            if ($encoding && strtoupper($encoding) !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            // 写回临时文件以便 fgetcsv 读取
            $tmpFile = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tmpFile, $content);

            // ---- 逐行解析 CSV ----
            $handle = fopen($tmpFile, 'r');
            if (!$handle) {
                setFlash('danger', '无法打开文件');
                redirect(url('admin', ['action' => 'import']));
                return;
            }

            $questions  = [];
            $rowNum     = 0;
            $parseErrors= [];

            // 预加载分类和标签用于名称映射（减少数据库查询）
            $allCategories = $this->categoryModel->getAll();
            $categoryMap   = []; // name => id
            foreach ($allCategories as $cat) {
                $categoryMap[$cat['name']] = $cat['id'];
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;

                // 跳过空行
                if (count($row) === 1 && trim($row[0]) === '') { continue; }

                // 第一行如果是表头则跳过（检测第一列是否包含 single/multiple/judge/fill）
                if ($rowNum === 1 && !in_array(strtolower(trim($row[0])), ['single', 'multiple', 'judge', 'fill'], true)) {
                    continue;
                }

                // 至少需要 7 列（题目类型、题面、A/B/C/D、正确答案）
                if (count($row) < 7) {
                    $parseErrors[] = "第 {$rowNum} 行：列数不足（至少需要 7 列，当前 " . count($row) . " 列）";
                    continue;
                }

                $questionType = strtolower(trim($row[0]));
                $contentText  = trim($row[1]);
                $optA         = trim($row[2] ?? '');
                $optB         = trim($row[3] ?? '');
                $optC         = trim($row[4] ?? '');
                $optD         = trim($row[5] ?? '');
                $correctStr   = strtoupper(trim($row[6]));
                $explanation  = trim($row[7] ?? '');
                $difficulty   = (int) ($row[8] ?? 1);
                $categoryName = trim($row[9] ?? '');
                // 标签列：收集索引10及之后的所有列（处理未用双引号包裹的多标签被CSV拆分为多列的情况）
                $tagParts = [];
                for ($i = 10; $i < count($row); $i++) {
                    $part = trim($row[$i]);
                    if ($part !== '') {
                        $tagParts[] = $part;
                    }
                }
                $tagNamesStr = implode(',', $tagParts);

                // 校验题目类型
                if (!in_array($questionType, ['single', 'multiple', 'judge', 'fill'], true)) {
                    $parseErrors[] = "第 {$rowNum} 行：题目类型无效（{$questionType}），应为 single、multiple、judge 或 fill";
                    continue;
                }

                // 校验难度范围
                if ($difficulty < 1 || $difficulty > 3) {
                    $difficulty = 1;
                }

                // 构建选项数组
                $options = [];
                $standardAnswer = null;

                if ($questionType === 'fill') {
                    // 填空题：第7列为标准答案文本
                    $standardAnswer = trim($row[6]);
                    if ($standardAnswer === '') {
                        $parseErrors[] = "第 {$rowNum} 行：填空题必须设置标准答案";
                        continue;
                    }
                } elseif ($questionType === 'judge') {
                    // 判断题：强制固定选项为 A=对 B=错
                    $correctAnswers = array_map('trim', explode(',', $correctStr));
                    $options = [
                        ['option_label' => 'A', 'option_text' => '对', 'is_correct' => in_array('A', $correctAnswers, true) ? 1 : 0],
                        ['option_label' => 'B', 'option_text' => '错', 'is_correct' => in_array('B', $correctAnswers, true) ? 1 : 0],
                    ];
                } else {
                    // 单选/多选：从选项列构建
                    $correctAnswers = array_map('trim', explode(',', $correctStr));
                    foreach (['A' => $optA, 'B' => $optB, 'C' => $optC, 'D' => $optD] as $label => $text) {
                        if ($text === '') { continue; }
                        $options[] = [
                            'option_label' => $label,
                            'option_text'  => $text,
                            'is_correct'   => in_array($label, $correctAnswers, true) ? 1 : 0,
                        ];
                    }
                }

                // 检查是否设置了有效的正确答案（填空题已在上面对检查）
                if ($questionType !== 'fill') {
                    $hasCorrect = false;
                    foreach ($options as $opt) {
                        if ($opt['is_correct']) { $hasCorrect = true; break; }
                    }
                    if (!$hasCorrect) {
                        $parseErrors[] = "第 {$rowNum} 行：未设置有效的正确答案";
                        continue;
                    }
                }

                // 处理分类（按名称查找或自动创建）
                $categoryId = null;
                if (!empty($categoryName)) {
                    if (isset($categoryMap[$categoryName])) {
                        $categoryId = $categoryMap[$categoryName];
                    } else {
                        // 自动创建分类
                        try {
                            $categoryId = $this->categoryModel->create($categoryName, null, '', 0);
                            $categoryMap[$categoryName] = $categoryId;
                        } catch (Exception $e) {
                            $parseErrors[] = "第 {$rowNum} 行：分类创建失败（{$categoryName}）";
                        }
                    }
                }

                // 处理标签（按名称查找或自动创建）
                $tagIds = [];
                if (!empty($tagNamesStr)) {
                    $tagNames = array_filter(array_map('trim', explode(',', $tagNamesStr)));
                    foreach ($tagNames as $tagName) {
                        $existingTag = $this->tagModel->findByName($tagName);
                        if ($existingTag) {
                            $tagIds[] = $existingTag['id'];
                        } else {
                            try {
                                $tagIds[] = $this->tagModel->create($tagName);
                            } catch (Exception $e) {
                                // 标签创建失败不阻断题目导入
                            }
                        }
                    }
                }

                $questions[] = [
                    'question_type'   => $questionType,
                    'content'         => $contentText,
                    'explanation'     => $explanation,
                    'standard_answer' => $standardAnswer,
                    'difficulty'      => $difficulty,
                    'category_id'     => $categoryId,
                    'tags'            => $tagIds,
                    'options'         => $options,
                ];
            }

            fclose($handle);
            unlink($tmpFile); // 删除临时文件

            // ---- 批量写入数据库 ----
            if (empty($questions)) {
                setFlash('warning', '未解析到有效的题目数据');
                redirect(url('admin', ['action' => 'import']));
                return;
            }

            $importResult = $this->questionModel->batchImport($questions);

            // 合并解析错误和导入错误
            $allErrors = array_merge($parseErrors, $importResult['errors']);

            // 将结果存入 Session 供视图展示
            $_SESSION['import_result'] = [
                'total'   => count($questions),
                'success' => $importResult['success'],
                'failed'  => $importResult['failed'] + count($parseErrors),
                'errors'  => $allErrors,
            ];

            setFlash(
                $importResult['failed'] === 0 ? 'success' : 'warning',
                "导入完成：成功 {$importResult['success']} 条，失败 {$importResult['failed']} 条"
            );
            redirect(url('admin', ['action' => 'import']));
        } else {
            // GET：显示上传表单
            $importResult = $_SESSION['import_result'] ?? null;
            unset($_SESSION['import_result']); // 读取后清除

            require_once __DIR__ . '/../views/layouts/header.php';
            require_once __DIR__ . '/../views/admin/import.php';
            require_once __DIR__ . '/../views/layouts/footer.php';
        }
    }

    // =====================================================
    // 分类管理
    // =====================================================

    /**
     * 分类管理（列表 + 新增 + 编辑 + 删除）
     * 通过 GET 参数 action 区分子操作：
     * - index（默认）：列表展示
     * - create：POST 新增分类
     * - update：POST 编辑分类
     * - delete：POST 删除分类
     */
    public function categories(): void {
        $subAction = getParam('sub_action', 'list');

        if (isPost()) {
            // CSRF 安全验证
            if (!verifyCsrfToken()) {
                setFlash('danger', '安全验证失败，请重试');
                redirect(url('admin', ['action' => 'categories']));
                return;
            }

            switch ($subAction) {
                case 'create':
                    $name        = trim(postParam('name', ''));
                    $parentId    = postParam('parent_id') ? (int) postParam('parent_id') : null;
                    $description = trim(postParam('description', ''));
                    $sortOrder   = (int) postParam('sort_order', 0);

                    if (empty($name)) {
                        setFlash('warning', '分类名称不能为空');
                    } else {
                        try {
                            $this->categoryModel->create($name, $parentId, $description, $sortOrder);
                            setFlash('success', '分类已创建');
                        } catch (Exception $e) {
                            setFlash('danger', '创建失败：' . e($e->getMessage()));
                        }
                    }
                    break;

                case 'update':
                    $id          = (int) postParam('id', 0);
                    $name        = trim(postParam('name', ''));
                    $parentId    = postParam('parent_id') ? (int) postParam('parent_id') : null;
                    $description = trim(postParam('description', ''));
                    $sortOrder   = (int) postParam('sort_order', 0);

                    if ($id <= 0 || empty($name)) {
                        setFlash('warning', '参数无效');
                    } elseif ($id === $parentId) {
                        setFlash('warning', '分类不能作为自身的子分类');
                    } else {
                        if ($this->categoryModel->update($id, $name, $parentId, $description, $sortOrder)) {
                            setFlash('success', '分类已更新');
                        } else {
                            setFlash('danger', '更新失败');
                        }
                    }
                    break;

                case 'delete':
                    $id = (int) postParam('id', 0);
                    if ($id <= 0) {
                        setFlash('warning', '参数无效');
                    } else {
                        if ($this->categoryModel->delete($id)) {
                            setFlash('success', '分类已删除');
                        } else {
                            setFlash('danger', '删除失败（该分类下可能有题目或子分类）');
                        }
                    }
                    break;
            }

            redirect(url('admin', ['action' => 'categories']));
            return;
        }

        // GET：渲染分类列表
        $categories = $this->categoryModel->getAll();
        $tree       = $this->categoryModel->getTree();

        // 编辑模式：加载指定分类数据
        $editCategory = null;
        $editId = (int) getParam('id', 0);
        if ($editId > 0) {
            $editCategory = $this->categoryModel->findById($editId);
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/admin/categories.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // =====================================================
    // 标签管理
    // =====================================================

    /**
     * 标签管理（列表 + 新增 + 编辑 + 删除）
     * 通过 GET 参数 sub_action 区分子操作：
     * - list（默认）：列表展示
     * - create：POST 新增标签
     * - update：POST 编辑标签
     * - delete：POST 删除标签
     */
    public function tags(): void {
        $subAction = getParam('sub_action', 'list');

        if (isPost()) {
            // CSRF 安全验证
            if (!verifyCsrfToken()) {
                setFlash('danger', '安全验证失败，请重试');
                redirect(url('admin', ['action' => 'tags']));
                return;
            }

            switch ($subAction) {
                case 'create':
                    $name = trim(postParam('name', ''));
                    if (empty($name)) {
                        setFlash('warning', '标签名称不能为空');
                    } elseif ($this->tagModel->findByName($name)) {
                        setFlash('warning', '标签已存在');
                    } else {
                        try {
                            $this->tagModel->create($name);
                            setFlash('success', '标签已创建');
                        } catch (Exception $e) {
                            setFlash('danger', '创建失败：' . e($e->getMessage()));
                        }
                    }
                    break;

                case 'update':
                    $id   = (int) postParam('id', 0);
                    $name = trim(postParam('name', ''));
                    if ($id <= 0 || empty($name)) {
                        setFlash('warning', '参数无效');
                    } else {
                        // 检查名称是否与其他标签冲突
                        $existing = $this->tagModel->findByName($name);
                        if ($existing && $existing['id'] !== $id) {
                            setFlash('warning', '标签名称已存在');
                        } else {
                            if ($this->tagModel->update($id, $name)) {
                                setFlash('success', '标签已更新');
                            } else {
                                setFlash('danger', '更新失败');
                            }
                        }
                    }
                    break;

                case 'delete':
                    $id = (int) postParam('id', 0);
                    if ($id <= 0) {
                        setFlash('warning', '参数无效');
                    } else {
                        if ($this->tagModel->delete($id)) {
                            setFlash('success', '标签已删除');
                        } else {
                            setFlash('danger', '删除失败');
                        }
                    }
                    break;
            }

            redirect(url('admin', ['action' => 'tags']));
            return;
        }

        // GET：渲染标签列表
        $tags = $this->tagModel->getAll();

        // 编辑模式：加载指定标签数据
        $editTag = null;
        $editId = (int) getParam('id', 0);
        if ($editId > 0) {
            $editTag = $this->tagModel->findById($editId);
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/admin/tags.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // =====================================================
    // 用户管理
    // =====================================================

    /**
     * 用户管理（列表 + 批量新建 + 修改角色 + 修改密码 + 删除）
     * 进入页面需二次验证管理员密码（通过 Bootstrap 模态弹窗）
     * 子操作：list / create / update_role / update_password / delete
     */
    public function users(): void {
        $subAction = getParam('sub_action', 'list');

        // 处理二次密码验证提交（在检查 admin_verified 之前处理，否则永远无法通过验证）
        if (isPost() && $subAction === 'verify') {
            if (!verifyCsrfToken()) {
                setFlash('danger', '安全验证失败，请重试');
                redirect(url('admin', ['action' => 'users']));
                return;
            }
            $password = postParam('password', '');
            $user = currentUser();
            // 通过数据库验证管理员密码（findByUsername 返回含 password_hash 的完整记录）
            $record = $this->userModel->findByUsername($user['username']);
            if ($record && password_verify($password, $record['password_hash'] ?? '')) {
                $_SESSION['admin_verified'] = true;
                setFlash('success', '验证成功');
                redirect(url('admin', ['action' => 'users']));
            } else {
                setFlash('danger', '密码错误');
                redirect(url('admin', ['action' => 'users']));
            }
            return;
        }

        // 未通过二次验证时，显示验证弹窗页面
        if (empty($_SESSION['admin_verified'])) {
            require_once __DIR__ . '/../views/layouts/header.php';
            require_once __DIR__ . '/../views/admin/users_verify.php';
            require_once __DIR__ . '/../views/layouts/footer.php';
            return;
        }

        if (isPost()) {
            // CSRF 安全验证
            if (!verifyCsrfToken()) {
                setFlash('danger', '安全验证失败，请重试');
                redirect(url('admin', ['action' => 'users']));
                return;
            }

            switch ($subAction) {
                // 退出二次验证
                case 'logout_verify':
                    unset($_SESSION['admin_verified']);
                    setFlash('info', '已退出用户管理');
                    redirect(url('admin'));
                    return;

                // 批量新建用户
                case 'create':
                    $users = $_POST['users'] ?? [];
                    if (empty($users) || !is_array($users)) {
                        setFlash('warning', '请填写用户信息');
                    } else {
                        $result = $this->userModel->batchCreate($users);
                        $msg = "创建完成：成功 {$result['success']} 个，失败 {$result['failed']} 个";
                        if (!empty($result['errors'])) {
                            $msg .= '<br>' . implode('<br>', array_map('e', $result['errors']));
                        }
                        setFlash($result['failed'] === 0 ? 'success' : 'warning', $msg);
                    }
                    break;

                // 修改用户角色
                case 'update_role':
                    $id   = (int) postParam('id', 0);
                    $role = postParam('role', 'user');
                    $currentUserId = (int) (currentUser()['id'] ?? 0);
                    if ($id <= 0) {
                        setFlash('warning', '参数无效');
                    } elseif ($id === $currentUserId) {
                        setFlash('warning', '不能修改自己的角色');
                    } elseif (!in_array($role, ['user', 'admin'], true)) {
                        setFlash('warning', '无效的角色');
                    } else {
                        if ($this->userModel->updateRole($id, $role)) {
                            setFlash('success', '角色已更新');
                        } else {
                            setFlash('danger', '更新失败');
                        }
                    }
                    break;

                // 修改用户密码
                case 'update_password':
                    $id          = (int) postParam('id', 0);
                    $newPassword = postParam('new_password', '');
                    if ($id <= 0 || empty($newPassword)) {
                        setFlash('warning', '参数无效');
                    } elseif (mb_strlen($newPassword) < 6) {
                        setFlash('warning', '密码至少6位');
                    } else {
                        if ($this->userModel->updatePassword($id, $newPassword)) {
                            setFlash('success', '密码已更新');
                        } else {
                            setFlash('danger', '更新失败');
                        }
                    }
                    break;

                // 删除用户
                case 'delete':
                    $id = (int) postParam('id', 0);
                    $currentUserId = (int) (currentUser()['id'] ?? 0);
                    if ($id <= 0) {
                        setFlash('warning', '参数无效');
                    } elseif ($id === $currentUserId) {
                        setFlash('warning', '不能删除自己');
                    } else {
                        if ($this->userModel->delete($id, $currentUserId)) {
                            setFlash('success', '用户已删除');
                        } else {
                            setFlash('danger', '删除失败');
                        }
                    }
                    break;
            }

            redirect(url('admin', ['action' => 'users']));
            return;
        }

        // GET：渲染用户列表
        $users         = $this->userModel->getAll();
        $currentUserId = (int) (currentUser()['id'] ?? 0);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/admin/users.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
