<?php
/**
 * 一次性迁移脚本：为 questions 表新增判断题类型
 * 使用方法：浏览器访问此文件
 * 使用完成后请删除此文件
 */
require_once __DIR__ . '/config/database.php';

$db = getDB();

try {
    $db->exec("ALTER TABLE questions MODIFY COLUMN question_type ENUM('single','multiple','judge') NOT NULL COMMENT '题型: single=单选题, multiple=多选题, judge=判断题'");
    echo '<h3>迁移成功！</h3>';
    echo '<p>question_type 字段已更新，支持 single / multiple / judge 三种类型。</p>';
    echo '<p style="color:red"><strong>请立即删除此文件！</strong></p>';
} catch (PDOException $e) {
    echo '<h3>迁移失败</h3>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
