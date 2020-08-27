<?php

namespace vitepay\wechat\request;

use think\helper\Str;
use vitepay\core\interfaces\Refundable;

class RefundQueryRequest extends Request
{
    protected $uri = 'pay/refundquery';
    
    public function __invoke(Refundable $refund)
    {
        $this->params = [
          'appid'         => $this->gateway->getOption('app_id'),
          'mch_id'        => $this->gateway->getOption('mch_id'),
          'device_info'   => $refund->getExtra('device_info'),
          'nonce_str'     => Str::random(),
          'sign_type'     => 'MD5',
          'out_refund_no' => $refund->getRefundNo(),
        ];
    }
}
