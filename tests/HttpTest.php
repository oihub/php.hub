<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\HttpProvider;

/**
 * Class HttpTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class HttpTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testHttp()
    {
        $method = 'GET';
        $uri = 'https://www.baidu.com';

        $provider = $this->getProvider();

        $response = $provider->request($method, $uri);
        $response->getBody()->rewind();
        $status = $response->getStatusCode();

        $this->assertSame(200, $status);
    }

    protected function getProvider()
    {
        $hub = Client::make([], [HttpProvider::class]);
        return $hub[HttpProvider::NAME];
    }
}
