<?php
/**
 * 站点配置模型
 * 管理 site_settings 表的增删改查
 */
require_once __DIR__ . '/../config/database.php';

class SettingModel {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * 获取所有配置项
     *
     * @return array 以 setting_key 为键的配置数组
     */
    public function getAll(): array {
        try {
            $stmt = $this->db->query('SELECT setting_key, setting_value, description FROM site_settings');
            $rows = $stmt->fetchAll();
            $result = [];
            foreach ($rows as $row) {
                $result[$row['setting_key']] = $row['setting_value'];
            }
            return $result;
        } catch (PDOException $e) {
            error_log('获取站点配置失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取单个配置项
     *
     * @param string $key     配置键名
     * @param string $default 默认值
     * @return string 配置值
     */
    public function get(string $key, string $default = ''): string {
        try {
            $stmt = $this->db->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :key LIMIT 1');
            $stmt->execute([':key' => $key]);
            $row = $stmt->fetch();
            return $row ? (string) $row['setting_value'] : $default;
        } catch (PDOException $e) {
            error_log('获取站点配置失败: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * 更新单个配置项（不存在则插入）
     *
     * @param string $key   配置键名
     * @param string $value 配置值
     * @return bool 是否成功
     */
    public function set(string $key, string $value): bool {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
                 ON DUPLICATE KEY UPDATE setting_value = :dup_value, updated_at = CURRENT_TIMESTAMP'
            );
            return $stmt->execute([':key' => $key, ':value' => $value, ':dup_value' => $value]);
        } catch (PDOException $e) {
            error_log('更新站点配置失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 批量更新配置
     *
     * @param array $data 键值对数组
     * @return bool 是否全部成功
     * @throws PDOException 数据库异常会抛出，便于上层定位真实原因
     */
    public function saveBatch(array $data): bool {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
                 ON DUPLICATE KEY UPDATE setting_value = :dup_value, updated_at = CURRENT_TIMESTAMP'
            );
            foreach ($data as $key => $value) {
                $stmt->execute([':key' => $key, ':value' => $value, ':dup_value' => $value]);
            }
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            $message = '批量更新站点配置失败: ' . $e->getMessage();
            error_log($message);
            throw new PDOException($message, (int) $e->getCode(), $e);
        }
    }
}
