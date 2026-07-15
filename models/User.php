<?php
/**
 * 用户数据模型
 * 处理用户注册、登录、查询等操作
 */
require_once __DIR__ . '/../config/database.php';

class UserModel {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * 用户注册
     * @param string $username 用户名
     * @param string $email 邮箱
     * @param string $password 明文密码
     * @return int|false 成功返回用户ID，失败返回false
     */
    public function register(string $username, string $email, string $password) {
        // 检查用户名是否已存在
        if ($this->findByUsername($username)) {
            return false;
        }
        // 检查邮箱是否已存在
        if ($this->findByEmail($email)) {
            return false;
        }

        try {
            // 加密密码
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare(
                'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)'
            );
            $stmt->execute([
                ':username'      => $username,
                ':email'         => $email,
                ':password_hash' => $passwordHash,
            ]);

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('用户注册失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 用户登录验证
     * @param string $username 用户名
     * @param string $password 明文密码
     * @return array|false 成功返回用户信息数组（不含密码哈希），失败返回false
     */
    public function login(string $username, string $password) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if (!$user) {
                return false;
            }

            // 验证密码
            if (!password_verify($password, $user['password_hash'])) {
                return false;
            }

            // 移除敏感的密码哈希字段
            unset($user['password_hash']);
            return $user;
        } catch (PDOException $e) {
            error_log('用户登录失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 根据 ID 查询用户
     * @param int $id 用户ID
     * @return array|null 用户信息数组（不含密码哈希），不存在返回null
     */
    public function findById(int $id): ?array {
        try {
            $stmt = $this->db->prepare(
                'SELECT id, username, email, role, created_at, updated_at FROM users WHERE id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (PDOException $e) {
            error_log('查询用户失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 根据邮箱查询用户（注册时检查邮箱唯一性）
     * @param string $email 邮箱
     * @return array|null 用户信息数组，不存在返回null
     */
    public function findByEmail(string $email): ?array {
        try {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (PDOException $e) {
            error_log('根据邮箱查询用户失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 根据用户名查询用户（注册时检查用户名唯一性）
     * @param string $username 用户名
     * @return array|null 用户信息数组，不存在返回null
     */
    public function findByUsername(string $username): ?array {
        try {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (PDOException $e) {
            error_log('根据用户名查询用户失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取所有用户列表（管理员用）
     * @return array 用户列表
     */
    public function getAll(): array {
        try {
            $stmt = $this->db->query(
                'SELECT id, username, email, role, created_at, updated_at FROM users ORDER BY id ASC'
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('获取用户列表失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 更新用户角色
     * @param int $id 用户ID
     * @param string $role 新角色（user 或 admin）
     * @return bool 是否更新成功
     */
    public function updateRole(int $id, string $role): bool {
        try {
            $stmt = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
            return $stmt->execute([':role' => $role, ':id' => $id]);
        } catch (PDOException $e) {
            error_log('更新用户角色失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除用户（不能删除自己）
     */
    public function delete(int $id, int $currentUserId): bool {
        if ($id === $currentUserId) {
            return false;
        }
        try {
            $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('删除用户失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 更新用户密码
     */
    public function updatePassword(int $id, string $newPassword): bool {
        try {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            return $stmt->execute([':hash' => $hash, ':id' => $id]);
        } catch (PDOException $e) {
            error_log('更新密码失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 批量创建用户
     */
    public function batchCreate(array $users): array {
        $success = 0;
        $failed = 0;
        $errors = [];
        foreach ($users as $index => $item) {
            $username = trim($item['username'] ?? '');
            $email = trim($item['email'] ?? '');
            $password = $item['password'] ?? '';
            $role = $item['role'] ?? 'user';
            if (empty($username) || empty($email) || empty($password)) {
                $failed++;
                $errors[] = "第" . ($index + 1) . "条: 用户名、邮箱、密码不能为空";
                continue;
            }
            if (mb_strlen($password) < 6) {
                $failed++;
                $errors[] = "第" . ($index + 1) . "条: 密码至少6位";
                continue;
            }
            if ($this->findByUsername($username)) {
                $failed++;
                $errors[] = "第" . ($index + 1) . "条: 用户名已存在";
                continue;
            }
            if ($this->findByEmail($email)) {
                $failed++;
                $errors[] = "第" . ($index + 1) . "条: 邮箱已存在";
                continue;
            }
            $result = $this->register($username, $email, $password);
            if ($result) {
                if ($role === 'admin') {
                    $this->updateRole((int)$result, 'admin');
                }
                $success++;
            } else {
                $failed++;
                $errors[] = "第" . ($index + 1) . "条: 创建失败";
            }
        }
        return ['success' => $success, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * 更新用户邮箱
     */
    public function updateEmail(int $id, string $newEmail): bool {
        // 检查邮箱是否已被其他用户使用
        $existing = $this->findByEmail($newEmail);
        if ($existing && $existing['id'] !== $id) {
            return false;
        }
        try {
            $stmt = $this->db->prepare('UPDATE users SET email = :email WHERE id = :id');
            return $stmt->execute([':email' => $newEmail, ':id' => $id]);
        } catch (PDOException $e) {
            error_log('更新邮箱失败: ' . $e->getMessage());
            return false;
        }
    }
}
