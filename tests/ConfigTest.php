<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\ConfigProvider;

/**
 * Class ConfigTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testConfig()
    {
        $provider = $this->getProvider();

        $value = $provider->get('key2.key2-1');

        $this->assertSame('value2-1', $value);
    }

    protected function getProvider()
    {
        $hub = Client::make([
            'key1' => 'value1',
            'key2' => [
                'key2-1' => 'value2-1',
                'key2-2' => 'value2-2',
            ],
        ], [ConfigProvider::class]);
        return $hub[ConfigProvider::NAME];
    }
}
