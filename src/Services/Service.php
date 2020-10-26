<?php

namespace PhpHos\Hub\Services;

use PhpHos\Hub\Client;

/**
 * Class Service.
 *
 * @author sean <maoxfjob@163.com>
 */
abstract class Service
{
    protected $hub;

    protected $config = [];

    public function __construct(Client $hub, array $config = [])
    {
        $this->hub = $hub;
        $this->config = $config;

        $this->init();
    }

    /**
     * 初始化.
     * 
     * @return void
     */
    protected function init(): void
    {
    }
}
