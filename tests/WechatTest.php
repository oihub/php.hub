<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\WeChatProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class WechatTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class WechatTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testFetch()
    {
        return $this->assertSame('', '');
        $provider = $this->getProvider();

        $response = $provider->fetch(
            '/cgi-bin/material/batchget_material',
            'POST',
            [
                'type' => 'news',
                'offset' => '0',
                'count' => '20',
            ]
        );
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);

        $this->assertArrayHasKey('item', $result);
        $this->assertArrayHasKey('item_count', $result);
        $this->assertArrayHasKey('total_count', $result);
    }

    /**
     * @test
     */
    public function testEvent()
    {
        return $this->assertSame('', '');
        $provider = $this->getProvider();
        $request = $this->getRequest();

        $response = $provider->serve(function ($data) {
            $this->assertArrayHasKey('Content', $data);
            return [
                'ToUserName' => $data['FromUserName'],
                'FromUserName' => $data['ToUserName'],
                'CreateTime' => time(),
                'MsgType' => 'text',
                'Content' => $data['Content'],
            ];
        }, $request);
        $response->send();
    }

    protected function getRequest()
    {
        $uri = 'https://test.com/?encrypt_type=aes&msg_signature=2d8dc08bdc3a202a72e948e592e5aa052cd90670&nonce=1347229193&openid=owcCY0f-ManJjbeZeIRevxAw69_c&signature=e46c26125fe79debd3c69b00663e09a4d2900df1&timestamp=1602837359';
        $content = '<xml>
<ToUserName><![CDATA[gh_3fad04df0180]]></ToUserName>
<Encrypt><![CDATA[z1qGK+6Z6pIADRf8+6Ko4YbC5sssRtkMPiiaqGUJ3B4Mq/JbOOOK5amF+kBmtf1D4DXzRxKzg1nu2UqSPbF4x6cwa2pvWXv/3FLTZb4OgXsgaSUCVUvJbnqSPmNcpjuydjTVpaNMk/ml2vXUKnKx2DvcRw/5hw50bHZuX8sPhOFYjO2xS6vntvN8nWUxCLMNf5GTn238/yy0kg81nIl1lgmQ9+U7JaIqdenBSfAI58pwfwpEoOYCA0TnTcataS3lJYf/8ihVPLAa6+6lNIg7mospciP9Vfl3D09sVL+jTici628Vm7D5eXwaKJc4Fz9RfSnxkSgNO+IQV4GeeaTj4hMU35O1DGDfiZd3KWQLjSLHY55SFjqCwTUlQaoIjc1c0K+gP11eqSlvHh5uQUAQ8kXSGkA5hx4o1xAf+RKLtWE=]]></Encrypt>
</xml>';

        return Request::create($uri, 'POST', [], [], [], [], $content);
    }

    protected function getProvider()
    {
        $hub = Client::make([
            'wechat' => [
                'appid' => 'appid',
                'secret' => 'secret',
                'token' => 'token',
                'aes_key' => 'aes_key',
            ]
        ], [WeChatProvider::class]);
        return $hub[WeChatProvider::NAME];
    }
}
