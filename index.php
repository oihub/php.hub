<?php

require __DIR__ . '/vendor/autoload.php';

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\WeChatProvider;

$hub = Client::make(
    [
        'wechat' => [
            'appid' => 'appid',
            'secret' => 'secret',
            'token' => 'token',
            'aes_key' => 'aes_key',
        ]
    ],
    [WeChatProvider::class]
);
$wechat = $hub[WeChatProvider::NAME];

$response = $wechat->serve(function ($data) {
    return [
        'ToUserName' => $data['FromUserName'],
        'FromUserName' => $data['ToUserName'],
        'CreateTime' => time(),
        'MsgType' => 'text',
        'Content' => $data['Content'],
    ];
});
$response->send();
