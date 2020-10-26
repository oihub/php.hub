<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\RequestProvider;

/**
 * Class RequestTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class RequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testRequest()
    {
        $provider = $this->getProvider();

        $scheme = $provider->getScheme();

        $this->assertSame('http', $scheme);
    }

    protected function getProvider()
    {
        $hub = Client::make([], [RequestProvider::class]);
        return $hub[RequestProvider::NAME];
    }
}
