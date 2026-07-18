<?php
/**
 * 轻量 .env 环境配置加载器
 *
 * 功能：
 * - 解析项目根目录 .env 文件，将键值对加载到 $_ENV/$_SERVER（putenv 可选）
 * - 若 .env 不存在或缺少 OSS_MASTER_KEY，自动生成随机主密钥
 * - .env 首行包含 <?php exit; ?> 防止 Nginx 漏配时密钥泄露
 *
 * 使用方式：在 index.php 入口处 require_once 本文件
 */

// .env 文件路径（项目根目录）
$_envPath = __DIR__ . '/../.env';

/**
 * 解析 .env 文件内容
 * 跳过 PHP 标记行、注释行和空行，提取 KEY=VALUE 键值对
 *
 * @param string $content .env 文件原始内容
 * @return array 键值对数组
 */
function _parseEnvContent(string $content): array {
    $vars = [];
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        $line = trim($line);
        // 跳过空行、注释行、PHP 标记行
        if ($line === '' || $line[0] === '#' || str_starts_with($line, '<?php')) {
            continue;
        }
        $eqPos = strpos($line, '=');
        if ($eqPos === false) continue;
        $key = trim(substr($line, 0, $eqPos));
        $val = trim(substr($line, $eqPos + 1));
        // 去除首尾引号
        if (strlen($val) >= 2 && in_array($val[0], ['"', "'"], true)) {
            $val = trim($val, $val[0]);
        }
        if ($key !== '') {
            $vars[$key] = $val;
        }
    }
    return $vars;
}

/**
 * 生成或更新 .env 文件
 * 自动添加 <?php exit; ?> 防护行和随机 OSS_MASTER_KEY
 */
function _ensureEnvFile(string $path): void {
    $key = base64_encode(random_bytes(32));
    $content = "<?php exit; ?>\n# OSS 加密主密钥（系统自动生成，请勿泄露或提交到 Git）\nOSS_MASTER_KEY={$key}\n";
    @file_put_contents($path, $content, LOCK_EX);
    @chmod($path, 0600);
}

// ---- 主逻辑 ----

if (!is_file($_envPath)) {
    // .env 不存在：自动生成
    _ensureEnvFile($_envPath);
}

// 读取并解析
$_envRaw = @file_get_contents($_envPath);
if ($_envRaw === false) {
    // 文件不可读，回退到系统环境变量
    return;
}

$_envVars = _parseEnvContent($_envRaw);

// 检查 OSS_MASTER_KEY 是否存在，缺失则追加
if (!isset($_envVars['OSS_MASTER_KEY']) || $_envVars['OSS_MASTER_KEY'] === '') {
    $newKey = base64_encode(random_bytes(32));
    $_envVars['OSS_MASTER_KEY'] = $newKey;
    // 重新写入完整内容（保留原有其他变量）
    $lines = ["<?php exit; ?>"];
    foreach ($_envVars as $k => $v) {
        $lines[] = "{$k}={$v}";
    }
    @file_put_contents($_envPath, implode("\n", $lines) . "\n", LOCK_EX);
    @chmod($_envPath, 0600);
}

// 注入到 $_ENV 和 $_SERVER（putenv 可能被禁用，作为可选补充）
foreach ($_envVars as $key => $val) {
    $_ENV[$key]    = $val;
    $_SERVER[$key] = $val;
    if (function_exists('putenv')) {
        @putenv("{$key}={$val}");
    }
}
