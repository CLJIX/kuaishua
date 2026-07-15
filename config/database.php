<?php
/**
 * "小题快刷" - 数据库连接配置文件
 * 
 * 使用 PDO 进行数据库操作，支持预处理语句防止 SQL 注入
 * 采用单例模式确保整个请求周期只建立一个数据库连接
 */

// 数据库连接参数
define('DB_HOST', 'localhost');       // 数据库主机地址
define('DB_NAME', 'xtks_db');        // 数据库名称
define('DB_USER', 'root');            // 数据库用户名
define('DB_PASS', '');                // 数据库密码（本地开发环境通常为空）
define('DB_CHARSET', 'utf8mb4');      // 字符集，支持完整 Unicode（含 emoji）

/**
 * 获取数据库 PDO 连接（单例模式）
 * 
 * 首次调用时创建 PDO 连接并缓存，后续调用直接返回已有连接
 * 配置说明：
 *   - ERRMODE_EXCEPTION: 发生错误时抛出异常，便于统一错误处理
 *   - FETCH_ASSOC: 默认以关联数组形式返回查询结果
 *   - EMULATE_PREPARES=false: 使用真正的预处理语句，提升安全性
 * 
 * @return PDO 数据库连接对象
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }
    return $pdo;
}
