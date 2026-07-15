<?php
/**
 * 小题快刷 - 应用入口
 * 所有请求通过 .htaccess 转发到此文件
 * 根据 page 参数进行路由分发
 */

// 加载依赖文件
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

// 启动安全 Session
startSecureSession();

// 获取路由参数
$page   = $_GET['page']   ?? 'home';
$action = $_GET['action'] ?? 'index';

// 路由映射表：page => [controller_file, class_name, allowed_methods]
$routes = [
    'home'      => ['controllers/QuestionController.php', 'QuestionController', ['index']],
    'login'     => ['controllers/AuthController.php',     'AuthController',     ['login']],
    'register'  => ['controllers/AuthController.php',     'AuthController',     ['register']],
    'logout'    => ['controllers/AuthController.php',     'AuthController',     ['logout']],
    'profile'   => ['controllers/ProfileController.php', 'ProfileController', ['index']],
    'questions' => ['controllers/QuestionController.php', 'QuestionController', ['index', 'list', 'detail']],
    'practice'  => ['controllers/PracticeController.php', 'PracticeController', ['index', 'submit', 'result']],
    'admin'     => ['controllers/AdminController.php',    'AdminController',    ['index', 'questions', 'question_edit', 'question_delete', 'import', 'categories', 'tags', 'users']],
];

// 分发路由
if (isset($routes[$page])) {
    [$file, $class, $allowedMethods] = $routes[$page];
    require_once __DIR__ . '/' . $file;
    $controller = new $class();

    // login / register / logout 直接以页面名作为方法名
    if (in_array($page, ['login', 'register', 'logout'], true)) {
        $method = $page;
    } else {
        $method = $action;
    }

    // 方法白名单检查
    if (!in_array($method, $allowedMethods, true)) {
        $method = 'index';
    }

    // 调用对应方法，不存在则回退到 index
    if (method_exists($controller, $method)) {
        $controller->$method();
    } else {
        $controller->index();
    }
} else {
    // 404 页面
    http_response_code(404);
    require_once __DIR__ . '/views/layouts/header.php';
    echo '<div class="container mt-5"><h2>页面未找到</h2><p>您访问的页面不存在。</p></div>';
    require_once __DIR__ . '/views/layouts/footer.php';
}
