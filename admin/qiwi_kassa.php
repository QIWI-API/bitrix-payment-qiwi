<?php

require_once dirname(__DIR__, 4) . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once dirname(__DIR__, 4) . '/bitrix/modules/qiwikassa.checkout/include.php';

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("subscribe");
if ($APPLICATION->GetGroupRight('store') <= 'D') {
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

if ($REQUEST_METHOD == 'POST' && check_bitrix_sessid()) {
    $result = false;
    $payment = \Bitrix\Sale\Internals\PaySystemActionTable::getRow([
        'select' => ['id'],
        'filter' => [
            '=ACTION_FILE' => ['qiwikassa_checkout'],
        ],
    ]);
    if (empty($_REQUEST['id'])) {

        $listQuery = \Bitrix\Sale\Internals\OrderTable::getList([
            'select' => ['id'],
            'filter' => [
                '=STATUS_ID' => 'N',
                '=REQUIRED_PS_PRESENTED' => 1
            ],
            'runtime' => [
                'REQUIRED_PS_PRESENTED' => [
                    'data_type' => 'boolean',
                    'expression' => [
                        'CASE WHEN EXISTS (SELECT ID FROM b_sale_order_payment WHERE ORDER_ID = %s AND (PAY_SYSTEM_ID = '.$payment['ID'].')) THEN 1 ELSE 0 END',
                        'ID'
                    ]
                ]
            ]
        ]);
        $result = [];
        while ($listRow = $listQuery->fetch())
        {
            $result[] = $listRow["ID"];
        }
    } else {
        $registry = \Bitrix\Sale\Registry::getInstance(\Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER);
        /** @var \Bitrix\Sale\Order $orderClass */
        $orderClass = $registry->getOrderClassName();
        /** @var \Bitrix\Sale\Order $order */
        $order = $orderClass::load($_REQUEST['id']);
        foreach ($order->getPaymentCollection() as $payment)
        {
            $paySystem = $payment->getPaySystem();
            $setResult = $paySystem->check($payment);
            $result = $setResult->isSuccess();
        }
    }

    $APPLICATION->RestartBuffer();

    header('Content-Type: application/json');
    echo json_encode(['result' => $result]);
    \CMain::FinalActions();
    die();
}

$APPLICATION->SetTitle(GetMessage("QIWI_KASSA_SYNC_TITLE"));

require dirname(__DIR__, 4) . '/bitrix/modules/main/include/prolog_admin_after.php';
?>

<p>
    <button id="qiwi-sync-button" type="button">
        <?php echo GetMessage("QIWI_KASSA_SYNC_BUTTON") ?>
    </button>
    <progress id="qiwi-sync-progress" style="display: none; width: 100%;"></progress>
</p>
<output id="qiwi-sync-output"></output>
<script>
    (() => {
        let button = document.getElementById('qiwi-sync-button');
        let progress = document.getElementById('qiwi-sync-progress');
        let output = document.getElementById('qiwi-sync-output');

        let format = (string, ...args) => string.replace(
            /{(\d+)}/g,
            (match, number) => typeof args[number] !== 'undefined' ? args[number] : match
        );

        let printLine = (message, ...args) => {
            let p = document.createElement('p');
            p.textContent = format(message, ...args);
            output.prepend(p);
        };

        let print = (message, ...args) => {
            output.firstChild.textContent +=  format(message, ...args);
        };

        let post = (id) => {
            let formData = new FormData();
            if (typeof id !== 'undefined') {
                formData.append('id', id);
            }
            formData.append('sessid', '<?php echo bitrix_sessid() ?>');
            return fetch(location.href, {method: 'POST', body: formData})
                .then(response => response.json())
                .catch((error) => printLine(error.message));
        };

        let process = (index, list) => {
            if (index === list.length) {
                button.style.display = 'block';
                progress.style.display = 'none'
                printLine('<?php echo GetMessage("QIWI_KASSA_SYNC_END") ?>');
                return;
            }
            progress.setAttribute('value', Math.floor(index / list.length * 100).toString());
            let nextIndex = index + 1;
            printLine('<?php echo GetMessage("QIWI_KASSA_SYNC_PROGRESS") ?>', nextIndex, list.length, list[index]);
            post(list[index]).then((json) => {
                if (json.result) {
                    print('<?php echo GetMessage("QIWI_KASSA_SYNC_SUCCESS") ?>');
                } else {
                    print('<?php echo GetMessage("QIWI_KASSA_SYNC_FAIL") ?>');
                }
                process(nextIndex, list);
            });
        };

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('qiwi-sync-button').addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                output.textContent = '';
                progress.removeAttribute('value');
                button.style.display = 'none';
                progress.style.display = 'block'
                printLine('<?php echo GetMessage("QIWI_KASSA_SYNC_REQUEST") ?>');
                post().then((json) => {
                    printLine('<?php echo GetMessage("QIWI_KASSA_SYNC_LIST") ?>', json.result.length);
                    process(0, json.result);
                });
            });
        });
    })();
</script>

<?php
require dirname(__DIR__, 4) . '/bitrix/modules/main/include/epilog_admin.php';
