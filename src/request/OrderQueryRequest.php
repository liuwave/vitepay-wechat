<?php

namespace vitepay\wechat\request;

use think\helper\Str;
use vitepay\core\interfaces\Payable;

class OrderQueryRequest extends Request
{
    protected $uri = 'pay/orderquery';

    public function __invoke(Payable $payable)
    {
        $this->params = [
            'appid'        => $this->gateway->getOption('app_id'),
            'mch_id'       => $this->gateway->getOption('mch_id'),
            'nonce_str'    => Str::random(),
            'sign_type'    => 'MD5',
            'out_trade_no' => $payable->getTradeNo(),
        ];
    }
}
