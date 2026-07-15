<?php
/**
 * 分类数据模型
 * 支持层级树形结构，提供增删改查及树形构建
 */
require_once __DIR__ . '/../config/database.php';

class CategoryModel {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * 创建分类
     * @param string $name 分类名称
     * @param int|null $parentId 父分类ID，null 表示顶级分类
     * @param string $description 分类描述
     * @param int $sortOrder 排序权重（数值越小越靠前）
     * @return int 新分类ID
     */
    public function create(string $name, ?int $parentId, string $description = '', int $sortOrder = 0): int {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO categories (name, parent_id, description, sort_order)
                 VALUES (:name, :parent_id, :description, :sort_order)'
            );
            $stmt->execute([
                ':name'        => $name,
                ':parent_id'   => $parentId,
                ':description' => $description,
                ':sort_order'  => $sortOrder,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('创建分类失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新分类
     * @param int $id 分类ID
     * @param string $name 分类名称
     * @param int|null $parentId 父分类ID
     * @param string $description 分类描述
     * @param int $sortOrder 排序权重
     * @return bool 是否更新成功
     */
    public function update(int $id, string $name, ?int $parentId, string $description = '', int $sortOrder = 0): bool {
        try {
            $stmt = $this->db->prepare(
                'UPDATE categories SET name = :name, parent_id = :parent_id,
                 description = :description, sort_order = :sort_order WHERE id = :id'
            );
            return $stmt->execute([
                ':name'        => $name,
                ':parent_id'   => $parentId,
                ':description' => $description,
                ':sort_order'  => $sortOrder,
                ':id'          => $id,
            ]);
        } catch (PDOException $e) {
            error_log('更新分类失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除分类（检查是否有关联题目）
     * 若分类下存在题目则不允许删除
     * @param int $id 分类ID
     * @return bool 是否删除成功
     */
    public function delete(int $id): bool {
        // 检查是否有关联题目
        if ($this->getQuestionCount($id) > 0) {
            return false;
        }
        // 检查是否有子分类
        $children = $this->getChildren($id);
        if (!empty($children)) {
            return false;
        }

        try {
            $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('删除分类失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 根据 ID 查询分类
     * @param int $id 分类ID
     * @return array|null 分类信息，不存在返回null
     */
    public function findById(int $id): ?array {
        try {
            $stmt = $this->db->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('查询分类失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取所有分类（平铺列表）
     * @return array 分类列表，按 sort_order 排序
     */
    public function getAll(): array {
        try {
            $stmt = $this->db->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('获取分类列表失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取顶级分类（parent_id IS NULL）
     * @return array 顶级分类列表
     */
    public function getTopLevel(): array {
        try {
            $stmt = $this->db->query(
                'SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC, id ASC'
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('获取顶级分类失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取指定分类的子分类
     * @param int $parentId 父分类ID
     * @return array 子分类列表
     */
    public function getChildren(int $parentId): array {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM categories WHERE parent_id = :parent_id ORDER BY sort_order ASC, id ASC'
            );
            $stmt->execute([':parent_id' => $parentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('获取子分类失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取树形结构（递归构建）
     * 返回嵌套数组，每个节点包含 children 字段
     * @return array 树形分类结构
     */
    public function getTree(): array {
        // 获取所有分类（平铺）
        $all = $this->getAll();
        // 以 id 为键建立索引
        $indexed = [];
        foreach ($all as $item) {
            $item['children'] = [];
            $indexed[$item['id']] = $item;
        }
        // 构建树形结构
        $tree = [];
        foreach ($indexed as $id => $item) {
            if ($item['parent_id'] === null) {
                // 顶级分类
                $tree[] = &$indexed[$id];
            } else {
                // 挂载到父分类下
                if (isset($indexed[$item['parent_id']])) {
                    $indexed[$item['parent_id']]['children'][] = &$indexed[$id];
                }
            }
        }
        return $tree;
    }

    /**
     * 获取分类下的题目数量
     * @param int $categoryId 分类ID
     * @return int 题目数量
     */
    public function getQuestionCount(int $categoryId): int {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) AS cnt FROM questions WHERE category_id = :category_id');
            $stmt->execute([':category_id' => $categoryId]);
            $row = $stmt->fetch();
            return (int) ($row['cnt'] ?? 0);
        } catch (PDOException $e) {
            error_log('获取分类题目数量失败: ' . $e->getMessage());
            return 0;
        }
    }
}
