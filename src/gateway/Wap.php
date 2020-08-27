<?php

namespace vitepay\wechat\gateway;

use vitepay\wechat\BaseGateway;
use vitepay\core\entity\PurchaseResponse;
use vitepay\core\interfaces\Payable;
use vitepay\wechat\request\UnifiedOrderRequest;

/**
 * Class Wap
 * @package vitepay\wechat\gateway
 */
class Wap extends BaseGateway
{
    
    /**
     * @inheritDoc
     *
     */
    public function purchase(Payable $charge) : PurchaseResponse
    {
        $request = $this->createRequest(UnifiedOrderRequest::class, $charge, self::TYPE_MWEB);
        
        $result = $this->sendRequest($request);
        
        return new PurchaseResponse($result[ 'mweb_url' ], PurchaseResponse::TYPE_REDIRECT);
    }
}
