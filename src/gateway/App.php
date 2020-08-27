<?php

namespace vitepay\wechat\gateway;

use think\helper\Str;
use vitepay\wechat\BaseGateway;
use vitepay\core\entity\PurchaseResponse;
use vitepay\core\interfaces\Payable;
use vitepay\wechat\request\UnifiedOrderRequest;

class App extends BaseGateway
{
    
    /**
     * 购买
     *
     * @param Payable $charge
     *
     * @return PurchaseResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function purchase(Payable $charge) : PurchaseResponse
    {
        $request = $this->createRequest(UnifiedOrderRequest::class, $charge, self::TYPE_APP);
        $result  = $this->sendRequest($request);
        
        $data = [
          'appid'     => $this->getOption('app_id'),
          'partnerid' => $this->getOption('mch_id'),
          'prepayid'  => $result[ 'prepay_id' ],
          'package'   => 'Sign=WXPay',
          'noncestr'  => Str::random(),
          'timestamp' => (string)time(),
        ];
        
        $data[ 'sign' ] = $this->generateSign($data);
        
        return new PurchaseResponse($data, PurchaseResponse::TYPE_PARAMS);
    }
}
