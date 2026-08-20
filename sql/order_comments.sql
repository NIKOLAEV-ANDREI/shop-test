-- 1. Последние 100 комментариев менеджеров
SELECT
    mc.`id_order` AS `order_id`,
    o.`date_add` AS `order_date`,
    CONCAT(c.`firstname`, ' ', c.`lastname`) AS `customer_name`,
    o.`total_paid_tax_incl` AS `order_total`,
    mc.`comment`,
    mc.`employee_name`,
    mc.`date_add` AS `comment_date_add`,
    mc.`date_upd` AS `comment_date_upd`
FROM `ps_manager_comment` mc
INNER JOIN `ps_orders` o
    ON o.`id_order` = mc.`id_order`
INNER JOIN `ps_customer` c
    ON c.`id_customer` = o.`id_customer`
ORDER BY
    mc.`date_add` DESC,
    mc.`id_manager_comment` DESC
LIMIT 100;

-- 2. Топ 5 заказов по количеству комментариев
SELECT
    mc.`id_order` AS `order_id`,
    COUNT(*) AS `comment_count`,
    MIN(mc.`date_add`) AS `first_comment_date`,
    MAX(mc.`date_add`) AS `last_comment_date`
FROM `ps_manager_comment` mc
GROUP BY mc.`id_order`
ORDER BY
    `comment_count` DESC,
    `last_comment_date` DESC,
    mc.`id_order` DESC
LIMIT 5;

-- 3. Последние 3 заказа без комментариев менеджеров
SELECT
    o.`id_order` AS `order_id`,
    o.`date_add` AS `order_date`
FROM `ps_orders` o
WHERE NOT EXISTS (
    SELECT 1
    FROM `ps_manager_comment` mc
    WHERE mc.`id_order` = o.`id_order`
)
ORDER BY
    o.`date_add` DESC,
    o.`id_order` DESC
LIMIT 3;