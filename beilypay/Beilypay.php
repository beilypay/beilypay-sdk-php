<?php
namespace beilypay;

use Exception;
use hightman\http\Client;
use hightman\http\Request;

class Beilypay {

    private $appId;         // 商户 APPID
    private $merchantId;    // 商户号
    private $appSecret;     // 商户密钥
    private $apiHost;       // 对接 api 的地址，以 http 或 https 开头

    /**
     * 构造函数
     * @param string $appId 商户 APPID
     * @param string $merchantId 商户号
     * @param string $appSecret 商户密钥
     * @param string $apiHost 对接 api 的地址，以 http 或 https 开头
     **/
    function __construct($appId, $merchantId, $appSecret, $apiHost) {
        $this->appId = $appId;
        $this->merchantId = $merchantId;
        $this->appSecret = $appSecret;
        $this->apiHost = rtrim($apiHost, "/") . "/v2";   // 去掉最后的 "/" 符号
    }

    /**
     * 创建代收订单
     * @return Order
     */
    function createPayment(
        $orderNumber,       // 商户订单号
        $amount,            // 订单金额
        $notifyUrl,         // 订单回调地址（支付成功才有回调）
        $frontCallbackUrl,  // 前端页面回跳地址
        $userId,            // 商户的用户ID
        $userName,          // 商户的用户昵称
        $mobile = null,     // 商户的用户手机号，留空 则 默认随机10位数字
        $email = null       // 商户的用户Email地址，留空 则 {$mobile}@gmail.com
    ) 
    {
        if ($mobile == null) {
           $mobile = rand(1000000000, 9999999999);
        }
        if ($email == null ) {
            $email = "$mobile@gmail.com";
        }
        
        $http = new Client();
        $request = array(
            "appId"         => $this->appId,
            "payAmount"     => $amount, 
            "email"         => $email,
            "frontCallback" => $frontCallbackUrl,
            "merchantId"    => $this->merchantId,
            "mobile"        => $mobile,
            "notifyUrl"     => $notifyUrl,
            "outOrderNo"    => $orderNumber,
            "userId"        => $userId,
            "userName"      => $userName
        );
        $request["sign"] = $this->sign($request);

        $res = $http->postJson("{$this->apiHost}/payment/create", $request);
        if (isset($res['code']) && isset($res['data']) && $res["code"] == 200) {
            $data = $res['data'];
            $payment = new Order();
            $payment->appId             = $this->appId;
            $payment->merchantOrderNo   = $data["outOrderNo"];
            $payment->orderNo           = $data["orderNo"];
            $payment->amount            = $data["payAmount"];
            $payment->payUrl            = $data["payUrl"];
            $payment->paid              = 0;
            return $payment;
        }
        if (isset($res['code'])) {
            throw new Exception("create payment error(code={$res['code']}) {$res['msg']}");
        }
        throw new Exception("create payment error(code=unknow)");
    }

    /**
     * 创建代付订单
     * 
     * @return Order
     */
    function createTrans(
            $orderNumber,   // 商户的订单号
            $amount,        // 代付金额
            $notifyUrl,     // 代付结果回调地址
            $accountType,   // 收款账户类型 Card: 代付到银行卡
            $account,       // 对应的收款账户
            $accountOwner,  // 账户持有者姓名
            $bankCode,      // 账户类型为Card,对应的银行编码
            $ifsc,          // 账户类型为Card,分行的IFSC代码	
            $address,       // 收款人地址
            $mobile = null, // 收款人手机号 留空 则 默认随机10位数字
            $email = null   // 收款人邮箱 留空 则 {$mobile}@gmail.com
    ) 
    {

        if ($mobile == null) {
           $mobile = rand(1000000000, 9999999999);
        }
        if ($email == null ) {
            $email = "$mobile@gmail.com";
        }

        
        $http = new Client();
        $request = array(
            "appId"         => $this->appId,
            "merchantId"    => $this->merchantId,
            "notifyUrl"     => $notifyUrl,
            "outOrderNo"    => $orderNumber,
            "payAmount"     => $amount,
            "accountType"   => $accountType,
            "account"       => $account,
            "accountOwner"  => $accountOwner,
            "bankCode"      => $bankCode,
            "ifsc"          => $ifsc,
            "address"       => $address,
            "mobile"        => $mobile,
            "email"         => $email,
        );
        $request["sign"] = $this->sign($request);

        $res = $http->postJson("{$this->apiHost}/trans/create", $request);
        if (isset($res['code']) && isset($res['data']) && $res["code"] == 200) {
            $data = $res['data'];
            $trans = new Order();
            $trans->appId             = $this->appId;
            $trans->merchantOrderNo   = $data["outOrderNo"];
            $trans->orderNo           = $data["orderNo"];
            $trans->amount            = $data["payAmount"];
            $trans->paid              = 0;

            return $trans;
        }
        if (isset($res['code'])) {
            throw new Exception("create trans error(code={$res['code']}) {$res['msg']}");
        }
        throw new Exception("create trans error(code=unknow)");
    }

    /**
     * 查询代收订单
     * @param string @orderNo 平台订单号
     * @return Order
     */
    function queryPayment($orderNo) {

        $http = new Client();
        $request = array(
            "appId"         => $this->appId,
            "merchantId"    => $this->merchantId,
            "orderNo"       => $orderNo
        );
        $request["sign"] = $this->sign($request);

        $res = $http->postJson("{$this->apiHost}/payment/query", $request);
        if (isset($res['code']) && isset($res['data']) && $res["code"] == 200) {
            $data = $res['data'];
            $payment = new Order();
            $payment->appId             = $this->appId;
            $payment->merchantOrderNo   = $data["outOrderNo"];
            $payment->orderNo           = $data["orderNo"];
            $payment->amount            = $data["payAmount"];
            $payment->status            = $data["status"];
            $payment->paid              = $data['paid'];

            return $payment;
        }
        if (isset($res['code'])) {
            throw new Exception("query payment error(code={$res['code']}) {$res['msg']}");
        }
        throw new Exception("query payment error(code=unknow)");
    }

    /**
     * 查询代付订单
     * @param string @orderNo 平台订单号
     * @return Order
     */
    function queryTrans($orderNo) {

        $http = new Client();
        $request = array(
            "appId"         => $this->appId,
            "merchantId"    => $this->merchantId,
            "orderNo"       => $orderNo
        );
        $request["sign"] = $this->sign($request);

        $res = $http->postJson("{$this->apiHost}/trans/query", $request);
        if (isset($res['code']) && isset($res['data']) && $res["code"] == 200) {
            $data = $res['data'];
            $trans = new Order();
            $trans->appId             = $this->appId;
            $trans->merchantOrderNo   = $data["outOrderNo"];
            $trans->orderNo           = $data["orderNo"];
            $trans->amount            = $data["payAmount"];
            $trans->status            = $data["status"];
            $trans->paid              = isset($data['paid']) ? $data['paid'] : $trans->amount;

            return $trans;
        }
        if (isset($res['code'])) {
            throw new Exception("query trans error(code={$res['code']}) {$res['msg']}");
        }
        throw new Exception("query trans error(code=unknow)");
    }

    /**
     * 计算获得 sign 签名，依赖 $this->appSecret
     */
    function sign($array) {
        ksort($array, SORT_STRING);

        $str = "";
        foreach ($array as $k=>$v) {
            if ($v == null || $v == "") {
                continue;
            }

            $str .= "{$k}={$v}&";
        }
        return md5("{$str}key={$this->appSecret}");
    }

    /**
     * 对请求参数进行校验（适用于回调场景）
     */
    function verfy($array, $signKey = "sign") {
        $sign = $array[$signKey];
        unset($array[$signKey]);
        $newSign = $this->sign($array);
        return $sign == $newSign;
    }
}