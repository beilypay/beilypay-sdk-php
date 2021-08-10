<?php
require_once __DIR__ . "/beilypay/beilypay.inc.php";

use beilypay\Beilypay;

// 创建一个 Beilypay 对象
// 以下对接参数通过商务对接获得
$appId          = "1183601355";
$merchantId     = 3;
$appSecure      = "8c62bd95169ba8818c676a6f2025b8e5";
$apiHost        = "http://dev.beilypay.com";        // 注意 测试环境 和 生产环境 地址不同

// 创建实例
$b = new Beilypay($appId, $merchantId, $appSecure, $apiHost);

//
// 创建代收订单
//
try {    
    $orderNum       = md5(time() . rand(0, 999999));// 商户的订单号
    $amount         = 111;                          // 代付金额
    $userId         = "123123131";                  // 用户Id
    $userName       = "helloName";                  // 用户昵称
    $notifyUrl      = "http://www.baidu.com/";      // 代付结果回调地址，后端异步
    $frontUrl       = "http://www.baidu.com/";      // 前端回跳地址
    $order = $b->createPayment($orderNum, 111, "http://www.baidu.com/", "http://www.baidu.com/", $userId, $userName);
    echo "OK createPayment {$order->orderNo}, {$order->payUrl}\n";
    var_dump($order);
} catch(Exception $e) { 
    echo(sprintf("ERROR createPayment %s\n", $e->getMessage()));
}

//
// 查询代收订单
//
try {
    $orderNo = $order->orderNo;
    $order = $b->queryPayment($orderNo);
    echo "OK queryPayment {$order->orderNo} STATUS={$order->status}\n";
    var_dump($order);
}catch(Exception $e) { 
    echo(sprintf("ERROR queryPayment %s\n", $e->getMessage()));
}


//
// 创建代付订单
//
try {
    $orderNum       = md5(time() . rand(0, 999999));// 商户的订单号
    $amount         = 111;                          // 代付金额
    $notifyUrl      = "http://www.baidu.com/";      // 代付结果回调地址
    $accountType    = "Card";                       // 收款账户类型 Card: 代付到银行卡
    $account        = "account";                    // 对应的收款账户
    $accountOwner   = "owner";                      // 账户持有者姓名
    $bankCode       = "bankcode";                   // 账户类型为Card,对应的银行编码
    $ifsc           = "ifsc";                       // 账户类型为Card,分行的IFSC代码	
    $address        = "address";                    // 收款人地址

    $order = $b->createTrans($orderNum, $amount, $notifyUrl, $accountType, $account, $accountOwner, $bankCode, $ifsc, $address);
    echo "OK createTrans {$order->orderNo}\n";
    var_dump($order);
}catch(Exception $e) { 
    echo(sprintf("ERROR createTrans %s\n", $e->getMessage()));
}

//
// 查询代付订单
//
try {
    $orderNo = $order->orderNo;
    $order = $b->queryTrans($orderNo);
    echo "OK queryTrans {$order->orderNo} STATUS={$order->status}\n";
    var_dump($order);
}catch(Exception $e) {  
    echo(sprintf("ERROR queryTrans %s\n", $e->getMessage()));
}


// 
// 参数签名校验匹配
//
$request = array(
    "abc" => "a123",
    "acd" => 1234,
);
$request["sign"] = $b->sign($request);
echo("sign = {$request['sign']}\n");
$ok = $b->verfy($request);
var_dump($ok);