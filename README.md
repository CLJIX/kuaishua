# 📝 小题快刷

> 面向学生的轻量级在线刷题平台 —— 基于原生 PHP 构建，支持单选/多选题练习、CSV 批量导入与管理后台。

---

## ✨ 功能特性

### 👤 用户系统
- 用户注册与登录（密码使用 `password_hash` / `password_verify` 安全处理）
- 个人中心：修改密码、修改邮箱

### 📚 题库浏览
- **首页**：题目概览与快速入口
- **题目列表**：支持搜索、分类/标签筛选、分页浏览
- **题目详情**：查看题面、选项与解析

### 🎯 答题系统
- 支持 **单选题** 和 **多选题**
- ⏱️ 答题计时器，记录答题用时
- 📊 答题结果展示：**正确 ✅ / 半对 🟡 / 错误 ❌** 三态判定
- 答错后展示题目解析，辅助学习

### 🔧 管理后台
- **数据概览**：统计卡片（题目数、用户数、答题记录数等）
- **题目管理**：增删改查，支持富文本题面
- **CSV 批量导入**：上传 CSV 文件批量导入题目，提供模板下载，自动检测文件编码
- **分类管理**：树形层级结构，支持无限级分类
- **标签管理**：为题目打标签，支持多标签关联
- **用户管理**：批量新建用户、删除用户、修改角色、重置密码、二次密码验证

### 🛡️ 安全防护
- **CSRF 防护**：所有表单提交携带 CSRF Token，使用 `hash_equals` 时间安全比较
- **XSS 防护**：`e()` 函数转义 HTML 特殊字符 + `purify()` 函数净化富文本内容
- **SQL 注入防护**：统一使用 PDO 预处理语句，禁用模拟预处理
- **Session 安全**：`httponly` Cookie、`session_regenerate_id` 防会话固定、`SameSite=Lax` 策略

---

## 🛠️ 技术栈

| 层次 | 技术选型 |
|------|----------|
| 后端 | 原生 PHP（无框架）、MySQL + PDO（单例模式） |
| 前端 | Bootstrap 5 + Bootstrap Icons（CDN 引入） |
| 安全 | `password_hash` / `password_verify`、`session_regenerate_id`、`httponly` Cookie、CSRF Token |

---

## 📁 项目结构

```
xtks/
├── assets/                     # 静态资源
│   ├── css/
│   │   └── style.css           # 全局样式
│   ├── js/
│   │   └── app.js              # 前端脚本
│   └── csv_template.csv        # CSV 导入模板
├── config/
│   └── database.php            # 数据库连接配置（PDO 单例）
├── controllers/                # 控制器层
│   ├── AdminController.php     # 管理后台（题目/分类/标签/用户/导入/概览）
│   ├── AuthController.php      # 认证（登录/注册/登出）
│   ├── PracticeController.php  # 答题（答题/提交/结果）
│   ├── ProfileController.php   # 个人中心
│   └── QuestionController.php  # 题目浏览（首页/列表/详情）
├── database/
│   ├── schema.sql              # 建表脚本 + 初始数据
│   └── fix_admin_password.sql  # 管理员密码修复脚本
├── includes/                   # 公共模块
│   ├── auth.php                # 认证函数（登录/登出/权限检查）
│   ├── csrf.php                # CSRF Token 生成与验证
│   └── functions.php           # 辅助函数（Session/转义/重定向/Flash）
├── models/                     # 数据模型层
│   ├── Category.php            # 分类模型（树形结构）
│   ├── Question.php            # 题目模型（含选项操作）
│   ├── Tag.php                 # 标签模型
│   └── User.php                # 用户模型
├── uploads/                    # 文件上传目录
├── views/                      # 视图层
│   ├── admin/                  # 管理后台页面
│   │   ├── _flash.php          # Flash 消息组件
│   │   ├── _sidebar.php        # 侧边栏组件
│   │   ├── categories.php      # 分类管理
│   │   ├── dashboard.php       # 数据概览
│   │   ├── import.php          # CSV 导入
│   │   ├── question_edit.php   # 题目编辑
│   │   ├── questions.php       # 题目列表
│   │   ├── tags.php            # 标签管理
│   │   ├── users.php           # 用户管理
│   │   └── users_verify.php    # 用户二次密码验证
│   ├── auth/                   # 认证页面
│   │   ├── login.php           # 登录页
│   │   └── register.php        # 注册页
│   ├── layouts/                # 布局模板
│   │   ├── header.php          # 页头
│   │   └── footer.php          # 页脚
│   ├── questions/              # 题目相关页面
│   │   ├── detail.php          # 题目详情
│   │   ├── list.php            # 题目列表
│   │   └── result.php          # 答题结果
│   ├── home.php                # 首页
│   └── profile.php             # 个人中心
├── .htaccess                   # Apache URL 重写规则
├── index.php                   # 应用入口（路由分发）
├── fix_password.php            # 管理员密码重置工具
└── PLAN.md                     # 项目规划文档
```

---

## 🖥️ 环境要求

- **PHP** >= 8.0
- **MySQL** >= 5.7
- **Web 服务器**：Apache（需启用 `mod_rewrite`）或 Nginx

---

## 🚀 安装部署

### 1. 获取项目

将项目克隆或下载到 Web 服务器根目录：

```bash
git clone <仓库地址> /var/www/xtks
```

### 2. 配置数据库连接

编辑 `config/database.php`，修改以下常量：

```php
define('DB_HOST', 'localhost');    // 数据库主机地址
define('DB_NAME', 'xtks_db');     // 数据库名称
define('DB_USER', 'root');         // 数据库用户名
define('DB_PASS', '');             // 数据库密码
define('DB_CHARSET', 'utf8mb4');   // 字符集
```

### 3. 导入数据库

在 MySQL 中执行建表脚本：

```bash
mysql -u root -p < database/schema.sql
```

该脚本会自动创建 `xtks_db` 数据库及所有数据表，并插入默认管理员账号。

### 4. 默认管理员账号

| 用户名 | 密码 |
|--------|------|
| `admin` | `admin123` |

> ⚠️ 首次登录后请立即修改默认密码！

### 5. 配置 Web 服务器

#### 快速启动（PHP 内置服务器）

开发环境可使用 PHP 内置服务器快速启动：

```bash
php -S localhost:8000
```

访问 `http://localhost:8000` 即可使用。

#### Apache

项目已包含 `.htaccess` 文件，确保 Apache 启用了 `mod_rewrite` 模块：

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx

在 Nginx 配置中添加 URL 重写规则：

```nginx
location / {
    try_files $uri $uri/ /index.php?page=$uri&$args;
}
```

---

## 📖 使用说明

### CSV 批量导入格式

CSV 文件需包含 **11 列**，格式如下：

| 列号 | 字段 | 说明 |
|------|------|------|
| 1 | 题目类型 | `single`（单选）或 `multiple`（多选） |
| 2 | 题面 | 题目内容，支持 HTML 标签 |
| 3 | 选项A | 选项 A 的内容 |
| 4 | 选项B | 选项 B 的内容 |
| 5 | 选项C | 选项 C 的内容 |
| 6 | 选项D | 选项 D 的内容 |
| 7 | 正确答案 | 正确选项字母，多选题用逗号分隔（如 `A,B,C`） |
| 8 | 解析 | 题目解析，答错后展示 |
| 9 | 难度 | `1`=简单、`2`=中等、`3`=困难 |
| 10 | 分类名称 | 所属分类，留空则不归类 |
| 11 | 标签 | 标签名称，多个标签用逗号分隔并用双引号包裹（如 `"HTML,HTML5,前端"`） |

**示例：**

```csv
题目类型,题面,选项A,选项B,选项C,选项D,正确答案,解析,难度,分类名称,标签
single,PHP中哪个函数用于加密密码？,md5,password_hash,sha256,base64,B,password_hash是PHP推荐的密码加密函数,2,PHP基础,安全
multiple,以下哪些是合法的PHP数据类型？,string,integer,float,character,"A,B,C",PHP不支持character类型,1,PHP基础,数据类型
```

> 💡 项目提供标准模板文件 `assets/csv_template.csv` 可供下载参考。

### 管理后台访问

登录后以管理员身份访问：

```
http://your-domain/index.php?page=admin&action=index
```

侧边栏提供以下管理入口：
- 数据概览 (`action=index`)
- 题目管理 (`action=questions`)
- CSV 导入 (`action=import`)
- 分类管理 (`action=categories`)
- 标签管理 (`action=tags`)
- 用户管理 (`action=users`)

### 多选题半对判定规则

多选题答题结果采用 **三态判定**：

| 状态 | 条件 |
|------|------|
| ✅ 正确 | 用户选择的选项与所有正确选项 **完全一致** |
| 🟡 半对 | 用户选择的选项 **全部正确**，但 **未选全** 所有正确答案（数据库 `is_correct=0`，前端橙色标识） |
| ❌ 错误 | 用户选择的选项中 **包含了错误选项**，或未作答 |

> 💡 半对状态不计入正确率统计，结果页通过 `.result-partial` 样式类渲染。

---

## 🗺️ 路由说明

所有请求通过 `index.php` 入口分发，路由参数为 `page` 和 `action`：

| 路由 | Action | 功能说明 |
|------|--------|----------|
| `?page=home` | `index` | 🏠 首页 |
| `?page=login` | — | 🔑 登录页 |
| `?page=register` | — | 📝 注册页 |
| `?page=logout` | — | 🚪 登出 |
| `?page=questions&action=list` | `list` | 📋 题目列表（搜索/筛选/分页） |
| `?page=questions&action=detail&id=X` | `detail` | 🔍 题目详情 |
| `?page=practice` | `index` | 🎯 开始答题 |
| `?page=practice&action=submit` | `submit` | 📨 提交答案 |
| `?page=practice&action=result` | `result` | 📊 答题结果 |
| `?page=profile` | `index` | 👤 个人中心 |
| `?page=admin&action=index` | `index` | 📈 管理后台 - 数据概览 |
| `?page=admin&action=questions` | `questions` | 📝 管理后台 - 题目管理 |
| `?page=admin&action=question_edit` | `question_edit` | ✏️ 管理后台 - 编辑题目 |
| `?page=admin&action=question_delete` | `question_delete` | 🗑️ 管理后台 - 删除题目 |
| `?page=admin&action=import` | `import` | 📥 管理后台 - CSV 导入 |
| `?page=admin&action=categories` | `categories` | 🌳 管理后台 - 分类管理 |
| `?page=admin&action=tags` | `tags` | 🏷️ 管理后台 - 标签管理 |
| `?page=admin&action=users` | `users` | 👥 管理后台 - 用户管理 |

---

## 🗄️ 数据库表结构

共 7 张核心表，使用 InnoDB 引擎，支持外键和事务：

| 表名 | 说明 |
|------|------|
| `users` | 用户表（用户名、邮箱、密码哈希、角色 user/admin） |
| `categories` | 分类表（树形结构，`parent_id` 自引用，支持无限层级） |
| `tags` | 标签表 |
| `questions` | 题目主表（题型 single/multiple、题面、解析、难度 1-3、分类关联） |
| `question_options` | 题目选项表（option_label、option_text、is_correct） |
| `question_tags` | 题目-标签关联表（多对多） |
| `practice_records` | 答题记录表（用户、题目、答案、是否正确、用时） |

**关键索引**：`questions.category_id`、`questions.question_type`、`question_options.question_id`、`practice_records(user_id, question_id)` 联合索引等。

---

## 📄 许可证

本项目仅供学习参考使用。

---

## 🧪 快速验证

安装完成后可按以下流程进行功能验证：

1. 启动服务：`php -S localhost:8000`
2. **用户流程**：注册 → 登录 → 浏览题目 → 刷题 → 查看结果
3. **管理员流程**：后台登录 → 添加分类/标签 → 手动添加题目 → CSV 导入 → 验证数据
4. **响应式测试**：浏览器缩放或移动端查看布局适配效果
