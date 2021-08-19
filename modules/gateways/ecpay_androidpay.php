<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/ecpay/ecpay.php';

function ecpay_androidpay_MetaData() {
    return array(
        'DisplayName' => 'ECPay - AndroidPay',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => false,
        'TokenisedStorage' => false,
    );
}

function ecpay_androidpay_config() {
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => '綠界 - AndroidPay',
        ),
        'MerchantID' => array(
            'FriendlyName' => '會員編號',
            'Type' => 'text',
            'Size' => '7',
            'Default' => '',
            'Description' => 'ECPay 會員編號。',
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

function ecpay_androidpay_link($params) {

    # detect if mobile
    if (!isMobile()) return '無法在 PC 上使用'.$params['FriendlyName'].'。';

    # Invoice Variables
    $TimeStamp = time();
    $TradeNo = $params['InvoicePrefix'].$TimeStamp.$params['invoiceid'];
    $amount = $params['amount']; # Format: ##.##
    $TotalAmount = round($amount); # Format: ##

    # System Variables
    $systemurl = $params['systemurl'];

    $transaction = new ECPay_Pay('AndroidPay');

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
    $transaction->ReturnURL = $systemurl.'/modules/gateways/callback/ecpay_androidpay.php';
    $transaction->ClientBackURL = $params['returnurl'];

    return $transaction->GetHTML($params['langpaynow'], 'background-image: url(\''.$systemurl.'/modules/gateways/ecpay/android_pay.png\');background-position: 0.5px 0.5px;background-repeat: no-repeat;background-size: 27px 27px;padding-left: 30px;');
}
