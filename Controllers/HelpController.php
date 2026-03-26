<?php
namespace Controllers;

use Core\Controller;

class HelpController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->view('help/index', [
            'title' => 'ヘルプ'
        ]);
    }

    public function terms()
    {
        $this->view('help/terms', [
            'title' => '利用規約'
        ]);
    }

    public function installManual()
    {
        $this->view('help/install_manual', [
            'title' => 'インストールマニュアル'
        ]);
    }

    public function adminManual()
    {
        $this->view('help/admin_manual', [
            'title' => '管理者マニュアル'
        ]);
    }
}
