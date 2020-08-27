# vitepay-wechat

vitepay的微信支付网关。


## 安装

    composer require vitepay/core vitepay/wechat
    


## 配置

修改配置`config/vitepay_wechat.php`

	return [
	  'sandbox'     => true,//沙箱模式
	  'type'        => '',
	  'credentials' => [
		'key'     => '',
		'app_id'  => '',
		'mch_id'  => '',
		'cert'    => '',//证书
		'ssl_key' => '',//证书秘钥
	  ],
	  "gateways"    => [
		"js"   => [
		  'type' => 'js',
		],
		"app"  => [
		  'type'    => 'app',
		  'sandbox' => true,
		  'credentials' => [
			'app_id' => '',//需要填入APP的app_id
		  ],
		],
		"wap"  => [
		  'type'    => 'wap',
		  'sandbox' => true,
		],
		"scan" => [
		  'sandbox' => true,
		  'type'    => 'scan',
		],
		"mp"   => [
		  'type'        => 'js',
		  'sandbox'     => true,
		  'credentials' => [
			'app_id' => '',//需要填入小程序的app_id
		  ],
		],	  
	  ],
	];
	
## 使用

参见 [liuwave/vitepay](https://github.com/liuwave/vitepay)


## 相关支付

- [liuwave/vitepay-alipay](https://github.com/liuwave/vitepay-alipay)



## License
    

The MIT License (MIT). Please see [License File](https://choosealicense.com/licenses/mit) for more information.
    
