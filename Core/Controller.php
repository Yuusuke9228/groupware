<?php
// core/Controller.php
namespace Core;

class Controller
{
    protected $auth;

    public function __construct()
    {
        $this->auth = Auth::getInstance();
    }

    // ビューを表示するメソッド
    protected function view($view, $data = [])
    {
        // データを変数として展開
        extract($data);

        // ヘッダー表示
        require_once __DIR__ . '/../views/layouts/header.php';

        // メインコンテンツ表示
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            throw new \Exception("View {$view} not found");
        }

        // フッター表示
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // リダイレクト
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    // JSONレスポンスを返す
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // GETパラメータを取得
    protected function getQuery($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    // POSTデータを取得
    protected function getPost($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }

        return $_POST[$key] ?? $default;
    }

    // リクエストボディを取得（JSON）
    protected function getRequestBody()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    // CSRFトークンを検証
    protected function validateCsrfToken($token)
    {
        return $token === $_SESSION['csrf_token'];
    }

    // CSRFトークンを生成
    protected function generateCsrfToken()
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
}
