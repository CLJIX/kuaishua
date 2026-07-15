# "小题快刷"在线刷题网站 - 开发计划

## Context

用户需要从零搭建一个名为"小题快刷"的在线刷题网站，使用原生 PHP + MySQL 开发，支持用户注册登录、题库管理、刷题练习等核心功能。项目要求响应式设计，完美适配 PC 和移动端。

---

## 项目目录结构

```
k:\xtks/
├── config/
│   └── database.php          # 数据库连接配置
├── database/
│   └── schema.sql            # 数据库建表 DDL
├── includes/
│   ├── functions.php         # 公共辅助函数（安全过滤、重定向等）
│   ├── auth.php              # 认证与权限检查
│   └── csrf.php              # CSRF Token 管理
├── models/
│   ├── User.php              # 用户数据模型
│   ├── Category.php          # 分类数据模型
│   ├── Tag.php               # 标签数据模型
│   └── Question.php          # 题目数据模型
├── controllers/
│   ├── AuthController.php    # 注册/登录/登出
│   ├── QuestionController.php# 题目展示与刷题
│   ├── AdminController.php   # 后台管理（题库/分类/标签/导入）
│   └── PracticeController.php# 练习与答题提交
├── views/
│   ├── layouts/
│   │   ├── header.php        # 公共头部（含导航栏）
│   │   └── footer.php        # 公共尾部
│   ├── auth/
│   │   ├── login.php         # 登录页
│   │   └── register.php      # 注册页
│   ├── questions/
│   │   ├── list.php          # 题目列表页
│   │   ├── detail.php        # 题目详情/刷题页
│   │   └── result.php        # 答题结果页
│   ├── admin/
│   │   ├── dashboard.php     # 管理后台首页
│   │   ├── questions.php     # 题目管理列表
│   │   ├── question_edit.php # 题目编辑/新增
│   │   ├── import.php        # CSV 批量导入页
│   │   ├── categories.php    # 分类管理
│   │   └── tags.php          # 标签管理
│   └── home.php              # 网站首页
├── assets/
│   ├── css/
│   │   └── style.css         # 自定义样式
│   └── js/
│       └── app.js            # 前端交互脚本
├── uploads/                  # 上传文件目录
├── index.php                 # 路由入口
└── .htaccess                 # URL 重写规则
```

---

## 数据库设计（MySQL）

共 7 张核心表：

| 表名 | 说明 |
|---|---|
| users | 用户表（含角色字段 role: user/admin） |
| categories | 分类表（支持 parent_id 层级分组） |
| tags | 标签表 |
| questions | 题目主表（类型、题面、答案、解析、分类关联） |
| question_options | 题目选项表（option_label, option_text, is_correct） |
| question_tags | 题目-标签多对多关联表 |
| practice_records | 答题记录表（用户、题目、答案、是否正确、用时） |

关键索引：
- questions.category_id、questions.question_type
- question_options.question_id
- question_tags.tag_id、question_tags.question_id
- practice_records.user_id、practice_records.question_id

---

## 实现任务分解

### Task 1：数据库 DDL 与配置文件
- 创建 `database/schema.sql`（完整建表语句）
- 创建 `config/database.php`（PDO 连接封装）
- 创建 `.htaccess`（URL 重写）

### Task 2：公共基础模块
- 创建 `includes/functions.php`（输入过滤、重定向、flash 消息）
- 创建 `includes/csrf.php`（CSRF Token 生成与验证）
- 创建 `includes/auth.php`（Session 管理、登录状态检查、角色权限验证）

### Task 3：数据模型层
- 创建 `models/User.php`（注册、登录验证、按 ID 查询）
- 创建 `models/Category.php`（CRUD、树形结构查询）
- 创建 `models/Tag.php`（CRUD）
- 创建 `models/Question.php`（CRUD、分页查询、按分类/标签筛选、导入）

### Task 4：控制器层 + 路由入口
- 创建 `controllers/AuthController.php`（注册/登录/登出逻辑）
- 创建 `controllers/QuestionController.php`（题目列表、详情展示）
- 创建 `controllers/PracticeController.php`（提交答案、计算结果）
- 创建 `controllers/AdminController.php`（后台 CRUD、CSV 导入解析）
- 创建 `index.php`（简易路由分发器）

### Task 5：前端页面 - 公共布局与认证页
- 创建 `views/layouts/header.php`（Bootstrap 5 导航栏，响应式）
- 创建 `views/layouts/footer.php`
- 创建 `views/auth/login.php`
- 创建 `views/auth/register.php`
- 创建 `views/home.php`（首页仪表盘）

### Task 6：前端页面 - 刷题与题目展示
- 创建 `views/questions/list.php`（题目列表、筛选、分页）
- 创建 `views/questions/detail.php`（刷题页面）
- 创建 `views/questions/result.php`（答题结果页）

### Task 7：前端页面 - 管理后台
- 创建 `views/admin/dashboard.php`（管理后台概览）
- 创建 `views/admin/questions.php`（题目管理列表）
- 创建 `views/admin/question_edit.php`（题目新增/编辑表单）
- 创建 `views/admin/import.php`（CSV 导入页面及结果反馈）
- 创建 `views/admin/categories.php`（分类管理）
- 创建 `views/admin/tags.php`（标签管理）

### Task 8：静态资源
- 创建 `assets/css/style.css`（自定义响应式样式）
- 创建 `assets/js/app.js`（前端交互脚本）

---

## 关键技术决策

- **前端框架**：Bootstrap 5（CDN 引入），自带响应式栅格和组件
- **路由**：index.php 单一入口，通过 `$_GET['page']` 参数进行简易路由分发
- **CSV 导入**：PHP 原生 `fgetcsv()` 解析，不引入第三方库
- **密码安全**：`password_hash(PASSWORD_DEFAULT)` + `password_verify()`
- **会话安全**：`session_regenerate_id(true)` 防会话固定
- **CSRF**：每个表单携带随机 Token，服务端验证

---

## 验证方案

1. 使用 PHP 内置服务器启动：`php -S localhost:8000`
2. 手动测试流程：注册 → 登录 → 浏览题目 → 刷题 → 查看结果
3. 管理员测试：后台登录 → 添加分类/标签 → 手动添加题目 → CSV 导入 → 验证数据
4. 响应式测试：浏览器缩放模拟移动端查看布局
