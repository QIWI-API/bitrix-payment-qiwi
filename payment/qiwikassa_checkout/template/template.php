<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

/** @var \Bitrix\Sale\Payment $payment */
/** @var array $params */

Asset::getInstance()->addCss('/bitrix/themes/.default/sale.css');
Loc::loadMessages(__FILE__);

$popup = $params['QIWI_KASSA_USE_POPUP'] == 'Y';

if ($popup) {
    Asset::getInstance()->addJs('https://oplata.qiwi.com/popup/v1.js');
}

?>
<div class="sale-qiwi-paysystem-wrapper">
    <span class="tablebodytext">
        <?=Loc::getMessage('SALE_HANDLERS_QIWI_KASSA_DESCRIPTION')?>
        <?=SaleFormatCurrency($params['PAYMENT_SHOULD_PAY'], $payment->getField('CURRENCY'))?>
    </span>
    <div>
        <div class="sale-paysystem-qiwi-button-container">
            <span class="sale-paysystem-qiwi-button">
                <a id="paysystem-qiwi-button" href="<?=$params['URL'];?>" class="sale-paysystem-qiwi-button-item">
                    <?=Loc::getMessage('SALE_HANDLERS_QIWI_KASSA_BUTTON_PAID')?>
                </a>
            </span>
            <span class="sale-paysystem-qiwi-button-description">
                <?=Loc::getMessage('SALE_HANDLERS_QIWI_KASSA_REDIRECT_MESS')?>
            </span>
        </div>
        <p>
            <span class="tablebodytext sale-paysystem-description">
                <?=Loc::getMessage('SALE_HANDLERS_QIWI_KASSA_WARNING_RETURN')?>
            </span>
        </p>
    </div>
</div>
<?php if ($popup) : ?>
<script>
    window.addEventListener('load', function() {
        document.getElementById('paysystem-qiwi-button').addEventListener('click', function(event) {
            event.preventDefault();
            QiwiCheckout.openInvoice({ payUrl: this.getAttribute('href') });
            return false;
        });
    });
</script>
<?php endif; ?>
<style type="text/css">
    .sale-qiwi-paysystem-wrapper {
        position: relative;
        padding: 24px 38px 24px 38px;
        margin: 0 -15px 0 0;
        border: 1px solid #ff9e16;
        font: 14px "Helvetica Neue", Arial, Helvetica, sans-serif;
        color: #424956;
    }

    .sale-paysystem-qiwi-button-item {
        background: #ff9e16;
        padding: 0 22px;
        height: 38px;
        border: 0;
        -webkit-border-radius: 2px;
        -moz-border-radius: 2px;
        border-radius: 2px;
        font: bold 13px/35px "Helvetica Neue", Arial, Helvetica, sans-serif;
        color: #fff;
        -webkit-transition: background .3s ease;
        -moz-transition: background .3s ease;
        transition: background .3s ease;
        display: block;
    }

    .sale-paysystem-qiwi-button {
        display: inline-block;
        margin: 26px 10px 26px 0;
    }

    .sale-paysystem-qiwi-button-description {
        display: inline-block;
        margin: 0 0 15px 0;
        font: 12px "Helvetica Neue", Arial, Helvetica, sans-serif;
        color: #80868e;
    }
</style>
