<?php
/**
 * Created by PhpStorm.
 * User: liuwave
 * Date: 2020/8/26 19:11
 * Description:
 */

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