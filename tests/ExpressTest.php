<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\ExpressProvider;

/**
 * Class ExpressTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class ExpressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testKdniao()
    {
        $provider = $this->getProvider();

        $url = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
        $result = $provider->kdniao($url, '8001', [
            'ShipperCode' => 'YZPY',
            'LogisticCode' => '9899402217950',
            'CustomerName' => '',
        ]);

        $this->assertArrayHasKey('Success', $result);
    }

    protected function getProvider()
    {
        $hub = Client::make([
            'express' => [
                'kdniao' => [
                    'ebusiness_id' => 1323838,
                    'app_key' => '1a6c738b-c1ba-4786-b445-431e9e5889af',
                ],
            ],
        ], [ExpressProvider::class]);
        return $hub[ExpressProvider::NAME];
    }
}
