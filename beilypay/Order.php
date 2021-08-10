<?php
namespace beilypay;

class Order {
    /**
     * 商户 APPID
     */
    public $appId;
    /**
     * 商户的订单号
     */
    public $merchantOrderNo;
    /**
     * 支付平台的订单号
     */
    public $orderNo;
    /**
     * 订单金额
     */
    public $amount;
    /**
     * 实 收|付 金额
     */
    public $paid;
    /**
     * 订单状态 0 - 未完成， 1 - 已完成
     */
    public $status = 0;

    /**
     * 代收订单时为支付链接
     */
    public $payUrl;
}