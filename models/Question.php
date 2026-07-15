<?php
/**
 * 题目数据模型
 * 处理题目的增删改查、答案验证、答题记录、统计等功能
 * 是最核心也是最复杂的数据模型
 */
require_once __DIR__ . '/../config/database.php';

class QuestionModel {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * 创建题目（含选项和标签）
     * 使用事务确保原子性：题目、选项、标签必须全部成功
     * @param array $data 题目数据 [question_type, content, explanation, difficulty, category_id, tags[]]
     * @param array $options 选项数组 [['option_label'=>'A', 'option_text'=>'...', 'is_correct'=>1], ...]
     * @return int 新题目ID
     */
    public function create(array $data, array $options): int {
        try {
            $this->db->beginTransaction();

            // 插入题目主记录
            $stmt = $this->db->prepare(
                'INSERT INTO questions (question_type, content, explanation, difficulty, category_id)
                 VALUES (:question_type, :content, :explanation, :difficulty, :category_id)'
            );
            $stmt->execute([
                ':question_type' => $data['question_type'],
                ':content'       => $data['content'],
                ':explanation'   => $data['explanation'] ?? null,
                ':difficulty'    => $data['difficulty'] ?? 1,
                ':category_id'   => $data['category_id'] ?? null,
            ]);
            $questionId = (int) $this->db->lastInsertId();

            // 插入选项
            $this->insertOptions($questionId, $options);

            // 设置标签
            if (!empty($data['tags'])) {
                $this->insertTags($questionId, $data['tags']);
            }

            $this->db->commit();
            return $questionId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('创建题目失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新题目（含选项和标签）
     * 使用事务：先删旧选项再插入新选项，标签同理
     * @param int $id 题目ID
     * @param array $data 题目数据
     * @param array $options 新选项数组
     * @return bool 是否更新成功
     */
    public function update(int $id, array $data, array $options): bool {
        try {
            $this->db->beginTransaction();

            // 更新题目主信息
            $stmt = $this->db->prepare(
                'UPDATE questions SET question_type = :question_type, content = :content,
                 explanation = :explanation, difficulty = :difficulty, category_id = :category_id
                 WHERE id = :id'
            );
            $stmt->execute([
                ':question_type' => $data['question_type'],
                ':content'       => $data['content'],
                ':explanation'   => $data['explanation'] ?? null,
                ':difficulty'    => $data['difficulty'] ?? 1,
                ':category_id'   => $data['category_id'] ?? null,
                ':id'            => $id,
            ]);

            // 删除旧选项后重新插入
            $stmtDelOpt = $this->db->prepare('DELETE FROM question_options WHERE question_id = :question_id');
            $stmtDelOpt->execute([':question_id' => $id]);
            $this->insertOptions($id, $options);

            // 更新标签关联
            if (isset($data['tags'])) {
                $stmtDelTag = $this->db->prepare('DELETE FROM question_tags WHERE question_id = :question_id');
                $stmtDelTag->execute([':question_id' => $id]);
                $this->insertTags($id, $data['tags']);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('更新题目失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除题目（级联删除选项和标签关联）
     * question_options 和 question_tags 表均设置了 ON DELETE CASCADE
     * @param int $id 题目ID
     * @return bool 是否删除成功
     */
    public function delete(int $id): bool {
        try {
            // 使用事务确保删除操作的原子性
            $this->db->beginTransaction();

            // 手动删除关联数据（兼容性保障）
            $stmtTags = $this->db->prepare('DELETE FROM question_tags WHERE question_id = :question_id');
            $stmtTags->execute([':question_id' => $id]);

            $stmtOpts = $this->db->prepare('DELETE FROM question_options WHERE question_id = :question_id');
            $stmtOpts->execute([':question_id' => $id]);

            $stmtRecs = $this->db->prepare('DELETE FROM practice_records WHERE question_id = :question_id');
            $stmtRecs->execute([':question_id' => $id]);

            // 删除题目本身
            $stmt = $this->db->prepare('DELETE FROM questions WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('删除题目失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 根据 ID 查询题目详情（含选项和标签）
     * @param int $id 题目ID
     * @return array|null 包含 question 基本信息、options 数组、tags 数组，不存在返回null
     */
    public function findById(int $id): ?array {
        try {
            // 查询题目基本信息
            $stmt = $this->db->prepare(
                'SELECT q.*, c.name AS category_name
                 FROM questions q
                 LEFT JOIN categories c ON q.category_id = c.id
                 WHERE q.id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $question = $stmt->fetch();

            if (!$question) {
                return null;
            }

            // 查询选项
            $question['options'] = $this->getOptions($id);

            // 查询标签
            $stmtTags = $this->db->prepare(
                'SELECT t.* FROM tags t
                 INNER JOIN question_tags qt ON qt.tag_id = t.id
                 WHERE qt.question_id = :question_id
                 ORDER BY t.name ASC'
            );
            $stmtTags->execute([':question_id' => $id]);
            $question['tags'] = $stmtTags->fetchAll();

            return $question;
        } catch (PDOException $e) {
            error_log('查询题目详情失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 分页查询题目列表（支持多条件筛选组合）
     * @param array $filters 筛选条件：category_id, question_type, difficulty, tag_id, keyword
     * @param int $page 当前页码（从1开始）
     * @param int $perPage 每页数量（默认 20）
     * @return array ['items' => [...], 'total' => int, 'pages' => int, 'current_page' => int]
     */
    public function getList(array $filters = [], int $page = 1, int $perPage = 20): array {
        try {
            // 构建 WHERE 条件和参数
            $where = [];
            $params = [];

            if (!empty($filters['category_id'])) {
                $where[] = 'q.category_id = :category_id';
                $params[':category_id'] = (int) $filters['category_id'];
            }

            if (!empty($filters['question_type'])) {
                $where[] = 'q.question_type = :question_type';
                $params[':question_type'] = $filters['question_type'];
            }

            if (!empty($filters['difficulty'])) {
                $where[] = 'q.difficulty = :difficulty';
                $params[':difficulty'] = (int) $filters['difficulty'];
            }

            // 按标签筛选（需要 JOIN question_tags）
            $joinTag = false;
            if (!empty($filters['tag_id'])) {
                $joinTag = true;
                $where[] = 'qt_filter.tag_id = :tag_id';
                $params[':tag_id'] = (int) $filters['tag_id'];
            }

            // 关键词搜索（在题面内容中搜索）
            if (!empty($filters['keyword'])) {
                $where[] = 'q.content LIKE :keyword';
                $params[':keyword'] = '%' . $filters['keyword'] . '%';
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $joinTagClause = $joinTag
                ? 'INNER JOIN question_tags qt_filter ON qt_filter.question_id = q.id'
                : '';

            // 查询总数
            $countSql = "SELECT COUNT(DISTINCT q.id) AS cnt FROM questions q {$joinTagClause} {$whereClause}";
            $stmtCount = $this->db->prepare($countSql);
            $stmtCount->execute($params);
            $total = (int) ($stmtCount->fetch()['cnt'] ?? 0);

            // 计算分页
            $pages = (int) max(1, ceil($total / $perPage));
            $page = max(1, min($page, $pages));
            $offset = ($page - 1) * $perPage;

            // 查询分页数据
            $listSql = "SELECT q.*, c.name AS category_name
                        FROM questions q
                        LEFT JOIN categories c ON q.category_id = c.id
                        {$joinTagClause}
                        {$whereClause}
                        GROUP BY q.id
                        ORDER BY q.id DESC
                        LIMIT :limit OFFSET :offset";
            $stmtList = $this->db->prepare($listSql);
            // 绑定筛选参数
            foreach ($params as $key => $value) {
                $stmtList->bindValue($key, $value);
            }
            $stmtList->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmtList->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmtList->execute();
            $items = $stmtList->fetchAll();

            return [
                'items'        => $items,
                'total'        => $total,
                'pages'        => $pages,
                'current_page' => $page,
            ];
        } catch (PDOException $e) {
            error_log('分页查询题目失败: ' . $e->getMessage());
            return [
                'items'        => [],
                'total'        => 0,
                'pages'        => 1,
                'current_page' => 1,
            ];
        }
    }

    /**
     * 获取题目的所有选项
     * @param int $questionId 题目ID
     * @return array 选项列表，按 option_label 排序
     */
    public function getOptions(int $questionId): array {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM question_options WHERE question_id = :question_id ORDER BY option_label ASC'
            );
            $stmt->execute([':question_id' => $questionId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('获取题目选项失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 验证用户答案是否正确
     * - 单选题：比对单个正确选项
     * - 多选题：比对所有正确选项（顺序无关，即用户答案排序后与正确答案排序后一致）
     * @param int $questionId 题目ID
     * @param string|array $userAnswer 用户答案（单选为字符串如"A"，多选为数组如["A","B"]）
     * @return array ['is_correct' => bool, 'correct_answer' => string]
     */
    public function checkAnswer(int $questionId, $userAnswer): array {
        // 获取题目信息
        $stmt = $this->db->prepare('SELECT question_type FROM questions WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $questionId]);
        $question = $stmt->fetch();

        if (!$question) {
            return ['is_correct' => false, 'is_partial' => false, 'correct_answer' => ''];
        }

        // 获取所有正确选项的 label
        $stmtCorrect = $this->db->prepare(
            'SELECT option_label FROM question_options
             WHERE question_id = :question_id AND is_correct = 1
             ORDER BY option_label ASC'
        );
        $stmtCorrect->execute([':question_id' => $questionId]);
        $correctLabels = array_column($stmtCorrect->fetchAll(), 'option_label');
        $correctAnswer = implode(',', $correctLabels);

        // 判断半对（仅多选题）
        $isPartial = false;

        if ($question['question_type'] === 'single') {
            // 单选题：用户答案为单个字符串
            $isCorrect = (strtoupper(trim((string) $userAnswer)) === strtoupper($correctLabels[0] ?? ''));
        } else {
            // 多选题：将用户答案与正确答案都排序后比较
            if (is_string($userAnswer)) {
                $userAnswer = array_map('trim', explode(',', $userAnswer));
            }
            $userLabels = array_map('strtoupper', $userAnswer);
            sort($userLabels);
            $sortedCorrect = $correctLabels;
            sort($sortedCorrect);
            $isCorrect = ($userLabels === $sortedCorrect);

            // 判断半对：选的都是对的，但没选全
            if (!$isCorrect) {
                $userCorrectCount = count(array_intersect($userLabels, $sortedCorrect));
                $userWrongCount = count($userLabels) - $userCorrectCount;
                if ($userCorrectCount > 0 && $userWrongCount === 0) {
                    $isPartial = true;
                }
            }
        }

        return [
            'is_correct'     => $isCorrect,
            'is_partial'     => $isPartial,
            'correct_answer' => $correctAnswer,
        ];
    }

    /**
     * 记录答题结果
     * @param int $userId 用户ID
     * @param int $questionId 题目ID
     * @param string $userAnswer 用户提交的答案（多选时逗号分隔，如 A,B）
     * @param bool $isCorrect 是否正确
     * @param int $timeSpent 答题用时（秒）
     * @return int 记录ID
     */
    public function savePracticeRecord(int $userId, int $questionId, string $userAnswer, bool $isCorrect, int $timeSpent = 0): int {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO practice_records (user_id, question_id, user_answer, is_correct, time_spent)
                 VALUES (:user_id, :question_id, :user_answer, :is_correct, :time_spent)'
            );
            $stmt->execute([
                ':user_id'     => $userId,
                ':question_id' => $questionId,
                ':user_answer' => $userAnswer,
                ':is_correct'  => $isCorrect ? 1 : 0,
                ':time_spent'  => $timeSpent,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('保存答题记录失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取用户的答题统计
     * @param int $userId 用户ID
     * @return array 统计数据：总答题数、正确数、正确率、各难度统计等
     */
    public function getUserStats(int $userId): array {
        try {
            // 总答题数和正确数
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) AS total,
                        SUM(is_correct) AS correct_count
                 FROM practice_records WHERE user_id = :user_id'
            );
            $stmt->execute([':user_id' => $userId]);
            $stats = $stmt->fetch();

            $total = (int) ($stats['total'] ?? 0);
            $correct = (int) ($stats['correct_count'] ?? 0);

            // 按难度统计
            $stmtDiff = $this->db->prepare(
                'SELECT q.difficulty,
                        COUNT(*) AS cnt,
                        SUM(pr.is_correct) AS correct_cnt
                 FROM practice_records pr
                 INNER JOIN questions q ON pr.question_id = q.id
                 WHERE pr.user_id = :user_id
                 GROUP BY q.difficulty'
            );
            $stmtDiff->execute([':user_id' => $userId]);
            $byDifficulty = $stmtDiff->fetchAll();

            return [
                'total'         => $total,
                'correct'       => $correct,
                'wrong'         => $total - $correct,
                'accuracy'      => $total > 0 ? round($correct / $total * 100, 1) : 0,
                'by_difficulty' => $byDifficulty,
            ];
        } catch (PDOException $e) {
            error_log('获取用户答题统计失败: ' . $e->getMessage());
            return [
                'total'         => 0,
                'correct'       => 0,
                'wrong'         => 0,
                'accuracy'      => 0,
                'by_difficulty' => [],
            ];
        }
    }

    /**
     * 获取用户在某分类下的答题记录
     * @param int $userId 用户ID
     * @param int|null $categoryId 分类ID，null 表示获取全部记录
     * @return array 答题记录列表（含题目信息）
     */
    public function getUserRecords(int $userId, ?int $categoryId = null): array {
        try {
            if ($categoryId !== null) {
                $stmt = $this->db->prepare(
                    'SELECT pr.*, q.content AS question_content, q.question_type, q.difficulty,
                            c.name AS category_name
                     FROM practice_records pr
                     INNER JOIN questions q ON pr.question_id = q.id
                     LEFT JOIN categories c ON q.category_id = c.id
                     WHERE pr.user_id = :user_id AND q.category_id = :category_id
                     ORDER BY pr.created_at DESC'
                );
                $stmt->execute([':user_id' => $userId, ':category_id' => $categoryId]);
            } else {
                $stmt = $this->db->prepare(
                    'SELECT pr.*, q.content AS question_content, q.question_type, q.difficulty,
                            c.name AS category_name
                     FROM practice_records pr
                     INNER JOIN questions q ON pr.question_id = q.id
                     LEFT JOIN categories c ON q.category_id = c.id
                     WHERE pr.user_id = :user_id
                     ORDER BY pr.created_at DESC'
                );
                $stmt->execute([':user_id' => $userId]);
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('获取用户答题记录失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 检查用户是否已答过某题
     * @param int $userId 用户ID
     * @param int $questionId 题目ID
     * @return bool 是否已答过
     */
    public function hasUserAnswered(int $userId, int $questionId): bool {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) AS cnt FROM practice_records
                 WHERE user_id = :user_id AND question_id = :question_id'
            );
            $stmt->execute([':user_id' => $userId, ':question_id' => $questionId]);
            $row = $stmt->fetch();
            return ((int) ($row['cnt'] ?? 0)) > 0;
        } catch (PDOException $e) {
            error_log('检查用户是否已答题失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取题目总数
     * @return int 题目总数
     */
    public function getTotalCount(): int {
        try {
            $stmt = $this->db->query('SELECT COUNT(*) AS cnt FROM questions');
            $row = $stmt->fetch();
            return (int) ($row['cnt'] ?? 0);
        } catch (PDOException $e) {
            error_log('获取题目总数失败: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 批量导入题目（用于CSV导入）
     * 每条题目独立使用事务，单条失败不影响其他题目
     * @param array $questions 题目数据数组，每项结构同 create() 的 $data + $options
     * @return array ['success' => int, 'failed' => int, 'errors' => [...]]
     */
    public function batchImport(array $questions): array {
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($questions as $index => $item) {
            try {
                $data = [
                    'question_type' => $item['question_type'],
                    'content'       => $item['content'],
                    'explanation'   => $item['explanation'] ?? null,
                    'difficulty'    => $item['difficulty'] ?? 1,
                    'category_id'   => $item['category_id'] ?? null,
                    'tags'          => $item['tags'] ?? [],
                ];
                $options = $item['options'] ?? [];

                if (empty($data['content'])) {
                    throw new Exception('题目内容不能为空');
                }
                if (empty($options)) {
                    throw new Exception('选项不能为空');
                }

                $this->create($data, $options);
                $success++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = "第 " . ($index + 1) . " 条: " . $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'failed'  => $failed,
            'errors'  => $errors,
        ];
    }

    // =========================================================
    // 私有辅助方法
    // =========================================================

    /**
     * 插入题目选项（内部使用，需在事务中调用）
     * @param int $questionId 题目ID
     * @param array $options 选项数组
     */
    private function insertOptions(int $questionId, array $options): void {
        $stmt = $this->db->prepare(
            'INSERT INTO question_options (question_id, option_label, option_text, is_correct)
             VALUES (:question_id, :option_label, :option_text, :is_correct)'
        );
        foreach ($options as $option) {
            $stmt->execute([
                ':question_id'  => $questionId,
                ':option_label' => $option['option_label'],
                ':option_text'  => $option['option_text'],
                ':is_correct'   => $option['is_correct'] ?? 0,
            ]);
        }
    }

    /**
     * 插入题目标签关联（内部使用，需在事务中调用）
     * @param int $questionId 题目ID
     * @param array $tagIds 标签ID数组
     */
    private function insertTags(int $questionId, array $tagIds): void {
        $stmt = $this->db->prepare(
            'INSERT INTO question_tags (question_id, tag_id) VALUES (:question_id, :tag_id)'
        );
        foreach ($tagIds as $tagId) {
            $stmt->execute([
                ':question_id' => $questionId,
                ':tag_id'      => (int) $tagId,
            ]);
        }
    }
}
