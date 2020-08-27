<?php

namespace vitepay\wechat\request;

use function vitepay\wechat\array2xml;

abstract class Request extends \vitepay\core\Request
{
    protected $endpoint = 'https://api.mch.weixin.qq.com';

    protected $uri;

    public function getMethod()
    {
        return 'POST';
    }

    public function getUri()
    {
        if ($this->gateway->isSandbox()) {
            return $this->endpoint . '/sandboxnew/' . $this->uri;
        }
        return $this->endpoint . '/' . $this->uri;
    }

    public function getHeaders()
    {
        return [];
    }

    public function getBody()
    {
        $params         = $this->params;
        $params['sign'] = $this->gateway->generateSign($params);

        return array2xml($params);
    }
}
