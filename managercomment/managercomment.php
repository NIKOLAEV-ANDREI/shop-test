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

    //МЕТОД СОЗДАНИЯ ТАБЛИЦЫ
    private function installDatabase()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'manager_comment` (
            `id_manager_comment` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_order` INT(10) UNSIGNED NOT NULL,
            `id_employee` INT(10) UNSIGNED NOT NULL,
            `employee_name` VARCHAR(255) NOT NULL,
            `comment` VARCHAR(500) NOT NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_manager_comment`),
            KEY `idx_order_date` (`id_order`, `date_add`),
            KEY `idx_date_add` (`date_add`),
            KEY `idx_employee` (`id_employee`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    //МЕТОД УДАЛЕНИЯ ТАБЛИЦЫ
    private function uninstallDatabase()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'manager_comment`';

        return Db::getInstance()->execute($sql); //получение подключения PrestaShop к базе + возвращение результата выполнения
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->installDatabase()) {
            parent::uninstall();

            return false;
        }

        if (!$this->registerHook('displayAdminOrder')) {
            $this->uninstallDatabase();
            parent::uninstall();

            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!$this->uninstallDatabase()) {
            return false;
        }

        return parent::uninstall();
    }
}
