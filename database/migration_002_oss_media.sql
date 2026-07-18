-- ============================================================
-- "小题快刷" - 迁移脚本：OSS 对象存储 + 媒体库
-- 执行前提：已完成 schema.sql 基础建表
-- 幂等设计：重复执行不会报错
-- ============================================================

USE xtks_db;

-- -----------------------------------------------------------
-- 1. 媒体资源表
-- 存储上传到 OSS 的图片/文件元数据
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT DEFAULT NULL COMMENT '关联题目ID（可选）',
    file_name VARCHAR(255) NOT NULL COMMENT '原始文件名',
    file_size INT NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
    mime_type VARCHAR(100) NOT NULL COMMENT 'MIME类型',
    oss_path VARCHAR(500) NOT NULL COMMENT 'OSS对象路径（不含bucket）',
    cdn_url VARCHAR(500) DEFAULT NULL COMMENT 'CDN访问URL',
    uploader_id INT DEFAULT NULL COMMENT '上传用户ID',
    biz_type ENUM('question','site','general') NOT NULL DEFAULT 'general' COMMENT '业务类型',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE SET NULL,
    FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_question (question_id),
    INDEX idx_uploader (uploader_id),
    INDEX idx_biz_type (biz_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='媒体资源表';

-- -----------------------------------------------------------
-- 2. 站点配置表：新增 OSS 相关配置项
-- AK/SK 以 AES-256-GCM 加密后存入，Bucket/Endpoint/Region/CDN 明文存储
-- -----------------------------------------------------------
INSERT IGNORE INTO site_settings (setting_key, setting_value, description) VALUES
('oss_access_key_id',     '', 'OSS AccessKey ID（AES-256-GCM加密存储）'),
('oss_access_key_secret', '', 'OSS AccessKey Secret（AES-256-GCM加密存储）'),
('oss_bucket',            '', 'OSS Bucket名称'),
('oss_endpoint',          '', 'OSS Endpoint（如 oss-cn-hangzhou.aliyuncs.com）'),
('oss_region',            '', 'OSS Region（如 cn-hangzhou）'),
('oss_cdn_domain',        '', 'CDN加速域名（可选，如 cdn.example.com）');
