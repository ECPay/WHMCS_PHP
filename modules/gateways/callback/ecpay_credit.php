<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../ecpay/ecpay.php';

$params = getGatewayVariables('ecpay_credit');
if (!$params['type']) {
    die('Module Not Activated');
}

$transactionStatus = ($_POST['RtnCode'] === '1') ? 'OK' : htmlentities($_POST['RtnMsg']);
$invoiceId = $_POST['MerchantTradeNo'];
$transactionId = $invoiceId.'-'.$_POST['TradeNo'];
$paymentAmount = $_POST['TradeAmt'];
$paymentFee = $_POST['PaymentTypeChargeFee'];

if ($_POST['SimulatePaid'] == '1' && $params['testMode'] != 'on') {
    $transactionStatus = 'Simulate Paid';
}

if (substr($_POST['PaymentType'], 0, 6) !== 'Credit') {
    $transactionStatus = 'Wrong Payment Type';
}

if ($_POST['MerchantID'] !== $params['MerchantID'] && $params['testMode'] != 'on') {
    $transactionStatus = 'Wrong MerchantID';
}

# 檢查碼
if ($params['testMode'] == 'on') {
    $CheckMacValue = CheckMacValue($_POST);
} else {
    $CheckMacValue = CheckMacValue($_POST, $params['HashKey'], $params['HashIV']);
}
if ($CheckMacValue !== $_POST['CheckMacValue']) $transactionStatus = 'Verification Failure';

$invoiceId = substr($invoiceId, strlen($params['InvoicePrefix'])+10);
$invoiceId = checkCbInvoiceID($invoiceId, $params['name']);
checkCbTransID($transactionId);
logTransaction($params['name'], $_POST, $transactionStatus);

if ($transactionStatus == 'OK') {
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        $paymentFee,
        'ecpay_credit'
    );
    die('1|OK');
}

echo '0|'.$transactionStatus;
