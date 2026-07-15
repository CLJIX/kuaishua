-- 修复管理员密码（密码: admin123）
-- 密码哈希由 password_hash('admin123', PASSWORD_DEFAULT) 生成
UPDATE users SET password_hash = '$2y$10$e0MYzXyjpJS7Pd0RVDFKMOgasOnDesEhEIqe4sGmoqgKkYelIqKhe' WHERE username = 'admin';
