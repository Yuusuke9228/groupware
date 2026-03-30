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
        $view = 'help/index';
        $this->view($view, [
            'title' => t('help.title')
        ]);
    }

    public function terms()
    {
        $view = 'help/terms';
        $this->view($view, [
            'title' => t('header.terms')
        ]);
    }

    public function installManual()
    {
        $view = 'help/install_manual';
        $this->view($view, [
            'title' => t('help.install_manual')
        ]);
    }

    public function adminManual()
    {
        $view = 'help/admin_manual';
        $this->view($view, [
            'title' => t('help.admin_manual')
        ]);
    }
}
