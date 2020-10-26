<?php

namespace PhpHos\Hub\Services;

use PhpHos\Hub\Services\Express\KDNiao;

/**
 * Class ExpressService.
 *
 * @author sean <maoxfjob@163.com>
 */
class ExpressService extends Service
{
    /**
     * 初始化.
     *
     * @return void
     */
    protected function init(): void
    {
        $this->config = array_replace_recursive([
            'kdniao' => [
                'ebusiness_id' => '',
                'app_key' => '',
            ]
        ], $this->config);
    }

    /**
     * 快递鸟请求.
     *
     * @param string $url 请求地址.
     * @param string $type 请求指令类型.
     * @param array $params 请求内容需进行 URL(utf-8) 编码.
     * 请求内容 JSON 格式，须和 DataType 一致.
     * @return array
     */
    public function kdniao(
        string $url,
        string $type,
        array $params
    ): array {
        $kdniao = new KDNiao($this->config['kdniao']);
        return $kdniao->request($url, $type, $params);
    }
}
