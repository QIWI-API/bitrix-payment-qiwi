<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Sale\PaySystem\Manager;
use \Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

$data = [
    'NAME' => Loc::getMessage('QIWI_KASSA_NAME'),
    'SORT' => 750,
    'IS_AVAILABLE' => function_exists('curl_version'),
    'CODES' => [
        'QIWI_KASSA_SECRET_API_KEY' => [
            'NAME' => Loc::getMessage('QIWI_KASSA_SECRET_API_KEY'),
            'SORT' => 300,
            'GROUP' => 'CONNECT_SETTINGS_QIWI',
        ],
        'QIWI_KASSA_BILL_PREFIX' => [
            'NAME' => Loc::getMessage('QIWI_KASSA_BILL_PREFIX'),
            'SORT' => 400,
            'GROUP' => 'CONNECT_SETTINGS_QIWI',
        ],
        'QIWI_KASSA_BILL_LIFETIME' => [
            'NAME' => Loc::getMessage('QIWI_KASSA_BILL_LIFETIME'),
            'DESCRIPTION' => Loc::getMessage('QIWI_KASSA_BILL_LIFETIME_DESC'),
            'SORT' => 900,
            'GROUP' => 'CONNECT_SETTINGS_QIWI',
            'DEFAULT' => [
                'PROVIDER_VALUE' => '7',
                'PROVIDER_KEY' => 'VALUE',
            ],
        ],
        'QIWI_KASSA_NOTIFY_URL' => [
            'NAME' => Loc::getMessage('QIWI_KASSA_NOTIFY_URL'),
            'SORT' => 1100,
            'DESCRIPTION' => Loc::getMessage('QIWI_KASSA_NOTIFY_URL_DESC'),
            'GROUP' => 'CONNECT_SETTINGS_QIWI',
            'DEFAULT' => [
                'PROVIDER_VALUE' => '/bitrix/tools/sale_ps_result.php?qiwi=notify',
                'PROVIDER_KEY' => 'VALUE',
            ],
            'INPUT' => [
                'TYPE' => 'STRING',
                'VALUE' => '/bitrix/tools/sale_ps_result.php?qiwi=notify',
                'DISABLED' => 'Y',
            ],
        ],
        'QIWI_KASSA_CHANGE_STATUS_PAY' => [
            'NAME' => Loc::getMessage('QIWI_KASSA_CHANGE_STATUS_PAY'),
            'SORT' => 300,
            'GROUP' => 'GENERAL_SETTINGS',
            'INPUT' => [
                'TYPE' => 'Y/N',
            ],
        ],
        'QIWI_KASSA_THEME_CODE' => [
            'NAME' => Loc::getMessage('QIWI_KASSA_THEME_CODE'),
            'SORT' => 400,
            'DESCRIPTION' => Loc::getMessage('QIWI_KASSA_THEME_CODE_DESC'),
            'GROUP' => 'GENERAL_SETTINGS',
        ],
    ],
];
