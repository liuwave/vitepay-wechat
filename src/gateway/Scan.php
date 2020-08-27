<?php

namespace vitepay\wechat\gateway;

use vitepay\wechat\BaseGateway;
use vitepay\core\entity\PurchaseResponse;
use vitepay\core\interfaces\Payable;
use vitepay\wechat\request\UnifiedOrderRequest;

class Scan extends BaseGateway
{
    
    /**
     * 购买
     *
     * @param Payable $charge
     *
     * @return PurchaseResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function purchase(Payable $charge):PurchaseResponse
    {
        $request = $this->createRequest(UnifiedOrderRequest::class, $charge, self::TYPE_NATIVE);

        $result = $this->sendRequest($request);

        return new PurchaseResponse($result['code_url'], PurchaseResponse::TYPE_SCAN);
    }
}
