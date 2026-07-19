<?php
/**
 * 题目控制器
 * 处理首页展示、题目列表、题目详情
 */
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Tag.php';

class QuestionController {
    private QuestionModel $questionModel;
    private CategoryModel $categoryModel;
    private TagModel      $tagModel;

    public function __construct() {
        $this->questionModel = new QuestionModel();
        $this->categoryModel = new CategoryModel();
        $this->tagModel      = new TagModel();
    }

    /**
     * 首页 - 题库概览
     * 展示分类列表、题目总数；已登录用户额外显示个人答题统计
     */
    public function index(): void {
        // 基础数据
        $categories     = $this->categoryModel->getTopLevel();
        $totalQuestions = $this->questionModel->getTotalCount();

        // 已登录用户获取个人统计
        $userStats = null;
        if (isLoggedIn()) {
            $user      = currentUser();
            $userStats = $this->questionModel->getUserStats($user['id']);
        }

        // 渲染首页视图
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/home.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * 题目列表页 - 支持多条件筛选和分页
     * GET 参数：category_id, question_type, difficulty, tag_id, keyword, page
     */
    public function list(): void {
        // 收集筛选条件
        $filters = [
            'category_id'   => getParam('category_id'),
            'question_type' => getParam('question_type'),
            'difficulty'    => getParam('difficulty'),
            'tag_id'        => getParam('tag_id'),
            'keyword'       => getParam('keyword'),
        ];

        // 当前页码（至少为 1）
        $page = max(1, (int) getParam('page', 1));

        // 每页显示 20 条
        $result = $this->questionModel->getList($filters, $page, 50);

        // 供筛选下拉框使用的数据
        $categories = $this->categoryModel->getAll();
        $tags       = $this->tagModel->getAll();

        // 渲染列表页视图
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/questions/list.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * 题目详情页（刷题页面）
     * GET 参数：id
     * 需要登录才能提交答案；未登录用户可查看题目但无法作答
     */
    public function detail(): void {
        $id = (int) getParam('id', 0);
        if ($id <= 0) {
            setFlash('warning', '题目参数无效');
            redirect(url('questions', ['action' => 'list']));
            return;
        }

        // 获取题目详情（含选项和标签）
        $question = $this->questionModel->findById($id);
        if (!$question) {
            setFlash('warning', '题目不存在');
            redirect(url('questions', ['action' => 'list']));
            return;
        }

        // 检查当前用户是否已答过此题
        $hasAnswered = false;
        if (isLoggedIn()) {
            $user        = currentUser();
            $hasAnswered = $this->questionModel->hasUserAnswered($user['id'], $id);
        }

        // 渲染详情页视图
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/questions/detail.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
