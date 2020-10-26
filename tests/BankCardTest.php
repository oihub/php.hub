<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\BankCardProvider;

/**
 * Class BankCardTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class BankCardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testCache()
    {
        $provider = $this->getProvider();

        $result = $provider->getCardInfo('6228480322879495610');

        $this->assertArrayHasKey('validated', $result);
    }

    protected function getProvider()
    {
        $hub = Client::make([], [BankCardProvider::class]);
        return $hub[BankCardProvider::NAME];
    }
}
