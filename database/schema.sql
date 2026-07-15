-- ============================================================
-- "小题快刷"在线刷题系统 - 数据库建表脚本
-- 数据库名: xtks_db
-- 字符集: utf8mb4
-- 存储引擎: InnoDB（支持外键和事务）
-- ============================================================

-- 创建数据库（如尚未创建）
CREATE DATABASE IF NOT EXISTS xtks_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE xtks_db;

-- -----------------------------------------------------------
-- 1. 用户表
-- 存储用户账户信息，role 字段区分普通用户和管理员
-- -----------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名，唯一',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT '邮箱地址，唯一',
    password_hash VARCHAR(255) NOT NULL COMMENT '加密后的密码哈希',
    role ENUM('user','admin') NOT NULL DEFAULT 'user' COMMENT '角色: user=普通用户, admin=管理员',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- -----------------------------------------------------------
-- 2. 分类表（支持层级结构）
-- parent_id 指向自身表实现树形分类，支持无限层级
-- -----------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '分类名称',
    parent_id INT DEFAULT NULL COMMENT '父分类ID，NULL表示顶级分类',
    description TEXT COMMENT '分类描述',
    sort_order INT DEFAULT 0 COMMENT '排序权重，数值越小越靠前',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分类表（支持层级）';

-- -----------------------------------------------------------
-- 3. 标签表
-- 用于给题目标记知识点标签，支持多对多关联
-- -----------------------------------------------------------
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE COMMENT '标签名称，唯一',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='标签表';

-- -----------------------------------------------------------
-- 4. 题目主表
-- 存储题目核心信息：题型、题面、答案解析、难度、所属分类
-- -----------------------------------------------------------
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_type ENUM('single','multiple') NOT NULL COMMENT '题型: single=单选题, multiple=多选题',
    content TEXT NOT NULL COMMENT '题面内容，支持HTML富文本',
    explanation TEXT COMMENT '答案解析，答错后展示',
    difficulty TINYINT DEFAULT 1 COMMENT '难度等级: 1=简单, 2=中等, 3=困难',
    category_id INT DEFAULT NULL COMMENT '所属分类ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_type (question_type),
    INDEX idx_difficulty (difficulty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='题目主表';

-- -----------------------------------------------------------
-- 5. 题目选项表
-- 存储每道题的选项内容，is_correct 标记正确答案
-- 一道题可以有多个选项（A/B/C/D...），多选题可有多个正确答案
-- -----------------------------------------------------------
CREATE TABLE question_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL COMMENT '所属题目ID',
    option_label CHAR(1) NOT NULL COMMENT '选项标签: A, B, C, D...',
    option_text TEXT NOT NULL COMMENT '选项文本内容',
    is_correct TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否为正确答案: 0=否, 1=是',
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='题目选项表';

-- -----------------------------------------------------------
-- 6. 题目-标签关联表（多对多）
-- 一道题可以关联多个标签，一个标签也可以关联多道题
-- -----------------------------------------------------------
CREATE TABLE question_tags (
    question_id INT NOT NULL COMMENT '题目ID',
    tag_id INT NOT NULL COMMENT '标签ID',
    PRIMARY KEY (question_id, tag_id),
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    INDEX idx_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='题目-标签关联表';

-- -----------------------------------------------------------
-- 7. 答题记录表
-- 记录用户每次答题的结果，用于统计和分析学习进度
-- -----------------------------------------------------------
CREATE TABLE practice_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '答题用户ID',
    question_id INT NOT NULL COMMENT '题目ID',
    user_answer TEXT COMMENT '用户提交的答案（多选时逗号分隔，如 A,B）',
    is_correct TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否答对: 0=错误, 1=正确',
    time_spent INT DEFAULT 0 COMMENT '答题用时（秒）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '答题时间',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_question (question_id),
    INDEX idx_user_question (user_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='答题记录表';

-- ============================================================
-- 初始化数据
-- ============================================================

-- 默认管理员账户（密码: admin123）
-- 密码哈希由 password_hash('admin123', PASSWORD_DEFAULT) 生成
INSERT INTO users (username, email, password_hash, role) VALUES 
('admin', 'admin@xiaotikuaidshua.com', '$2y$10$e0MYzXyjpJS7Pd0RVDFKMOgasOnDesEhEIqe4sGmoqgKkYelIqKhe', 'admin');
