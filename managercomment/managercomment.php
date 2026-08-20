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

        $this->displayName = $this->l('Комментарии менеджеров');
        $this->description = $this->l('Внутренние комментарии менеджеров к заказам.');
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

    // ПОЛУЧЕНИЕ КОММЕНТАРИЕВ КОНКРЕТНОГО ЗАКАЗА
    private function getOrderComments($idOrder)
    {
        $query = new DbQuery();

        $query->select( //перечисление возвращаемых столбцов
            'mc.id_manager_comment,
            mc.id_order,
            mc.id_employee,
            mc.employee_name,
            mc.comment,
            mc.date_add,
            mc.date_upd'
        );
        $query->from('manager_comment', 'mc');
        $query->where('mc.id_order = ' . (int) $idOrder);
        $query->orderBy('mc.date_add ASC, mc.id_manager_comment ASC');

        return Db::getInstance()->executeS($query);
    }

    // BACKEND ВАЛИДАЦИЯ ТЕКСТА КОММЕНТАРИЯ
    private function getCommentValidationError($comment)
    {
        if (!is_string($comment)) {
            return $this->l('Комментарий должен быть текстом.');
        }

        $comment = trim($comment);
        $length = Tools::strlen($comment);

        if ($length < 5) {
            return $this->l('Комментарий должен содержать не менее 5 символов.');
        }

        if ($length > 500) {
            return $this->l('Комментарий должен содержать не более 500 символов.');
        }

        return '';
    }

    // СОХРАНЕНИЕ НОВОГО КОММЕНТАРИЯ
    private function addComment(
        $idOrder,
        $idEmployee,
        $employeeName,
        $comment
    ) {
        $currentDate = date('Y-m-d H:i:s');

        return Db::getInstance()->insert(
            'manager_comment',
            array(
                'id_order' => (int) $idOrder,
                'id_employee' => (int) $idEmployee,
                'employee_name' => pSQL($employeeName),
                'comment' => pSQL($comment),
                'date_add' => $currentDate,
                'date_upd' => $currentDate,
            )
        );
    }

    // ПОЛУЧЕНИЕ ОДНОГО КОММЕНТАРИЯ КОНКРЕТНОГО ЗАКАЗА
    private function getOrderCommentById($idComment, $idOrder)
    {
        $query = new DbQuery();

        $query->select(
            'mc.id_manager_comment,
            mc.id_order,
            mc.id_employee,
            mc.employee_name,
            mc.comment,
            mc.date_add,
            mc.date_upd'
        );
        $query->from('manager_comment', 'mc');
        $query->where(
            'mc.id_manager_comment = ' . (int) $idComment
        );
        $query->where(
            'mc.id_order = ' . (int) $idOrder
        );

        return Db::getInstance()->getRow($query);
    }

    // ОБНОВЛЕНИЕ СОБСТВЕННОГО КОММЕНТАРИЯ
    private function updateComment(
        $idComment,
        $idOrder,
        $idEmployee,
        $comment
    ) {
        $where = '`id_manager_comment` = ' . (int) $idComment
            . ' AND `id_order` = ' . (int) $idOrder
            . ' AND `id_employee` = ' . (int) $idEmployee;

        return Db::getInstance()->update(
            'manager_comment',
            array(
                'comment' => pSQL($comment),
                'date_upd' => date('Y-m-d H:i:s'),
            ),
            $where,
            1
        );
    }

    // УДАЛЕНИЕ СОБСТВЕННОГО КОММЕНТАРИЯ
    private function deleteComment(
        $idComment,
        $idOrder,
        $idEmployee
    ) {
        $where = '`id_manager_comment` = ' . (int) $idComment
            . ' AND `id_order` = ' . (int) $idOrder
            . ' AND `id_employee` = ' . (int) $idEmployee;

        return Db::getInstance()->delete(
            'manager_comment',
            $where,
            1
        );
    }

    // ОБРАБОТКА УДАЛЕНИЯ КОММЕНТАРИЯ
    private function processDeleteComment($idOrder, $idComment)
    {
        $idOrder = (int) $idOrder;
        $idComment = (int) $idComment;

        if ($idOrder <= 0 || $idComment <= 0) {
            return $this->l('Передан неверный идентификатор комментария.');
        }

        $employee = $this->context->employee;

        if (!$employee || !Validate::isLoadedObject($employee)) {
            return $this->l('Не удалось определить текущего сотрудника.');
        }

        $storedComment = $this->getOrderCommentById(
            $idComment,
            $idOrder
        );

        if (!$storedComment) {
            return $this->l('Комментарий не найден.');
        }

        if ((int) $storedComment['id_employee'] !== (int) $employee->id) {
            return $this->l(
                'Вы можете удалять только собственные комментарии.'
            );
        }

        $isDeleted = $this->deleteComment(
            $idComment,
            $idOrder,
            (int) $employee->id
        );

        if (!$isDeleted) {
            return $this->l('Не удалось удалить комментарий.');
        }

        return '';
    }

    // ОБРАБОТКА ДОБАВЛЕНИЯ КОММЕНТАРИЯ
    private function processAddComment($idOrder, $comment)
    {
        $validationError = $this->getCommentValidationError($comment);

        if ($validationError !== '') {
            return $validationError;
        }

        $order = new Order((int) $idOrder);

        if (!Validate::isLoadedObject($order)) {
            return $this->l('Заказ не найден.');
        }

        $employee = $this->context->employee;

        if (!$employee || !Validate::isLoadedObject($employee)) {
            return $this->l('Не удалось определить текущего сотрудника.');
        }

        $employeeName = trim(
            $employee->firstname . ' ' . $employee->lastname
        );

        $isAdded = $this->addComment(
            (int) $order->id,
            (int) $employee->id,
            $employeeName,
            trim($comment)
        );

        if (!$isAdded) {
            return $this->l('Не удалось сохранить комментарий.');
        }

        return '';
    }

    // ОБРАБОТКА РЕДАКТИРОВАНИЯ КОММЕНТАРИЯ
    private function processEditComment(
        $idOrder,
        $idComment,
        $comment
    ) {
        $idOrder = (int) $idOrder;
        $idComment = (int) $idComment;

        if ($idOrder <= 0 || $idComment <= 0) {
            return $this->l('Передан неверный идентификатор комментария.');
        }

        $employee = $this->context->employee;

        if (!$employee || !Validate::isLoadedObject($employee)) {
            return $this->l('Не удалось определить текущего сотрудника.');
        }

        $storedComment = $this->getOrderCommentById(
            $idComment,
            $idOrder
        );

        if (!$storedComment) {
            return $this->l('Комментарий не найден.');
        }

        if ((int) $storedComment['id_employee'] !== (int) $employee->id) {
            return $this->l(
                'Вы можете редактировать только собственные комментарии.'
            );
        }

        $validationError = $this->getCommentValidationError($comment);

        if ($validationError !== '') {
            return $validationError;
        }

        $isUpdated = $this->updateComment(
            $idComment,
            $idOrder,
            (int) $employee->id,
            trim($comment)
        );

        if (!$isUpdated) {
            return $this->l('Не удалось обновить комментарий.');
        }

        return '';
    }

    // ВЫВОД И ОБРАБОТКА КОММЕНТАРИЕВ НА СТРАНИЦЕ ЗАКАЗА
    public function hookDisplayAdminOrder($params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $idOrder = (int) $params['id_order'];
        $errorMessage = '';
        $commentValue = '';
        $editCommentId = 0;
        $editCommentValue = '';

        $isPostRequest = isset($_SERVER['REQUEST_METHOD'])
            && $_SERVER['REQUEST_METHOD'] === 'POST';

        if ($isPostRequest) {
            if (Tools::isSubmit('submitManagerComment')) {
                $submittedComment = Tools::getValue('manager_comment');

                if (is_string($submittedComment)) {
                    $commentValue = $submittedComment;
                }

                $errorMessage = $this->processAddComment(
                    $idOrder,
                    $submittedComment
                );

                if ($errorMessage === '') {
                    $redirectUrl = $this->context->link
                        ->getAdminLink('AdminOrders')
                        . '&vieworder'
                        . '&id_order=' . $idOrder
                        . '&manager_comment_added=1';

                    Tools::redirectAdmin($redirectUrl);
                }
            } elseif (Tools::isSubmit('submitManagerCommentEdit')) {
                $editCommentId = (int) Tools::getValue(
                    'id_manager_comment'
                );

                $submittedEditComment = Tools::getValue(
                    'manager_comment_edit'
                );

                if (is_string($submittedEditComment)) {
                    $editCommentValue = $submittedEditComment;
                }

                $errorMessage = $this->processEditComment(
                    $idOrder,
                    $editCommentId,
                    $submittedEditComment
                );

                if ($errorMessage === '') {
                    $redirectUrl = $this->context->link
                        ->getAdminLink('AdminOrders')
                        . '&vieworder'
                        . '&id_order=' . $idOrder
                        . '&manager_comment_updated=1';

                    Tools::redirectAdmin($redirectUrl);
                }
            } elseif (Tools::isSubmit('submitManagerCommentDelete')) {
                $deleteCommentId = (int) Tools::getValue(
                    'id_manager_comment'
                );

                $errorMessage = $this->processDeleteComment(
                    $idOrder,
                    $deleteCommentId
                );

                if ($errorMessage === '') {
                    $redirectUrl = $this->context->link
                        ->getAdminLink('AdminOrders')
                        . '&vieworder'
                        . '&id_order=' . $idOrder
                        . '&manager_comment_deleted=1';

                    Tools::redirectAdmin($redirectUrl);
                }
            }
        }

        $comments = $this->getOrderComments($idOrder);
        $successMessage = '';

        if (Tools::getValue('manager_comment_added') === '1') {
            $successMessage = $this->l('Комментарий успешно добавлен.');
        } elseif (Tools::getValue('manager_comment_updated') === '1') {
            $successMessage = $this->l('Комментарий успешно обновлён.');
        } elseif (Tools::getValue('manager_comment_deleted') === '1') {
            $successMessage = $this->l('Комментарий успешно удалён.');
        }

        $formAction = $this->context->link->getAdminLink('AdminOrders')
            . '&vieworder'
            . '&id_order=' . $idOrder;

        $currentEmployeeId = 0;

        if (
            $this->context->employee
            && Validate::isLoadedObject($this->context->employee)
        ) {
            $currentEmployeeId = (int) $this->context->employee->id;
        }

        $this->context->smarty->assign(array(
            'manager_comments' => $comments,
            'manager_comment_error' => $errorMessage,
            'manager_comment_success' => $successMessage,
            'manager_comment_value' => $commentValue,
            'manager_comment_form_action' => $formAction,
            'manager_comment_edit_id' => $editCommentId,
            'manager_comment_edit_value' => $editCommentValue,
            'manager_comment_current_employee_id' => $currentEmployeeId,
        ));

        return $this->display(
            __FILE__,
            'views/templates/hook/displayAdminOrder.tpl'
        );
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
