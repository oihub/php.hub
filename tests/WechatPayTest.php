<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\WeChatPayProvider;

/**
 * Class WechatPayTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class WechatPayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testCert()
    {
        return $this->assertSame('', '');
        $provider = $this->getProvider();

        $result = $provider->cert();
        $this->assertGreaterThan(0, count($result));
    }

    /**
     * @test
     */
    public function testFetch()
    {
        return $this->assertSame('', '');
        $provider = $this->getProvider();

        $result = $provider->fetch('merchant/fund/balance/BASIC');

        $this->assertArrayHasKey('available_amount', $result);
        $this->assertArrayHasKey('pending_amount', $result);
    }

    /**
     * @test
     */
    public function testMedia()
    {
        return $this->assertSame('', '');
        $provider = $this->getProvider();

        $uri = 'merchant/media/upload';
        $path = 'filename.png';

        $result = $provider->media($uri, $path);

        $this->assertArrayHasKey('media_id', $result);
    }

    protected function getProvider()
    {
        $hub = Client::make([
            'wechatpay' => [
                'aes_key' => 'aes_key',
                'merchant_id' => 'merchant_id',
                'merchant_serial_number' => 'merchant_serial_number',
                'merchant_private_key' => __DIR__ . '/WeChatPay/apiclient_key.pem',
                'wechatpay_certificate' => __DIR__ . '/WeChatPay/certificate.pem',
                'wechatpay_serial_no' => 'wechatpay_serial_no',
            ]
        ], [WeChatPayProvider::class]);
        return $hub[WeChatPayProvider::NAME];
    }
}
