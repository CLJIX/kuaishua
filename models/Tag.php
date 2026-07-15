<?php
/**
 * 标签数据模型
 * 管理标签及其与题目的多对多关联
 */
require_once __DIR__ . '/../config/database.php';

class TagModel {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * 创建标签
     * @param string $name 标签名称
     * @return int 新标签ID
     */
    public function create(string $name): int {
        try {
            $stmt = $this->db->prepare('INSERT INTO tags (name) VALUES (:name)');
            $stmt->execute([':name' => $name]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('创建标签失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新标签
     * @param int $id 标签ID
     * @param string $name 新名称
     * @return bool 是否更新成功
     */
    public function update(int $id, string $name): bool {
        try {
            $stmt = $this->db->prepare('UPDATE tags SET name = :name WHERE id = :id');
            return $stmt->execute([':name' => $name, ':id' => $id]);
        } catch (PDOException $e) {
            error_log('更新标签失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除标签（同时删除关联记录）
     * question_tags 表设置了 ON DELETE CASCADE，删除标签后关联记录自动删除
     * @param int $id 标签ID
     * @return bool 是否删除成功
     */
    public function delete(int $id): bool {
        try {
            // 先手动删除关联（确保兼容性），再删除标签
            $this->db->beginTransaction();

            $stmtRel = $this->db->prepare('DELETE FROM question_tags WHERE tag_id = :tag_id');
            $stmtRel->execute([':tag_id' => $id]);

            $stmt = $this->db->prepare('DELETE FROM tags WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('删除标签失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 根据 ID 查询标签
     * @param int $id 标签ID
     * @return array|null 标签信息，不存在返回null
     */
    public function findById(int $id): ?array {
        try {
            $stmt = $this->db->prepare('SELECT * FROM tags WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('查询标签失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 根据名称查找标签（用于导入时检查是否已存在）
     * @param string $name 标签名称
     * @return array|null 标签信息，不存在返回null
     */
    public function findByName(string $name): ?array {
        try {
            $stmt = $this->db->prepare('SELECT * FROM tags WHERE name = :name LIMIT 1');
            $stmt->execute([':name' => $name]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('根据名称查询标签失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取所有标签
     * @return array 标签列表，按名称排序
     */
    public function getAll(): array {
        try {
            $stmt = $this->db->query('SELECT * FROM tags ORDER BY name ASC');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('获取标签列表失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取指定题目的所有标签
     * @param int $questionId 题目ID
     * @return array 该题目关联的标签列表
     */
    public function getTagsByQuestionId(int $questionId): array {
        try {
            $stmt = $this->db->prepare(
                'SELECT t.* FROM tags t
                 INNER JOIN question_tags qt ON qt.tag_id = t.id
                 WHERE qt.question_id = :question_id
                 ORDER BY t.name ASC'
            );
            $stmt->execute([':question_id' => $questionId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('获取题目标签失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 批量设置题目的标签（先删除旧关联再插入新关联）
     * 使用事务确保原子性
     * @param int $questionId 题目ID
     * @param array $tagIds 标签ID数组
     */
    public function setQuestionTags(int $questionId, array $tagIds): void {
        try {
            $this->db->beginTransaction();

            // 删除旧的标签关联
            $stmtDel = $this->db->prepare('DELETE FROM question_tags WHERE question_id = :question_id');
            $stmtDel->execute([':question_id' => $questionId]);

            // 插入新的标签关联
            if (!empty($tagIds)) {
                $stmtIns = $this->db->prepare(
                    'INSERT INTO question_tags (question_id, tag_id) VALUES (:question_id, :tag_id)'
                );
                foreach ($tagIds as $tagId) {
                    $stmtIns->execute([
                        ':question_id' => $questionId,
                        ':tag_id'      => (int) $tagId,
                    ]);
                }
            }

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('设置题目标签失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取标签关联的题目数量
     * @param int $tagId 标签ID
     * @return int 题目数量
     */
    public function getQuestionCount(int $tagId): int {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) AS cnt FROM question_tags WHERE tag_id = :tag_id'
            );
            $stmt->execute([':tag_id' => $tagId]);
            $row = $stmt->fetch();
            return (int) ($row['cnt'] ?? 0);
        } catch (PDOException $e) {
            error_log('获取标签题目数量失败: ' . $e->getMessage());
            return 0;
        }
    }
}
