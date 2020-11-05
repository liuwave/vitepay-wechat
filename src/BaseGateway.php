<?php

namespace vitepay\wechat;

use Carbon\Carbon;
use DomainException;
use http\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use think\Cache;
use think\facade\Log;
use think\Request;
use vitepay\core\entity\PurchaseResponse;
use vitepay\core\Gateway;
use vitepay\core\entity\PurchaseResult;
use vitepay\core\interfaces\Payable;
use vitepay\core\interfaces\Refundable;
use vitepay\wechat\request\GetSignKeyRequest;
use vitepay\wechat\request\OrderQueryRequest;
use vitepay\wechat\request\RefundQueryRequest;
use vitepay\wechat\request\RefundRequest;

use function vitepay\wechat\array2xml;
use function vitepay\wechat\convert_key;
use function vitepay\wechat\xml2array;

/**
 * Class Wechat
 * @package vitepay\wechat\channel
 */
class BaseGateway extends Gateway
{
    /**
     *
     */
    const TYPE_NATIVE = 'NATIVE';
    /**
     *
     */
    const TYPE_JSAPI = 'JSAPI';
    /**
     *
     */
    const TYPE_APP = 'APP';
    /**
     *
     */
    const TYPE_MWEB = 'MWEB';
    
    /** @var Cache */
    protected $cache;
    
    /**
     * Wechat constructor.
     *
     * @param \think\Cache $cache
     * @param array        $options
     */
    public function __construct(Cache $cache, $options = [])
    {
        parent::__construct($options);
        $this->cache = $cache;
    }
    
    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @return mixed|void
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['app_id', 'mch_id', 'key']);
        
        $resolver->setDefined(['cert', 'ssl_key']);
        
        $resolver->setNormalizer(
          'cert',
          function (Options $options, $value) {
              if (!empty($value) && !is_file($value)) {
                  $fn = runtime_path().'think-pay-wechat-cert-'.md5($value);
                  if (!file_exists($fn)) {
                      file_put_contents($fn, convert_key($value, 'CERTIFICATE'));
                  }
                  
                  return $fn;
              }
              
              return $value;
          }
        );
        
        $resolver->setNormalizer(
          'ssl_key',
          function (Options $options, $value) {
              if (!empty($value) && !is_file($value)) {
                  $fn = runtime_path().'think-pay-wechat-ssl-key-'.md5($value);
                  if (!file_exists($fn)) {
                      file_put_contents($fn, convert_key($value, 'PRIVATE KEY'));
                  }
                  
                  return $fn;
              }
              
              return $value;
          }
        );
    }
    
    /**
     * @return array
     */
    protected function getHttpClientConfig()
    {
        $config = parent::getHttpClientConfig();
        
        if ($this->getOption('cert') && $this->getOption('ssl_key')) {
            $config = array_merge(
              $config,
              [
                'cert'    => $this->getOption('cert'),
                'ssl_key' => $this->getOption('ssl_key'),
              ]
            );
        }
        
        return $config;
    }
    
    /**
     * @return mixed
     * @throws \throwable
     */
    protected function getSignKey()
    {
        return $this->cache->remember(
          'wechat_sandbox_key_new',
          function () {
              $request = $this->createRequest(GetSignKeyRequest::class, $this->getOption('mch_id'));
              
              $result = $this->sendRequest($request);
              
              return $result[ 'sandbox_signkey' ];
          },
          86400
        );
    }
    
    /**
     * @param array $params
     *
     * @return string
     */
    public function generateSign(array $params) : string
    {
        if ($this->isLog()) {
            Log::info('wechatSignLog'.json_encode(['params' => $params],JSON_UNESCAPED_UNICODE || JSON_UNESCAPED_SLASHES));
        }
        if (isset($params[ 'sign' ])) {
            unset($params[ 'sign' ]);
        }
        
        ksort($params);
        $query = urldecode(http_build_query($params));
        $query .= "&key={$this->getOption('key')}";
        $md5   = strtoupper(md5($query));
        if ($this->isLog()) {
            Log::info('wechatSign::'.json_encode(['query' => $query, $md5],JSON_UNESCAPED_UNICODE || JSON_UNESCAPED_SLASHES));
        }
        
        return $md5;
    }
    
    /**
     * @param \vitepay\core\interfaces\Payable $charge
     *
     * @return \vitepay\core\entity\PurchaseResult
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function query(Payable $charge)
    {
        $request = $this->createRequest(OrderQueryRequest::class, $charge);
        
        $data = $this->sendRequest($request);
        
        if ($data[ 'result_code' ] == 'SUCCESS' && $data[ 'trade_state' ] == 'SUCCESS') {
            $result = new PurchaseResult(
              $this->getName(), $data[ 'transaction_id' ], $data[ 'total_fee' ], true, Carbon::parse($data[ 'time_end' ]), $data
            );
        }
        else {
            $result = new PurchaseResult($this->getName(), null, null, false, null, $data);
        }
        
        return $result;
    }
    
    /**
     * 退款
     *
     * @param Refundable $refund
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function refund(Refundable $refund) : array
    {
        $request = $this->createRequest(RefundRequest::class, $refund);
        
        return $this->sendRequest($request);
    }
    
    /**
     * @param \vitepay\core\interfaces\Refundable $refund
     *
     * @return array|mixed
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function refundQuery(Refundable $refund) : array
    {
        $request = $this->createRequest(RefundQueryRequest::class, $refund);
        
        return $this->sendRequest($request);
    }
    
    /**
     * @param \think\Request $request
     *
     * @return mixed|\think\Response
     */
    public function completePurchase(Request $request)
    {
        libxml_disable_entity_loader(true);
        $data = xml2array($request->getContent());
        $this->verifySign($this->generateSign($data), $data[ 'sign' ]);
        $charge = $this->retrieveCharge($data[ 'out_trade_no' ]);
        if (!$charge->isComplete()) {
            $charge->onComplete(
              new PurchaseResult(
                $this->getName(),
                $data[ 'transaction_id' ],
                $data[ 'total_fee' ],
                $data[ 'result_code' ] == 'SUCCESS',
                Carbon::parse($data[ 'time_end' ]),
                $data
              )
            );
        }
        $return = [
          'return_code' => 'SUCCESS',
          'return_msg'  => 'OK',
        ];
        
        return response(array2xml($return));
    }
    
    /**
     * @param $data
     * @param $sign
     *
     * @return mixed|void
     */
    public function verifySign($data, $sign)
    {
        if ($sign != $data) {
            throw new DomainException('签名验证失败');
        }
    }
    
    /**
     * @param \Psr\Http\Message\RequestInterface  $request
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array|mixed
     */
    protected function handleResponse(RequestInterface $request, ResponseInterface $response) : array
    {
        $result = xml2array(
          $response->getBody()
            ->getContents()
        );
        
        if ($result[ 'return_code' ] != 'SUCCESS') {
            throw new DomainException($result[ 'return_msg' ] ?? ($result[ 'retmsg' ] ?? '支付出错'));
        }
        
        if (isset($result[ 'sign' ])) {
            $this->verifySign($this->generateSign($result), $result[ 'sign' ]);
        }
        
        if (isset($result[ 'result_code' ]) && $result[ 'result_code' ] != 'SUCCESS') {
            throw new DomainException($result[ 'err_code_des' ]);
        }
        
        return $result;
    }
    
    /**
     * @param bool $sandbox
     *
     * @return \vitepay\core\Gateway
     * @throws \throwable
     */
    public function setSandbox(bool $sandbox = true)
    {
        $this->options[ 'key' ] = $this->getSignKey();
        
        return parent::setSandbox($sandbox);
    }
    
    /**
     * @inheritDoc
     */
    public function purchase(\vitepay\core\interfaces\Payable $charge) : PurchaseResponse
    {
        throw new InvalidArgumentException('Channel [wechat] has no gateway');
    }
    
}
