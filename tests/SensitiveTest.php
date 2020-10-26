<?php

use PhpHos\Hub\Client;
use PhpHos\Hub\Providers\SensitiveProvider;

/**
 * Class SensitiveTest.
 *
 * @author sean <maoxfjob@163.com>
 */
class SensitiveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testSave()
    {
        $provider = $this->getProvider();

        $input = __DIR__ . '/Sensitive/badword.txt';
        $output = __DIR__ . '/Sensitive/badword.bin';

        $data = $provider->readfile($input);
        $provider->filltrie($data);
        $provider->saveLexicon($output);

        $this->assertFileExists($output);
    }

    /**
     * @test
     */
    public function testSearch()
    {
        $provider = $this->getProvider();

        $lexicon = __DIR__ . '/Sensitive/badword.bin';
        $provider->readLexicon($lexicon);
        $result = $provider->search('我要包二奶');

        $this->assertEquals($result, [
            '包二奶' => [
                'count' => 1,
                'value' => 1,
            ]
        ]);
    }

    /**
     * @test
     */
    public function testReplace()
    {
        $provider = $this->getProvider();

        $lexicon = __DIR__ . '/Sensitive/badword.bin';
        $provider->readLexicon($lexicon);
        $replaced = $provider->replace('我要包二奶', '**');

        $this->assertEquals($replaced, '我要**');

        $replaced = $provider->replace(
            '我要包二奶',
            function ($word, $value) {
                return "[$word -> $value]";
            }
        );

        $this->assertEquals($replaced, '我要[包二奶 -> 1]');
    }

    protected function getProvider()
    {
        $hub = Client::make([], [SensitiveProvider::class]);
        return $hub[SensitiveProvider::NAME];
    }
}
