<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ManagerComment extends Module
{
    public function __construct()
    {
        $this->name = 'managercomment';
        $this->tab = 'administration';
        $this->version = '0.1.0';
        $this->author = 'Test assignment';
        $this->need_instance = 0;
        $this->bootstrap = true;

        $this->ps_versions_compliancy = array(
            'min' => '1.6.0.0',
            'max' => '1.6.1.99',
        );

        parent::__construct();

        $this->displayName = $this->l('Manager comments');
        $this->description = $this->l('A skeleton module for manager comments on orders.');
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }
}
