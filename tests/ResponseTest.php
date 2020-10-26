<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\ResponseProvider;

/**
 * Class ResponseTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class ResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testResponse()
    {
        $provider = $this->getProvider();

        $response = $provider::create('success');
        $content = $response->getContent();

        $this->assertSame('success', $content);
    }

    protected function getProvider()
    {
        $hub = Client::make([], [ResponseProvider::class]);
        return $hub[ResponseProvider::NAME];
    }
}
