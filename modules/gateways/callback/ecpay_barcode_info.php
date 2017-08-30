<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../ecpay/ecpay.php';

$params = getGatewayVariables('ecpay_barcode');
if (!$params['type']) {
    die('Module Not Activated');
}

$transactionStatus = ($_POST['RtnCode'] === '10100073') ? 'OK' : htmlentities($_POST['RtnMsg']);
$invoiceId = $_POST['MerchantTradeNo'];

if (substr($_POST['PaymentType'], 0, 7) !== 'BARCODE') {
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

if ($transactionStatus == 'OK') {
    $transactionInfo = json_encode( array(
        $invoiceId => array(
            'Barcode1'   => $_POST['Barcode1'],
            'Barcode2'   => $_POST['Barcode2'],
            'Barcode3'   => $_POST['Barcode3'],
            'ExpireDate' => $_POST['ExpireDate']
        )
    ) );
    logActivity('ecpay_barcode:'.$transactionInfo);
    die('1|OK');
}

logActivity(var_export($_POST, true));

echo '0|', $transactionStatus;
