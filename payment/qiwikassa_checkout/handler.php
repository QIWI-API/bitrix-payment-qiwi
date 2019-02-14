<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Loader;
use Qiwi\Api\BillPayments;
use Qiwi\Api\BillPaymentsException;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem\ServiceHandler;
use Bitrix\Sale\PaySystem\IRefundExtended;
use Bitrix\Sale\PaySystem\Service;
use Bitrix\Sale\PaySystem\ServiceResult;
use Bitrix\Sale\PaySystem\Logger;
use Bitrix\Sale\PaySystem\IHold;
use Bitrix\Sale\Payment;
use Bitrix\Main\Web\Json;
use CEventLog;

if (!Loader::includeModule('qiwikassa.checkout')) {
    die('Module qiwikassa.checkout is not installed');
}
Loc::loadMessages(__FILE__);

/**
 * Handler for QIWI payment gateway.
 *
 * @package Sale\Handlers\PaySystem
 */
class Qiwikassa_CheckoutHandler extends ServiceHandler implements IRefundExtended, IHold
{
    /** @var string The secret key. */
    protected $secretKey;

    /** @var BillPayments QIWI Api class. */
    protected $qiwiApi;

    /** @var bool Set payment status 'PAID' after transaction. */
    protected $setPaid;

    /**
     * Init api.
     *
     * @param $type
     * @param Service $service
     * @throws \ErrorException
     */
    public function __construct($type, Service $service) {
        parent::__construct($type, $service);
        $this->secretKey = $this->getBusinessValue($payment, 'QIWI_KASSA_SECRET_API_KEY');
        $this->qiwiApi = new BillPayments($this->secretKey, [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => dirname(__FILE__, 3) . '/cacert.pem',
        ]);
    }

    /**
     * Initiate pay handler.
     *
     * @param Payment $payment
     * @param Request|null $request
     * @return ServiceResult
     */
    public function initiatePay(Payment $payment, Request $request = null) {
        global $APPLICATION;
        $result = new ServiceResult();
        if (!$payment->isPaid()) {
            /** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
            $paymentCollection = $payment->getCollection();
            if ($paymentCollection) {
                /** @var \Bitrix\Sale\Order $order */
                $order = $paymentCollection->getOrder();
                if ($order) {
                    //If order already has BillId(PS_INVOICE_ID), then find order by this BillID
                    $billId = $payment->getField('PS_INVOICE_ID');
                    if (!$billId) {
                        //otherwise we need to generate new BillID
                        $paymentId = $payment->getId();
                        $billId = $this->qiwiApi->generateId();
                        if ($billId) {
                            $setField = $payment->setField('PS_INVOICE_ID', $billId);
                            if ($setField->isSuccess()) {
                                $payment->save();
                            }
                        }
                    }
                    $phoneProp = $order->getPropertyCollection()->getPhone();
                    if ($phoneProp) {
                        $phone = $phoneProp->getValue();
                    }
                    $emailProp = $order->getPropertyCollection()->getUserEmail();
                    if ($emailProp) {
                        $email = $emailProp->getValue();
                    }
                    $themeCode = $this->getBusinessValue($payment, 'QIWI_KASSA_THEME_CODE');
                    $billParams = [
                        'amount' => $payment->getSum(),
                        'currency' => $payment->getField('CURRENCY'),
                        'comment' => $order->getField('USER_DESCRIPTION'),
                        'expirationDateTime' => $this->qiwiApi->getLifetimeByDay($this->getBusinessValue($payment, 'QIWI_KASSA_BILL_LIFETIME')),
                        'account' => $order->getField('USER_ID'),
                        'customFields' => [],
                    ];
                    if (!!$phone) {
                        $billParams['phone'] = $phone;
                    }
                    if (!!$email) {
                        $billParams['email'] = $email;
                    }
                    if (!!$themeCode) {
                        $billParams['customFields']['themeCode'] = $themeCode;
                    }
                    //creating bill, forming data for payment page
                    try {
                        $billInfo = $this->qiwiApi->createBill($billId, $billParams);
                        if ($billInfo['status']['value'] !== 'PAID') {
                            $successURL = ($_SERVER['HTTPS'] ? 'https://' : 'http://') . SITE_SERVER_NAME . '/bitrix/tools/sale_ps_result.php?qiwi=success&p_id=' . $payment->getId();
                            $successUrl = $successURL . '&back=' . urlencode($APPLICATION->GetCurUri());
                            $payURL = $this->qiwiApi->getPayUrl($billInfo, $successUrl);
                            $params = array(
                                'URL' => $payURL,
                                'SUCCESS_URL' => $successURL,
                                'PAYMENT_SHOULD_PAY' => $payment->getSum(),
                                'BX_PAYSYSTEM_CODE' => $this->service->getField('ID'),
                            );
                            $this->setExtraParams($params);
                        }
                    } catch (BillPaymentsException $exception) {
                        $result->addError(new Error($exception->getMessage()));
                        $error = 'Qiwi: 401: ' . join("\n", $result->getErrorMessages());
                        Logger::addError($error);
                    }
                }
            }
        }
        if ($result->isSuccess()) {
            return $this->showTemplate($payment, 'template');
        } else {
            return $result;
        }
    }

    /**
     * Identifies paysystem by GET parameter.
     *
     * @return array
     */
    public static function getIndicativeFields() {
        return ['qiwi'];
    }

    /**
     * Gets order id by URL GET parameter - 'p_id', or json-encoded POST body.
     *
     * @param Request $request
     * @return mixed
     */
    public function getPaymentIdFromRequest(Request $request) {
        $pid = $request->get('p_id');
        if ($pid) {
            return $pid;
        }
        $body = file_get_contents('php://input');
        if ($body) {
            $reqData = json_decode($body, true);
        }
        //loggin notifies from Qiwi
        CEventLog::Add([
            'SEVERITY' => 'SECURITY',
            'AUDIT_TYPE_ID' => 'PAYMENT_QIWI_NOTIFY',
            'MODULE_ID' => 'qiwikassa.checkout',
            'ITEM_ID' => 1,
            'DESCRIPTION' => print_r($reqData, true),
        ]);
        if ($reqData['bill']) {
            $pid = $this->getPidByQiwiBillId($reqData['bill']['billId']);
            if (!$pid) {
                http_response_code(404);
                die();
            }
            return $pid;
        }
        http_response_code(404);
        die();
    }

    /**
     * Finds order ID by PS_INVOICE_ID.
     *
     * @param int $billId
     * @return string|bool Order ID if exists or false
     */
    public function getPidByQiwiBillId($billId) {
        if ($billId) {
            $payment = Payment::getList([
                        'limit' => 1,
                        'select' => ['*'],
                        'filter' => ['=PS_INVOICE_ID' => $billId],
            ]);
            return $payment->fetch()['ORDER_ID'];
        } else {
            return false;
        }
    }

    /**
     * Proceesses request after payment.
     *
     * @param Payment $payment
     * @param Request $request
     * @return ServiceResult
     */
    public function processRequest(Payment $payment, Request $request) {
        $this->setPaid = $this->getBusinessValue($payment, 'QIWI_KASSA_CHANGE_STATUS_PAY') == 'Y';
        $result = new ServiceResult();
        $action = $request->get('qiwi');
        //loggin notifies from Qiwi
        CEventLog::Add([
            'SEVERITY' => 'SECURITY',
            'AUDIT_TYPE_ID' => 'QIWI_KASSA_NOTIFY_PROCESS_REQUEST',
            'MODULE_ID' => 'qiwikassa.checkout',
            'ITEM_ID' => 1,
            'DESCRIPTION' => print_r(['method' => 'processRequest', 'data' => $reqData], true),
        ]);
        switch ($action) {
            case 'success':
                $result = $this->processSuccessAction($payment, $request);
                break;
            case 'notify':
                $result = $this->processNotifyAction($payment, $request);
                break;
        }
        return $result;
    }

    /**
     * @param Payment $payment
     * @param Request $request
     * @return ServiceResult
     */
    public function processSuccessAction(Payment $payment, Request $request) {
        $result = new ServiceResult();
        $billInfo = $this->checkBill($payment->getField('PS_INVOICE_ID'), $result);
        if ($result->isSuccess()) {
            if ($billInfo) {
                switch ($billInfo['status']['value']) {
                    case 'PAID':
                        if ($this->setPaid) {
                            $result->setOperationType(ServiceResult::MONEY_COMING);
                        }
                        break;
                    case 'WAITING':
                    case 'REJECTED':
                    case 'EXPIRED':
                        $result->setOperationType(ServiceResult::MONEY_LEAVING);
                        break;
                }
                $psData['PS_STATUS_CODE'] = $billInfo['status']['value'];
                if ($request->get('back')) {
                    $data['BACK_URL'] = urldecode($request->get('back'));
                }
            }
        }
        $result->setPsData($psData);
        $result->setData($data);
        return $result;
    }

    /**
     * @param Payment $payment
     * @param Request $request
     * @return ServiceResult
     */
    public function processNotifyAction(Payment $payment, Request $request) {
        $body = file_get_contents('php://input');
        if ($body) {
            $billData = json_decode($body, true);
        }
        $result = new ServiceResult();
        if (!$this->checkNotifySignature($billData)) {
            $result->setData(['NOTIFY' => ['CODE' => 403]]);
            return $result;
        }
        //loggin notifies from Qiwi
        CEventLog::Add([
            'SEVERITY' => 'SECURITY',
            'AUDIT_TYPE_ID' => 'QIWI_KASSA_NOTIFY_STEP_2',
            'MODULE_ID' => 'qiwikassa.checkout',
            'ITEM_ID' => 2,
            'DESCRIPTION' => print_r(['method' => 'processNotifyAction', 'data' => $billData], true),
        ]);
        $billData = $billData['bill'];
        switch ($billData['status']['value']) {
            case 'PAID':
                if ($this->setPaid) {
                    $result->setOperationType(ServiceResult::MONEY_COMING);
                }
                break;
            case 'WAITING':
            case 'REJECTED':
            case 'EXPIRED':
            case 'PARTIAL':
            case 'FULL':
                $result->setOperationType(ServiceResult::MONEY_LEAVING);
                $psData['PAID'] = 'N';
                break;
        }
        $psData['PS_STATUS_CODE'] = $billData['status']['value'];
        $result->setPsData($psData);
        $result->setData(['NOTIFY' => ['CODE' => 200]]);
        //loggin notifies from Qiwi
        CEventLog::Add([
            'SEVERITY' => 'SECURITY',
            'AUDIT_TYPE_ID' => 'QIWI_KASSA_NOTIFY_STEP_3',
            'MODULE_ID' => 'qiwikassa.checkout',
            'ITEM_ID' => 2,
            'DESCRIPTION' => print_r(['method' => 'processNotifyAction', 'data' => $result->isSuccess()], true),
        ]);
        return $result;
    }

    /**
     * Sets header and prints json encoded data, then dies.
     *
     * @param array $data
     * @param int $code
     */
    public function sendJsonResponse($data = [], $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        header('Pragma: no-cache');
        die(Json::encode($data));
    }

    /**
     * Checks header's parameter from request.
     *
     * @param array $billData
     * @return boolean
     */
    public function checkNotifySignature($billData) {
        if (!$billData) {
            return false;
        }
        $signature = array_key_exists('HTTP_X_API_SIGNATURE_SHA256', $_SERVER) ? $_SERVER['HTTP_X_API_SIGNATURE_SHA256'] : '';
        return $this->qiwiApi->checkNotificationSignature($signature, $billData, $this->secretKey);
    }

    /**
     * Checks Bill in Qiwi system, adds error to $result if bill not found.
     *
     * @param int $billId
     * @param ServiceResult $result
     * @return array|false Bill array if correct BillId or false if bill not found
     */
    public function checkBill($billId, &$result) {
        if (!$billId) {
            return false;
        }
        try {
            $billInfo = $this->qiwiApi->getBillInfo($billId);
        } catch (BillPaymentsException $exception) {
            $result->addError(new Error($exception->getMessage()));
            $error = 'Qiwi: checkBillError: ' . join("\n", $result->getErrorMessages());
            Logger::addError($error);
            $billInfo = false;
        }
        return $billInfo;
    }

    /**
     * Needs for paysystems with test modes. Qiwi has not this mode.
     *
     * @param Payment $payment
     * @return bool
     */
    protected function isTestMode(Payment $payment = null) {
        return false;
    }

    /**
     * Bitrix's abstart method, we don't use.
     *
     * @return array
     */
    public function getCurrencyList() {
        return;
    }

    /**
     * Final function that sends response or redirects user to payment page.
     *
     * @param ServiceResult $result
     * @param Request $request
     */
    public function sendResponse(ServiceResult $result, Request $request) {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        $data = $result->getData();
        if ($data['NOTIFY']['CODE']) {
            switch ($data['NOTIFY']['CODE']) {
                case 403:
                    $this->sendJsonResponse(['error' => 403], 403);
                    break;
                default:
                    $this->sendJsonResponse(['error' => 0]);
                    break;
            }
        } elseif ($data['BACK_URL']) {
            LocalRedirect($data['BACK_URL']);
        } else {
            echo 'SUCCESS';
        }
        return;
    }

    /**
     * Cancels qiwi payment.
     *
     * @param Payment $payment
     * @return ServiceResult
     */

    public function cancel(Payment $payment){
        $result = new ServiceResult();
        $billId = $payment->getField('PS_INVOICE_ID');
        //loggin notifies from Qiwi
        CEventLog::Add([
            'SEVERITY' => 'SECURITY',
            'AUDIT_TYPE_ID' => 'QIWI_KASSA_NOTIFY_CANCEL_START',
            'MODULE_ID' => 'qiwikassa.checkout',
            'ITEM_ID' => 3,
            'DESCRIPTION' => print_r(['method' => 'cancel', 'billId' => $billId], true),
        ]);
        if ($billId){
            try{
                $billInfo = $this->qiwiApi->cancelBill($billId);
                $result->setOperationType(ServiceResult::MONEY_LEAVING);
                $psData['PS_STATUS_CODE'] = $billInfo['status']['value'];
                $result->setPsData($psData);
            } catch (BillPaymentsException $exception) {
                $result->addError(new Error($exception->getMessage()));
                $error = 'Qiwi: cancelError: ' . join("\n", $result->getErrorMessages());
                Logger::addError($error);
            }
        }
        //loggin notifies from Qiwi
        CEventLog::Add([
            'SEVERITY' => 'SECURITY',
            'AUDIT_TYPE_ID' => 'QIWI_KASSA_NOTIFY_CANCEL_END',
            'MODULE_ID' => 'qiwikassa.checkout',
            'ITEM_ID' => 3,
            'DESCRIPTION' => print_r(['method' => 'cancel', 'data' => $billInfo], true),
        ]);
        return $result;
    }


    /**
     * Confirm Qiwi payment, we don't use this method;
     *
     * @param Payment $payment
     * @return ServiceResult
     */
    public function confirm(Payment $payment){
        return;
    }

    /**
     * Sends request on qiwi server for refund payment.
     *
     * @param Payment $payment
     * @param int $refundableSum
     * @return ServiceResult
     */
    public function refund(Payment $payment, $refundableSum) {
        $result = new ServiceResult();
        $billId = $payment->getField('PS_INVOICE_ID');
        $refundId = $this->qiwiApi->generateId();
        //$amount = $payment->getSum();
        $amount = $refundableSum;
        $currency = $payment->getField('CURRENCY');
        try {
            $response = $this->qiwiApi->refund($billId, $refundId, $amount, $currency);
            if (!$response['errorCode']) {
                $payment->setField('PS_STATUS_CODE', $response['status']);
                $payment->setField('COMMENTS', 'Qiwi refundId: ' . $refundId);
                $saved = $payment->save();
                $result->setOperationType(ServiceResult::MONEY_LEAVING);
            } else {
                $result->addError(new Error($response['errorCode'] . ' : ' . $response['description']));
                $error = 'Qiwi: refundError: ' . join("\n", $result->getErrorMessages());
                Logger::addError($error);
            }
        } catch (BillPaymentsException $exception) {
            $result->addError(new Error($exception->getMessage()));
            $error = 'Qiwi: refundError: ' . join("\n", $result->getErrorMessages());
            Logger::addError($error);
        }
        //loggin notifies from Qiwi
        CEventLog::Add([
            'SEVERITY' => 'SECURITY',
            'AUDIT_TYPE_ID' => 'QIWI_KASSA_NOTIFY_REFUND',
            'MODULE_ID' => 'qiwikassa.checkout',
            'ITEM_ID' => 2,
            'DESCRIPTION' => print_r(['method' => 'refund', 'refundId' => $refundId, 'refundableSum' => $refundableSum, 'response' => $response, 'saved' => $saved->isSuccess()], true),
        ]);
        return $result;
    }

    /**
     * Refundable.
     *
     * @return bool
     */
    public function isRefundableExtended() {
        return true;
    }
}
