<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;
require_once __DIR__ . '/ecpay/ecpay.php';

function ecpay_cvs_MetaData() {
    return array(
        'DisplayName' => 'ECPay - 超商代碼',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => false,
        'TokenisedStorage' => false,
    );
}

function ecpay_cvs_config() {
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => '綠界 - 超商代碼',
        ),
        'MerchantID' => array(
            'FriendlyName' => '會員編號',
            'Type' => 'text',
            'Size' => '7',
            'Default' => '',
            'Description' => 'ECPay會員編號。',
        ),
        'HashKey' => array(
            'FriendlyName' => 'HashKey',
            'Type' => 'password',
            'Size' => '16',
            'Default' => '',
            'Description' => '於廠商管理後台->系統開發管理->系統介接設定中取得',
        ),
        'HashIV' => array(
            'FriendlyName' => 'HashIV',
            'Type' => 'password',
            'Size' => '16',
            'Default' => '',
            'Description' => '於廠商管理後台->系統開發管理->系統介接設定中取得',
        ),
        'StoreExpireDate' => array(
            'FriendlyName' => '繳費截止時間',
            'Type' => 'text',
            'Size' => '3',
            'Default' => '7',
            'Description' => '以分鐘為單位',
        ),
        'InvoicePrefix' => array(
            'FriendlyName' => '帳單前綴',
            'Type' => 'text',
            'Default' => '',
            'Description' => '選填（只能為數字、英文，且與帳單 ID 合併總字數不能超過 20）',
            'Size' => '5',
        ),
        'testMode' => array(
            'FriendlyName' => '測試模式',
            'Type' => 'yesno',
            'Description' => '測試模式',
        ),
    );
}

function ecpay_cvs_link($params) {

    # check if in log
    $log = Capsule::table('tblactivitylog')
            ->where('description', 'like', 'ecpay_cvs:{"'.$params['invoiceid'].'":%')
            ->orderBy('id', 'desc')
            ->first();
    if ($log) {
        $log = json_decode(substr($log->description, 10), true);
        $log = $log[$params['invoiceid']];
        $PaymentNo = $log['PaymentNo'];
        $ExpireDateStr = $log['ExpireDate'];
        $ExpireDate = strtotime($ExpireDateStr);
        if ($ExpireDate >= date()) {
            return '<div class="text-left alert alert-info"><p><b>繳費代碼：</b><code>'.$PaymentNo.'</code></p>'.
            '<p><b>代碼繳費期限：</b><code>'.$ExpireDateStr.'</code></p></div>';
        }
    }

    # Invoice Variables
    $TimeStamp = time();
    $TradeNo = $params['InvoicePrefix'].$TimeStamp.$params['invoiceid'];
    $amount = $params['amount']; # Format: ##.##
    $TotalAmount = round($amount); # Format: ##
    if ( $TotalAmount < 27 || $TotalAmount > 20000 ) return '此金額無法使用此付款方式。';

    # System Variables
    $systemurl = $params['systemurl'];

    # 交易設定
    $StoreExpireDate = $params['StoreExpireDate'];
    if (!$params['StoreExpireDate']) {
        $StoreExpireDate = 10800; //預設7天
    }

    $transaction = new ECPay_Pay('CVS');

    # 是否為測試模式
    if ($params['testMode'] == 'on') {
        $transaction->setTestMode();
    } else {
        $transaction->MerchantID = $params['MerchantID'];
        $transaction->HashKey = $params['HashKey'];
        $transaction->HashIV  = $params['HashIV'];
    }

    $transaction->MerchantTradeNo = $TradeNo;
    $transaction->TotalAmount = $TotalAmount;
    $transaction->TradeDesc = $params['description'];
    $transaction->ItemName = $params['description'];
    $transaction->ReturnURL = $systemurl.'/modules/gateways/callback/ecpay_cvs.php';
    $transaction->PaymentInfoURL = $systemurl.'/modules/gateways/callback/ecpay_cvs_info.php';
    $transaction->ClientBackURL = $params['returnurl'];
    $transaction->StoreExpireDate = $StoreExpireDate;
    $transaction->Desc_1 = $params['description'];

    return $transaction->GetHTML($params['langpaynow']);
}
