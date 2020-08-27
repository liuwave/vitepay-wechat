<?php

namespace vitepay\wechat\gateway;

use think\helper\Str;
use vitepay\wechat\BaseGateway;
use vitepay\core\entity\PurchaseResponse;
use vitepay\core\interfaces\Payable;
use vitepay\wechat\request\UnifiedOrderRequest;

class Js extends BaseGateway
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
        $request = $this->createRequest(UnifiedOrderRequest::class, $charge, self::TYPE_JSAPI);
        
        $result = $this->sendRequest($request);
        
        $data = [
          'appId'     => $this->getOption('app_id'),
          'timeStamp' => (string)time(),
          'nonceStr'  => Str::random(),
          'package'   => "prepay_id={$result['prepay_id']}",
          'signType'  => 'MD5',
        ];
        
        $data[ 'paySign' ] = $this->generateSign($data);
        
        return new PurchaseResponse($data, PurchaseResponse::TYPE_PARAMS);
    }
}
