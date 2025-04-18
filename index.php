<?php
// Display errors (only in dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_save_path('../sessions');

// Autoloading & Env Setup
require_once '../vendor/autoload.php';
require_once '../pero/functions/PeroAutoload.php';
require_once '../pero/functions/PeroHelpers.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Load .env
Dotenv\Dotenv::createImmutable(__DIR__)->load();

// Session & CSRF Token
session_start();
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

// Database Connection
global $db;
if(isset($_ENV['DB_DATABASE']) && isset($_ENV['DB_USERNAME']) && isset($_ENV['DB_PASSWORD']) && isset($_ENV['DB_HOST'])){
    $config = [
        'driver'    => $_ENV['DB_DRIVER'],
        'host'      => $_ENV['DB_HOST'],
        'port'      => $_ENV['DB_PORT'],
        'database'  => $_ENV['DB_DATABASE'],
        'username'  => $_ENV['DB_USERNAME'],
        'password'  => $_ENV['DB_PASSWORD'],
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
    ];
    $db = new Capsule;
    $db->addConnection($config);
    $db->setAsGlobal();
    $db->bootEloquent();
}

// Base Config Constants
define('BASE_URI', $_ENV['BASE_URI'] ?? ((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']));
define('BASE_URL', rtrim(BASE_URI, '/') . '/');
define('ADMIN_ROUTE', $_ENV['ADMIN_ROUTE'] ?? '/admin');
define('ADMIN_URL', rtrim(BASE_URI, '/') . ADMIN_ROUTE . '/');
define('ADMIN_FILES', '../pero/');
define('ADMIN_ASSET', 'https://perosite.com/panel/');

// URI Routing
$requestUri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$scriptName = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

if ($scriptName && $scriptName !== '/') {
    $requestUri = str_replace($scriptName, '', $requestUri);
}
$requestUri = $requestUri ?: '/';

// Static Routes Map
$staticRoutes = [
    ADMIN_ROUTE => ADMIN_FILES . 'blogs/posts.php',
    ADMIN_ROUTE . '/auth' => ADMIN_FILES . 'auth/login.php',
    ADMIN_ROUTE . '/auth/login' => ADMIN_FILES . 'auth/login.php',
    ADMIN_ROUTE . '/auth/google-auth' => ADMIN_FILES . 'auth/google.php',
    ADMIN_ROUTE . '/auth/logout' => ADMIN_FILES . 'auth/logout.php',
    ADMIN_ROUTE . '/dashboard' => ADMIN_FILES . 'blogs/posts.php',
    ADMIN_ROUTE . '/categories' => ADMIN_FILES . 'blogs/categories.php',
    ADMIN_ROUTE . '/gallery' => ADMIN_FILES . 'blogs/gallery.php',
    ADMIN_ROUTE . '/routes' => ADMIN_FILES . 'blogs/routes.php',
    ADMIN_ROUTE . '/sitemap' => ADMIN_FILES . 'blogs/sitemap.php',
    ADMIN_ROUTE . '/quote-requests' => ADMIN_FILES . 'enquiry/quote.php',
    ADMIN_ROUTE . '/contact-requests' => ADMIN_FILES . 'enquiry/contact.php',
];

// Static Route Handling
if (isset($staticRoutes[$requestUri])) {
    require_once __DIR__ . '/' . $staticRoutes[$requestUri];
    exit;
}

// Dynamic File Routing
$filename = __DIR__ . ($requestUri === '/' ? '/static/home.php' : '/static' . $requestUri . '.php');
if (is_file($filename)) {
    require_once $filename;
    exit;
}

// Dynamic Route Lookup from DB
if(!empty($db)){
    $route = $db->table('dynamic_routes')->where('route', ltrim($requestUri, '/'))->first();
    
    if ($route) {
        if (!empty($route->redirect)) {
            $url = str_starts_with($route->redirect, 'http') ? $route->redirect : BASE_URL . ltrim($route->redirect, '/');
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: $url");
            exit;
        }
    
        $peroblog = new PeroBlog();
    
        if ($route->entity_type === 'post') {
            $post = $peroblog->getById($route->entity_id);
            if ($post) {
                require __DIR__ . '/post.php';
                exit;
            }
        }
    
        if ($route->entity_type === 'blog_category') {
            $currentPage = max((int)($_GET['page'] ?? 1), 1);
            [$posts, $currentPage, $totalPages, $perPage] = $peroblog->getAllByCategoryId($route->entity_id, $currentPage);
            $categories = $peroblog->getAllCategories();
            if ($posts) {
                require __DIR__ . '/blogs.php';
                exit;
            }
        }
    }
}

// 404 Not Found
http_response_code(404);
require __DIR__ . '/404.php';