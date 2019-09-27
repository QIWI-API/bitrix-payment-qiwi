<?php

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

// Validate commerce modules exists.
try {
    if (! Loader::includeModule('sale') || ! Loader::includeModule('catalog')) {
        return;
    }
} catch (LoaderException $e) {
    return;
}

// Get version.
include 'install/version.php';

/** @var string CLIENT_NAME The client name. */
if (! defined('CLIENT_NAME')) {
    define('CLIENT_NAME', '1C Bitrix');
}

/** @var string CLIENT_VERSION The client version. */
if (! defined('CLIENT_VERSION')) {
    define('CLIENT_VERSION', $arModuleVersion['VERSION']);
}

unset($arModuleVersion);

// Autoload if needed.
if (! class_exists('Curl\Curl')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Curl' . DIRECTORY_SEPARATOR . 'Curl.php';
}

if (! class_exists('Qiwi\Api\BillPaymentsException')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'qiwi' . DIRECTORY_SEPARATOR . 'bill-payments-php-sdk' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'BillPaymentsException.php';
}

if (! class_exists('Qiwi\Api\BillPayments')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'qiwi' . DIRECTORY_SEPARATOR . 'bill-payments-php-sdk' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'BillPayments.php';
}
