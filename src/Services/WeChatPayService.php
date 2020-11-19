<?php

namespace PhpHos\Hub\Services;

use Monolog\Logger;
use GuzzleHttp\HandlerStack;
use PhpHos\Hub\Middlewares\LogMiddleware;
use PhpHos\Hub\Middlewares\RetryMiddleware;
use PhpHos\Hub\Services\WeChat\NoopValidator;
use WechatPay\GuzzleMiddleware\Auth\PrivateKeySigner;
use WechatPay\GuzzleMiddleware\Util\AesUtil;
use WechatPay\GuzzleMiddleware\Util\MediaUtil;
use WechatPay\GuzzleMiddleware\WechatPayMiddleware;
use WechatPay\GuzzleMiddleware\Util\PemUtil;
use WechatPay\GuzzleMiddleware\Util\SensitiveInfoCrypto;

/**
 * Class WeChatPayService.
 *
 * @author sean <maoxfjob@163.com>
 */
class WeChatPayService extends Service
{
    protected $encryptor;

    /**
     * 初始化.
     *
     * @return void
     */
    protected function init(): void
    {
        $this->config = array_replace_recursive([
            'aes_key' => '',
            'base_uri' => 'https://api.mch.weixin.qq.com/v3/',
            'merchant_id' => '',
            'merchant_serial_number' => '',
            'merchant_private_key' => '',
            'wechatpay_certificate' => '',
            'wechatpay_serial_no' => '',
            'log_path' => sys_get_temp_dir() . '/phphos/hub/wechatpay.log',
        ], $this->config);

        if ($this->config['merchant_private_key']) {
            $this->config['merchant_private_key'] = PemUtil::loadPrivateKey($this->config['merchant_private_key']);
        }

        if ($this->config['wechatpay_certificate']) {
            $this->config['wechatpay_certificate'] = PemUtil::loadCertificate($this->config['wechatpay_certificate']);
        }
    }

    /**
     * 请求.
     *
     * @param string $uri 链接.
     * @param array $params 参数.
     * @return array
     */
    public function fetch(string $uri = '', array $params = []): array
    {
        $options = [
            'handler' => $this->verifyStack(),
            'base_uri' => $this->config['base_uri'],
            'headers' => [
                'Accept' => 'application/json',
                'Wechatpay-Serial' => $this->config['wechatpay_serial_no'],
            ],
        ];
        if ($params) {
            $method = 'POST';
            $options['json'] = $params;
        } else {
            $method = 'GET';
        }
        $result = $this->request($method, $uri, $options);
        $result and $result = json_decode($result, true);
        return $result;
    }

    /**
     * 请求(无验证).
     *
     * @param string $uri 链接.
     * @param array $params 参数.
     * @return mixed
     */
    public function fetchNoVerify(
        string $uri = '',
        array $params = []
    ) {
        $options = [
            'handler' => $this->noopStack(),
            'base_uri' => $this->config['base_uri'],
            'headers' => [
                'Accept' => 'application/json',
                'Wechatpay-Serial' => $this->config['wechatpay_serial_no'],
            ],
        ];
        if ($params) {
            $method = 'POST';
            $options['json'] = $params;
        } else {
            $method = 'GET';
        }
        return $this->request($method, $uri, $options);
    }

    /**
     * 媒体.
     *
     * @param string $uri 链接.
     * @param string $path 路径.
     * @return array
     */
    public function media(string $uri, string $path): array
    {
        $media = new MediaUtil($path);
        $result = $this->request('POST', $uri, [
            'handler' => $this->verifyStack(),
            'body' => $media->getStream(),
            'headers' => [
                'Accept' => 'application/json',
                'content-type' => $media->getContentType(),
            ],
        ]);
        $result and $result = json_decode($result, true);
        return $result;
    }

    /**
     * 证书.
     *
     * @return array
     */
    public function cert(): array
    {
        $result = $this->request('GET', 'certificates', [
            'handler' => $this->noopStack(),
        ]);
        $result and $result = json_decode($result, true);

        if (empty($result['data'])) {
            throw new \Exception('请求证书失败: ' . json_encode($result));
        }

        foreach ($result['data'] as &$item) {
            $item['certificate'] = $this->aesDecrypt(
                $item['encrypt_certificate']['associated_data'],
                $item['encrypt_certificate']['nonce'],
                $item['encrypt_certificate']['ciphertext']
            );
        }

        return $result;
    }

    /**
     * 加密.
     *
     * @param string $str 字符串.
     * @return SensitiveInfoCrypto
     */
    public function encrypt(string $str): SensitiveInfoCrypto
    {
        return $this->getEncryptor()($str);
    }

    /**
     * 解密.
     *
     * @param string $str 字符串.
     * @return string
     */
    public function decrypt(string $str): string
    {
        return $this->getEncryptor()->setStage('decrypt')($str);
    }

    /**
     * AES 解密.
     *
     * @param string $associatedData AES GCM additional authentication data.
     * @param string $nonceStr AES GCM nonce.
     * @param string $ciphertext AES GCM cipher text.
     * @return string|bool
     */
    public function aesDecrypt(
        string $associatedData,
        string $nonceStr,
        string $ciphertext
    ) {
        return (new AesUtil($this->config['aes_key']))
            ->decryptToString(
                $associatedData,
                $nonceStr,
                $ciphertext
            );
    }

    /**
     * 签名.
     *
     * @param array $params 参数.
     * @return string
     */
    public function sign(array $params): string
    {
        $message = join("\n", $params) . "\n";

        $signer = new PrivateKeySigner(
            $this->config['wechatpay_serial_no'],
            $this->config['merchant_private_key']
        );

        return $signer->sign($message)->getSign();
    }

    /**
     * 签名.
     *
     * @param string $method 请求方式.
     * @param string $uri 路由.
     * @param array $options 选项.
     * @return mixed
     */
    public function request(
        string $method,
        string $uri,
        array $options
    ) {
        $options = array_merge([
            'http_errors' => false,
            'base_uri' => $this->config['base_uri'],
            'headers' => ['Accept' => 'application/json'],
        ], $options);

        $http = $this->hub['http'];
        $response = $http->request($method, $uri, $options);
        $response->getBody()->rewind();
        return $response->getBody()->getContents();
    }

    protected function getEncryptor(): SensitiveInfoCrypto
    {
        if (!$this->encryptor) {
            $this->encryptor = new SensitiveInfoCrypto(
                $this->config['wechatpay_certificate'],
                $this->config['merchant_private_key']
            );
        }

        return $this->encryptor;
    }

    protected function noopStack(): HandlerStack
    {
        $stack = HandlerStack::create();

        // $stack->push(RetryMiddleware::make($this->hub)->build(), 'retry');
        $stack->push(LogMiddleware::make($this->hub, [
            [
                'params' => [
                    'path' => $this->config['log_path'],
                    'level' => Logger::DEBUG,
                ]
            ]
        ])->build(), 'log');
        $stack->unshift(WechatPayMiddleware::builder()
            ->withMerchant(
                $this->config['merchant_id'],
                $this->config['merchant_serial_number'],
                $this->config['merchant_private_key']
            )
            ->withValidator(new NoopValidator())
            ->build(), 'wechatpay');

        return $stack;
    }

    protected function verifyStack(): HandlerStack
    {
        $stack = HandlerStack::create();

        // $stack->push(RetryMiddleware::make($this->hub)->build(), 'retry');
        $stack->push(LogMiddleware::make($this->hub, [
            [
                'params' => [
                    'path' => $this->config['log_path'],
                    'level' => Logger::DEBUG,
                ]
            ]
        ])->build(), 'log');
        $stack->unshift(WechatPayMiddleware::builder()
            ->withMerchant(
                $this->config['merchant_id'],
                $this->config['merchant_serial_number'],
                $this->config['merchant_private_key']
            )
            ->withWechatPay([$this->config['wechatpay_certificate']])
            ->build(), 'wechatpay');

        return $stack;
    }
}
