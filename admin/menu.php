<?php
IncludeModuleLangFile(__FILE__);
if ($APPLICATION->GetGroupRight('store') > 'D') {
    return [
        'parent_menu' => 'global_menu_services',
        'sort'        => 100,
        'url'         => 'qiwi_kassa.php?lang='.LANGUAGE_ID,
        'text'        => GetMessage('QIWI_KASSA_MENU_MAIN'),
        'title'       => GetMessage('QIWI_KASSA_MENU_MAIN_TITLE'),
        'icon'        => 'workflow_menu_icon',
        'page_icon'   => 'workflow_page_icon',
        'items_id'    => 'menu_qiwi_kassa',
        'items'       => [],
    ];
}

return false;
