<?php
/**
 * 媒体资源数据模型
 * 管理 media 表的增删改查，支持分页、筛选、关联题目查询
 */
require_once __DIR__ . '/../config/database.php';

class MediaModel {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * 分页查询媒体列表
     *
     * @param array $filters 筛选条件：keyword, biz_type, date_from, date_to, question_id
     * @param int   $page    当前页码
     * @param int   $perPage 每页条数
     * @return array ['items' => [...], 'total' => int, 'pages' => int, 'current_page' => int]
     */
    public function getList(array $filters = [], int $page = 1, int $perPage = 20): array {
        try {
            $where  = [];
            $params = [];

            if (!empty($filters['keyword'])) {
                $where[] = 'm.file_name LIKE :keyword';
                $params[':keyword'] = '%' . $filters['keyword'] . '%';
            }
            if (!empty($filters['biz_type'])) {
                $where[] = 'm.biz_type = :biz_type';
                $params[':biz_type'] = $filters['biz_type'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'm.created_at >= :date_from';
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'm.created_at <= :date_to';
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            if (!empty($filters['question_id'])) {
                $where[] = 'm.question_id = :question_id';
                $params[':question_id'] = (int) $filters['question_id'];
            }

            $whereClause = '';
            if (!empty($where)) {
                $whereClause = 'WHERE ' . implode(' AND ', $where);
            }

            // 总数
            $countSql = "SELECT COUNT(*) FROM media m {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            // 分页数据（LEFT JOIN questions 获取关联题目信息）
            $offset = ($page - 1) * $perPage;
            $sql = "SELECT m.*, q.content AS question_content
                    FROM media m
                    LEFT JOIN questions q ON m.question_id = q.id
                    {$whereClause}
                    ORDER BY m.created_at DESC
                    LIMIT :offset, :per_page";
            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll();

            return [
                'items'        => $items,
                'total'        => $total,
                'pages'        => (int) ceil($total / $perPage),
                'current_page' => $page,
            ];
        } catch (PDOException $e) {
            error_log('查询媒体列表失败: ' . $e->getMessage());
            return ['items' => [], 'total' => 0, 'pages' => 0, 'current_page' => $page];
        }
    }

    /**
     * 创建媒体记录
     *
     * @param array $data [file_name, file_size, mime_type, oss_path, cdn_url, uploader_id, biz_type, question_id]
     * @return int 新记录 ID
     */
    public function create(array $data): int {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO media (file_name, file_size, mime_type, oss_path, cdn_url, uploader_id, biz_type, question_id)
                 VALUES (:file_name, :file_size, :mime_type, :oss_path, :cdn_url, :uploader_id, :biz_type, :question_id)'
            );
            $stmt->execute([
                ':file_name'   => $data['file_name'],
                ':file_size'   => (int) ($data['file_size'] ?? 0),
                ':mime_type'   => $data['mime_type'],
                ':oss_path'    => $data['oss_path'],
                ':cdn_url'     => $data['cdn_url'] ?? null,
                ':uploader_id' => $data['uploader_id'] ?? null,
                ':biz_type'    => $data['biz_type'] ?? 'general',
                ':question_id' => $data['question_id'] ?? null,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('创建媒体记录失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 按 ID 查找媒体记录
     */
    public function findById(int $id): ?array {
        try {
            $stmt = $this->db->prepare('SELECT * FROM media WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('查找媒体记录失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 删除媒体记录
     */
    public function delete(int $id): bool {
        try {
            $stmt = $this->db->prepare('DELETE FROM media WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('删除媒体记录失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 按题目 ID 查询关联媒体
     */
    public function getByQuestionId(int $questionId): array {
        try {
            $stmt = $this->db->prepare('SELECT * FROM media WHERE question_id = :qid ORDER BY created_at DESC');
            $stmt->execute([':qid' => $questionId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('查询题目关联媒体失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 统计信息（总数、总大小、本月新增）
     */
    public function getStats(): array {
        try {
            $stmt = $this->db->query(
                "SELECT
                    COUNT(*) AS total_count,
                    COALESCE(SUM(file_size), 0) AS total_size,
                    SUM(CASE WHEN created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 ELSE 0 END) AS month_count
                 FROM media"
            );
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('获取媒体统计失败: ' . $e->getMessage());
            return ['total_count' => 0, 'total_size' => 0, 'month_count' => 0];
        }
    }
}
