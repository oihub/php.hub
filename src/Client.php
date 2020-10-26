<?php

namespace PhpHos\Hub;

use PhpHos\Hub\Providers\CacheProvider;
use PhpHos\Hub\Providers\ConfigProvider;
use PhpHos\Hub\Providers\HttpProvider;
use PhpHos\Hub\Providers\RequestProvider;
use PhpHos\Hub\Providers\ResponseProvider;

/**
 * Class Client.
 *
 * @author sean <maoxfjob@163.com>
 */
class Client extends \Pimple\Container
{
    /**
     * @var string 名称.
     */
    const NAME = 'Hub';

    /**
     * @var array
     */
    protected $clientConfig = [];

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * 构造函数.
     *
     * @param array $config 配置.
     * @param array $providers 服务.
     * @return void
     */
    public function __construct(
        array $config = [],
        array $providers = []
    ) {
        $this->clientConfig = $config;
        $this->providers = $providers;

        $this->batchRegister($this->getRegisters());
    }

    /**
     * 构造函数.
     *
     * @param array $config 配置.
     * @param array $providers 服务.
     * @return self
     */
    public static function make(
        array $config = [],
        array $providers = []
    ): self {
        return new static($config, $providers);
    }

    /**
     * 得到配置.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->clientConfig;
    }

    /**
     * 得到服务.
     *
     * @return array
     */
    public function getRegisters(): array
    {
        return array_merge([
            CacheProvider::class,
            ConfigProvider::class,
            HttpProvider::class,
            RequestProvider::class,
            ResponseProvider::class,
        ], $this->providers);
    }

    /**
     * 批量注册服务.
     *
     * @param array $providers 服务.
     * @return void
     */
    public function batchRegister(array $providers): void
    {
        foreach ($providers as $provider) {
            parent::register(new $provider());
        }
    }

    /**
     * 重新绑定服务.
     *
     * @param string $id 唯一标识.
     * @param mixed  $value 值.
     * @return void
     */
    public function rebind(string $id, $value): void
    {
        $this->offsetUnset($id);
        $this->offsetSet($id, $value);
    }
}
