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
        $view = get_locale() === 'en' ? 'help/index_en' : 'help/index';
        $this->view($view, [
            'title' => t('help.title')
        ]);
    }

    public function terms()
    {
        $view = get_locale() === 'en' ? 'help/terms_en' : 'help/terms';
        $this->view($view, [
            'title' => t('header.terms')
        ]);
    }

    public function installManual()
    {
        $view = get_locale() === 'en' ? 'help/install_manual_en' : 'help/install_manual';
        $this->view($view, [
            'title' => t('help.install_manual')
        ]);
    }

    public function adminManual()
    {
        $view = get_locale() === 'en' ? 'help/admin_manual_en' : 'help/admin_manual';
        $this->view($view, [
            'title' => t('help.admin_manual')
        ]);
    }
}
