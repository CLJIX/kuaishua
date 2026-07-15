<?php
/**
 * 一次性脚本：重置管理员密码为 admin123
 * 使用方法：php fix_password.php 或浏览器访问此文件
 * 使用完成后请删除此文件
 */
require_once __DIR__ . '/config/database.php';

$hash = password_hash('admin123', PASSWORD_DEFAULT);
$db = getDB();
$stmt = $db->prepare('UPDATE users SET password_hash = :hash WHERE username = :username');
$stmt->execute([':hash' => $hash, ':username' => 'admin']);

echo '管理员密码已重置为 admin123';
// 使用后请删除此文件
