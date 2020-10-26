<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\CacheProvider;

/**
 * Class CacheTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class CacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testCache()
    {
        $provider = $this->getProvider();

        $provider->set('cache.test', 'test');
        $value = $provider->get('cache.test');

        $this->assertSame('test', $value);
    }

    protected function getProvider()
    {
        $hub = Client::make([], [CacheProvider::class]);
        return $hub[CacheProvider::NAME];
    }
}
