<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/ecpay/ecpay.php';

function ecpay_credit_MetaData() {
    return array(
        'DisplayName' => 'ECPay - 信用卡',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => false,
        'TokenisedStorage' => false,
    );
}

function ecpay_credit_config() {
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => '綠界 - 信用卡',
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

function ecpay_credit_link($params) {

    # Invoice Variables
    $TimeStamp = time();
    $TradeNo = $params['InvoicePrefix'].$TimeStamp.$params['invoiceid'];
    $amount = $params['amount']; # Format: ##.##
    $TotalAmount = round($amount); # Format: ##

    $Language = ($params['clientdetails']['language']=='english') ? 'ENG' : '';

    # System Variables
    $systemurl = $params['systemurl'];

    $transaction = new ECPay_Pay('Credit');

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
    $transaction->ReturnURL = $systemurl.'/modules/gateways/callback/ecpay_credit.php';
    $transaction->ClientBackURL = $params['returnurl'];
    $transaction->Language = $Language;

    return $transaction->GetHTML($params['langpaynow']);
}

function ecpay_credit_refund($params) {
    if ($params['testMode'] == 'on') {
        return array(
            'status' => 'error',
            'rawdata' => 'Cannot refund in test mode.',
        );
    }
    list($MerchantTradeNo, $TradeNo) = explode('-', $params['transid']);
    $credit = new ECPay_Credit();
    $credit->MerchantTradeNo = $MerchantTradeNo;
    $credit->TradeNo = $TradeNo;
    $credit->TotalAmount = $params['amount'];
    $credit->HashKey = $params['HashKey'];
    $credit->HashIV  = $params['HashIV'];
    $CloseResult  = $credit->Close();
    $RefundResult = $credit->Refund();
    return array(
        'status' => ($RefundResult['RtnCode']==='1')?'success':'declined',
        'rawdata' => $RefundResult,
        'transid' => $RefundResult['TradeNo'],
        'fees' => 0,
    );
}
