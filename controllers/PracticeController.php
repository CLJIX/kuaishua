<?php
/**
 * 答题控制器
 * 处理答案提交和答题结果展示
 */
require_once __DIR__ . '/../models/Question.php';

class PracticeController {
    private QuestionModel $questionModel;

    public function __construct() {
        $this->questionModel = new QuestionModel();
    }

    /**
     * 提交答案
     * POST 参数：question_id, answer（单选字符串如 "A"，多选数组如 ["A","B"]）, time_spent
     * 需要登录才能作答
     */
    public function submit(): void {
        // 必须登录
        requireLogin();

        // 必须为 POST 请求且 CSRF 验证通过
        if (!isPost() || !verifyCsrfToken()) {
            setFlash('danger', '无效的请求');
            redirect(url('home'));
            return;
        }

        // ---- 获取并校验参数 ----
        $questionId = (int) postParam('question_id', 0);
        $answer     = postParam('answer', '');         // 单选字符串或多选数组
        $timeSpent  = (int) postParam('time_spent', 0);

        if ($questionId <= 0) {
            setFlash('warning', '题目参数无效');
            redirect(url('questions', ['action' => 'list']));
            return;
        }

        // 题目必须存在
        $question = $this->questionModel->findById($questionId);
        if (!$question) {
            setFlash('warning', '题目不存在');
            redirect(url('questions', ['action' => 'list']));
            return;
        }

        // 将多选数组答案统一为逗号分隔字符串，方便存储
        if (is_array($answer)) {
            // 排序后拼接，保证一致性
            $answer = array_map('strtoupper', array_map('trim', $answer));
            sort($answer);
            $answerStr = implode(',', $answer);
        } else {
            $answerStr = strtoupper(trim((string) $answer));
        }

        // ---- 验证答案 ----
        $checkResult = $this->questionModel->checkAnswer($questionId, $answer);
        $isCorrect   = $checkResult['is_correct'];
        $isPartial   = $checkResult['is_partial'];
        $correctAnswer = $checkResult['correct_answer'];

        // ---- 保存答题记录 ----
        $user     = currentUser();
        $recordId = 0;
        try {
            $recordId = $this->questionModel->savePracticeRecord(
                $user['id'],
                $questionId,
                $answerStr,
                $isCorrect,
                $timeSpent
            );
        } catch (Exception $e) {
            setFlash('danger', '保存答题记录失败，请重试');
            redirect(url('questions', ['action' => 'detail', 'id' => $questionId]));
            return;
        }

        // 将结果信息存入 Session，供结果页读取
        $_SESSION['last_result'] = [
            'record_id'     => $recordId,
            'question_id'   => $questionId,
            'user_answer'   => $answerStr,
            'is_correct'    => $isCorrect,
            'is_partial'    => $isPartial,
            'correct_answer'=> $correctAnswer,
            'time_spent'    => $timeSpent,
        ];

        // 重定向到结果页
        redirect(url('practice', ['action' => 'result', 'id' => $questionId]));
    }

    /**
     * 答题结果页
     * GET 参数：id（题目ID）
     * 从 Session 中读取最近一次答题结果并展示
     */
    public function result(): void {
        requireLogin();

        // 从 Session 读取结果数据
        $result = $_SESSION['last_result'] ?? null;
        if (!$result) {
            setFlash('warning', '没有答题结果');
            redirect(url('questions', ['action' => 'list']));
            return;
        }

        // 清除已读取的结果，避免刷新重复显示
        unset($_SESSION['last_result']);

        $questionId   = (int) ($result['question_id'] ?? 0);
        $question     = $questionId > 0 ? $this->questionModel->findById($questionId) : null;

        // 题目可能已被删除，需防御性检查
        if (!$question) {
            setFlash('warning', '题目不存在或已被删除');
            redirect(url('questions', ['action' => 'list']));
            return;
        }

        $isCorrect    = (bool) ($result['is_correct'] ?? false);
        $isPartial    = (bool) ($result['is_partial'] ?? false);
        $correctAnswer= $result['correct_answer'] ?? '';
        $userAnswer   = $result['user_answer'] ?? '';
        $timeSpent    = (int) ($result['time_spent'] ?? 0);
        $recordId     = (int) ($result['record_id'] ?? 0);

        // 获取当前题目的分类ID，用于查询同分类的下一题
        $categoryId   = $question['category_id'] ?? null;
        // 获取当前用户ID
        $user         = currentUser();
        $userId       = $user ? (int) $user['id'] : 0;
        // 查询同分类下的下一题ID（优先未答题目，支持回绕）
        $nextQuestionId = $this->questionModel->getNextQuestionId($questionId, $categoryId, $userId);

        // 渲染结果页视图
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/questions/result.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * 默认入口（重定向到题目列表）
     */
    public function index(): void {
        redirect(url('questions', ['action' => 'list']));
    }
}
