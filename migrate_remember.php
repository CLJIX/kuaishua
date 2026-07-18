<?php
/**
 * 记住我（Remember Me）功能 - 数据库迁移脚本
 *
 * 新增 remember_tokens 表，用于存储用户"记住我"登录令牌的哈希值。
 *
 * 使用方式：
 *   命令行：php migrate_remember.php
 *   浏览器：访问 http://your-domain/migrate_remember.php
 *
 * 执行完成后请删除此文件。
 */
require_once __DIR__ . '/config/database.php';

$pdo = getDB();

$sql = "CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '关联的用户ID',
    token_hash CHAR(64) NOT NULL UNIQUE COMMENT '令牌的SHA-256哈希值（64位十六进制）',
    expires_at DATETIME NOT NULL COMMENT '令牌过期时间',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '令牌创建时间',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='记住我登录令牌表'";

try {
    $pdo->exec($sql);
    echo "✅ remember_tokens 表创建成功（或已存在）\n";
} catch (PDOException $e) {
    echo "❌ 迁移失败：" . $e->getMessage() . "\n";
    exit(1);
}

// 清理已过期的令牌（如果表之前已存在）
try {
    $stmt = $pdo->exec("DELETE FROM remember_tokens WHERE expires_at < NOW()");
    echo "✅ 已清理过期令牌\n";
} catch (PDOException $e) {
    // 忽略
}

echo "\n迁移完成。请删除此文件。\n";
