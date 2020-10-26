<?php

namespace PhpHos\Hub\Services\Express;

/**
 * Class KDNiao.
 *
 * @author sean <maoxfjob@163.com>
 */
class KDNiao
{
    /**
     * @var array 配置.
     */
    protected $config;

    /**
     * 构造函数.
     *
     * @param array $config 配置.
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 请求.
     *
     * @param string $url 请求地址.
     * @param string $requestType 请求指令类型.
     * @param array $requestData 请求内容需进行 URL(utf-8) 编码.
     * 请求内容 JSON 格式，须和 DataType 一致.
     * @param string $DataType 请求返回数据类型.
     * @return array
     */
    public function request(
        string $url,
        string $requestType,
        array $requestData,
        string $dataType = '2'
    ): array {
        $requestData = json_encode($requestData);
        $data = [
            'EBusinessID' => $this->config['ebusiness_id'],
            'RequestType' => $requestType,
            'RequestData' => urlencode($requestData),
            'DataType' => $dataType,
        ];
        $data['DataSign'] = $this->encrypt($requestData, $this->config['app_key']);
        $response = $this->post($url, $data);
        return json_decode($response, true);
    }

    /**
     * 签名生成.
     *
     * @param string $data 内容.
     * @param string $appkey Appkey.
     * @return string
     */
    protected function encrypt(string $data, string $appkey): string
    {
        return urlencode(base64_encode(md5($data . $appkey)));
    }

    /**
     * 提交数据.
     *
     * @param string $url 请求 URL.
     * @param array $data 提交的数据.
     * @return string
     */
    protected function post(string $url, array $data): string
    {
        $temps = array();
        foreach ($data as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if (empty($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        $httpheader .= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }
}
