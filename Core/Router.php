<?php
// core/Router.php
namespace Core;

class Router
{
    private static $instance = null;
    private $routes = [];
    private $apiRoutes = [];
    private $auth;
    private $basePath = '';

    private function __construct()
    {
        $this->basePath = defined("BASE_PATH") ? BASE_PATH : "";
        $this->auth = Auth::getInstance();
    }

    private function isAjaxRequest()
    {
        $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if ($requestedWith === 'xmlhttprequest') {
            return true;
        }

        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        return strpos($accept, 'application/json') !== false;
    }

    private function sendNoCacheHeaders()
    {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0, s-maxage=0, proxy-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Surrogate-Control: no-store');
        header('CDN-Cache-Control: no-store');
        header('Vary: Cookie, Authorization, X-Requested-With');
    }

    // シングルトンパターンでインスタンスを取得
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 通常のルートを登録（ページ用）
    public function add($method, $path, $handler, $authRequired = false)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'authRequired' => $authRequired
        ];

        return $this;
    }

    // GETルートを登録
    public function get($path, $handler, $authRequired = false)
    {
        return $this->add('GET', $path, $handler, $authRequired);
    }

    // POSTルートを登録
    public function post($path, $handler, $authRequired = false)
    {
        return $this->add('POST', $path, $handler, $authRequired);
    }

    // APIルートを登録（JSON応答用）
    public function api($method, $path, $handler, $authRequired = false)
    {
        $this->apiRoutes[] = [
            'method' => $method,
            'path' => "/api" . $path,
            'handler' => $handler,
            'authRequired' => $authRequired
        ];

        return $this;
    }

    // API GETルートを登録
    public function apiGet($path, $handler, $authRequired = false)
    {
        return $this->api('GET', $path, $handler, $authRequired);
    }

    // API POSTルートを登録
    public function apiPost($path, $handler, $authRequired = false)
    {
        return $this->api('POST', $path, $handler, $authRequired);
    }

    // API DELETEルートを登録
    public function apiDelete($path, $handler, $authRequired = false)
    {
        return $this->api('DELETE', $path, $handler, $authRequired);
    }

    // パスパラメータを抽出
    private function extractParams($route, $path)
    {
        $routeParts = explode('/', trim($route, '/'));
        $pathParts = explode('/', trim($path, '/'));

        if (count($routeParts) !== count($pathParts)) {
            return null;
        }

        $params = [];

        foreach ($routeParts as $index => $routePart) {
            if (strpos($routePart, ':') === 0) {
                $paramName = substr($routePart, 1);
                $params[$paramName] = $pathParts[$index];
            } elseif (preg_match('/^\{(.+)\}$/', $routePart, $m)) {
                $params[$m[1]] = $pathParts[$index];
            } elseif ($routePart !== $pathParts[$index]) {
                return null;
            }
        }

        return $params;
    }

    // ルートの一致をチェック
    private function matchRoute($route, $method, $path)
    {
        if ($route['method'] !== $method) {
            return [false, []];
        }

        // 完全一致の場合
        if ($route['path'] === $path) {
            return [true, []];
        }

        // パラメータ付きルートの場合
        $params = $this->extractParams($route['path'], $path);

        if ($params !== null) {
            return [true, $params];
        }

        return [false, []];
    }

    // リクエストを処理
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $baseDir = dirname($_SERVER['SCRIPT_NAME']);

        // /public を除去（ルートの.htaccessからリダイレクトされた場合）
        $baseDir = preg_replace('#/public$#', '', $baseDir);

        // ベースディレクトリの調整
        if ($baseDir !== '/' && strpos($path, $baseDir) === 0) {
            $path = substr($path, strlen($baseDir));
        }

        // /index.php 配下のURLでも通常ルートとして扱う
        if (strpos($path, '/index.php') === 0) {
            $path = substr($path, strlen('/index.php'));
            if ($path === '') {
                $path = '/';
            }
        }

        // 特別なケース: 組織コードの重複チェックAPI
        if (strpos($path, '/api/organizations/check-code') !== false && $method === 'GET') {
            $controller = new \Controllers\OrganizationController();

            // クエリパラメータをそのまま渡す
            $params = $_GET;
            $response = $controller->apiCheckCodeUnique($params);

            $this->sendNoCacheHeaders();
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }

        // API用ルートを先にチェック
        foreach ($this->apiRoutes as $route) {
            list($matched, $params) = $this->matchRoute($route, $method, $path);

            if ($matched) {
                // 認証が必要なルートの場合
                if ($route['authRequired'] && !$this->auth->check()) {
                    $this->sendNoCacheHeaders();
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['error' => '認証が必要です']);
                    exit;
                }

                // JSONリクエストの処理
                $requestData = [];

                if ($method === 'POST' || $method === 'PUT') {
                    // まず$_POSTを確認
                    if (!empty($_POST)) {
                        $requestData = $_POST;
                    } else {
                        // $_POSTが空の場合はphp://inputからデータを取得
                        $input = file_get_contents('php://input');

                        if (!empty($input)) {
                            $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

                            if (strpos($contentType, 'application/json') !== false) {
                                $requestData = json_decode($input, true) ?? [];
                            } else {
                                // 他のフォーマット（例：x-www-form-urlencoded）
                                parse_str($input, $requestData);
                            }
                        }
                    }
                } else if ($method === 'DELETE') {
                    $input = file_get_contents('php://input');
                    if (!empty($input)) {
                        $requestData = json_decode($input, true) ?? [];
                    }
                }

                // ハンドラを実行
                $response = call_user_func($route['handler'], $params, $requestData);

                // 通常フォームからAPIへ直接送られた場合は、成功時に画面遷移へフォールバックする
                if (($method === 'POST' || $method === 'PUT') && !$this->isAjaxRequest()) {
                    if (!empty($response['success']) && !empty($response['redirect'])) {
                        header('Location: ' . $response['redirect']);
                        exit;
                    }

                    if (!empty($response['error'])) {
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            $_SESSION['error'] = $response['error'];
                        }

                        $fallbackUrl = $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/';
                        header('Location: ' . $fallbackUrl);
                        exit;
                    }
                }

                // JSON応答を返す
                $this->sendNoCacheHeaders();
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
        }

        // 通常ルートをチェック
        foreach ($this->routes as $route) {
            list($matched, $params) = $this->matchRoute($route, $method, $path);

            if ($matched) {
                // 認証が必要なルートの場合
                if ($route['authRequired'] && !$this->auth->check()) {
                    header('Location: ' . $this->basePath . '/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
                    exit;
                }

                // ハンドラを実行
                call_user_func($route['handler'], $params);
                exit;
            }
        }

        // 一致するルートがなかった場合
        header("HTTP/1.0 404 Not Found");
        $this->sendNoCacheHeaders();
        echo "404 Not Found";
    }
}
