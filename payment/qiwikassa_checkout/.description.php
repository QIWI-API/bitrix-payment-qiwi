<?php

if (! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$data = [
    'NAME'         => Loc::getMessage('QIWI_KASSA_NAME'),
    'SORT'         => 750,
    'IS_AVAILABLE' => function_exists('curl_version'),
    'CODES'        => [
        'QIWI_KASSA_NOTIFY_URL'     => [
            'NAME'        => Loc::getMessage('QIWI_KASSA_NOTIFY_URL'),
            'SORT'        => 100,
            'DESCRIPTION' => Loc::getMessage('QIWI_KASSA_NOTIFY_URL_DESC'),
            'GROUP'       => 'CONNECT_SETTINGS_QIWI',
            'DEFAULT'     => [
                'PROVIDER_VALUE' => '/bitrix/tools/sale_ps_result.php?qiwi=notify',
                'PROVIDER_KEY'   => 'VALUE',
            ],
            'INPUT'       => [
                'TYPE'     => 'STRING',
                'VALUE'    => '/bitrix/tools/sale_ps_result.php?qiwi=notify',
                'DISABLED' => 'Y',
            ],
        ],
        'QIWI_KASSA_SECRET_API_KEY' => [
            'NAME'        => Loc::getMessage('QIWI_KASSA_SECRET_API_KEY'),
            'SORT'        => 200,
            'DESCRIPTION' => Loc::getMessage('QIWI_KASSA_SECRET_API_KEY_DESC'),
            'GROUP'       => 'CONNECT_SETTINGS_QIWI',
        ],
        'QIWI_KASSA_BILL_LIFETIME'  => [
            'NAME'        => Loc::getMessage('QIWI_KASSA_BILL_LIFETIME'),
            'SORT'        => 500,
            'DESCRIPTION' => Loc::getMessage('QIWI_KASSA_BILL_LIFETIME_DESC'),
            'GROUP'       => 'CONNECT_SETTINGS_QIWI',
            'DEFAULT'     => [
                'PROVIDER_VALUE' => '45',
                'PROVIDER_KEY'   => 'VALUE',
            ],
        ],
        'QIWI_KASSA_THEME_CODE'     => [
            'NAME'        => Loc::getMessage('QIWI_KASSA_THEME_CODE'),
            'SORT'        => 600,
            'DESCRIPTION' => Loc::getMessage('QIWI_KASSA_THEME_CODE_DESC'),
            'GROUP'       => 'CONNECT_SETTINGS_QIWI',
            'DEFAULT'     => [
                'PROVIDER_VALUE' => '',
                'PROVIDER_KEY'   => 'INPUT',
            ],
        ],
        'QIWI_KASSA_USE_POPUP'      => [
            'NAME'    => Loc::getMessage('QIWI_KASSA_USE_POPUP'),
            'SORT'    => 700,
            'GROUP'   => 'CONNECT_SETTINGS_QIWI',
            'INPUT'   => [
                'TYPE' => 'Y/N',
            ],
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'N',
                'PROVIDER_KEY'   => 'INPUT',
            ],
        ],
        'QIWI_KASSA_USE_DEBUG'      => [
            'NAME'    => Loc::getMessage('QIWI_KASSA_USE_DEBUG'), 'SORT' => 800, 'GROUP' => 'CONNECT_SETTINGS_QIWI',
            'INPUT'   => [
                'TYPE' => 'Y/N',
            ],
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'N',
                'PROVIDER_KEY'   => 'INPUT',
            ],
        ],
    ],
];
