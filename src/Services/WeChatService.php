<?php

namespace PhpHos\Hub\Services;

use Monolog\Logger;
use GuzzleHttp\HandlerStack;
use PhpHos\Hub\Middlewares\LogMiddleware;
use PhpHos\Hub\Middlewares\RetryMiddleware;
use PhpHos\Hub\Middlewares\WeChatAccessTokenMiddleware;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WeChatService.
 *
 * @author sean <maoxfjob@163.com>
 */
class WeChatService extends Service
{
    /**
     * 初始化.
     *
     * @return void
     */
    protected function init(): void
    {
        $this->config = array_replace_recursive([
            'appid' => '',
            'secret' => '',
            'token' => '',
            'aes_key' => '',
            'base_uri' => 'https://api.weixin.qq.com',
            'block_size' => 32,
            'log_path' => sys_get_temp_dir() . '/phphos/hub/wechatpay.log',
        ], $this->config);
    }

    /**
     * 响应.
     *
     * @param callable $handler 处理.
     * @param Request $request 请求.
     * @return Response
     */
    public function serve(
        callable $handler = null,
        Request $request = null
    ): Response {
        $request or $request = $this->hub['request'];

        if (!$this->validate($request)) {
            throw new \Exception('验证签名失败.', 400);
        }

        $echostr = $request->get('echostr');
        if (is_null($echostr)) {
            $data = $this->decrypt($request);

            $result = $handler
                ? call_user_func($handler, $data)
                : null;

            $response = $this->response($result);
        } else {
            $response = $this->response($echostr);
        }

        return $response;
    }

    /**
     * 请求.
     *
     * @param string|UriInterface $uri 链接.
     * @param string $method GET|POST.
     * @param array $params 参数.
     * @return ResponseInterface
     */
    public function fetch(
        $uri = '',
        string $method = 'GET',
        array $params = []
    ): ResponseInterface {
        $method = strtoupper($method);

        $stack = HandlerStack::create();
        $stack->push(WeChatAccessTokenMiddleware::make(
            $this->hub,
            [
                'appid' => $this->config['appid'],
                'secret' => $this->config['secret'],
            ]
        )->build(), 'accessToken');
        $stack->push(RetryMiddleware::make($this->hub, [
            'handler' => function ($response) {
                WeChatAccessTokenMiddleware::make(
                    $this->hub,
                    [
                        'appid' => $this->config['appid'],
                        'secret' => $this->config['secret'],
                    ]
                )->fetchAccessToken();
                return true;
            },
        ])->build(), 'retry');
        $stack->push(LogMiddleware::make($this->hub, [
            [
                'params' => [
                    'path' => $this->config['log_path'],
                    'level' => Logger::DEBUG,
                ]
            ]
        ])->build(), 'log');

        $options = [
            'handler' => $stack,
            'base_uri' => $this->config['base_uri'],
        ];
        $params and $method === 'GET'
            ? $options['query'] = $params
            : $options['json'] = $params;
        $response =  $this->hub['http']->request($method, $uri, $options);
        $response->getBody()->rewind();
        return $response;
    }

    protected function validate(Request $request): bool
    {
        $token = $this->config['token'];
        $timestamp = $request->get('timestamp');
        $nonce = $request->get('nonce');

        return $request->get('signature') === $this->signature([
            $token,
            $timestamp,
            $nonce
        ]);
    }

    protected static function signature(array $params): string
    {
        sort($params, SORT_STRING);

        return sha1(implode($params));
    }

    protected function arrayToXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $key => $value) {
            $xml .= '<' . $key . '>';

            if (is_array($value)) {
                $xml .= $this->arrayToXml($value);
            } else if (is_numeric($value)) {
                $xml .= $value;
            } else {
                $xml .= $this->cdata($value);
            }

            $xml .= '</' . $key . '>';
        }
        $xml .= '</xml>';

        return $xml;
    }

    protected function xmlToArray(string $xml): array
    {
        $backup = libxml_disable_entity_loader(true);

        $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xml = json_decode(json_encode($xml), true);

        libxml_disable_entity_loader($backup);

        return $xml;
    }

    protected function encrypt(string $xml): string
    {
        $text = substr(md5(uniqid(microtime(true), true)), -16);
        $text .= pack('N', strlen($xml)) . $xml;
        $text .= $this->config['appid'];

        $padding = $this->config['block_size'] - (strlen($text) % $this->config['block_size']);
        $pattern = chr($padding);

        $xml = $text . str_repeat($pattern, $padding);
        $key = base64_decode($this->config['aes_key'] . '=', true);

        $encrypted = openssl_encrypt(
            $xml,
            'aes-' . (8 * strlen($key)) . '-cbc',
            $key,
            OPENSSL_NO_PADDING,
            substr($key, 0, 16)
        );
        $encrypted = base64_encode($encrypted);

        $nonce = substr($this->config['appid'], 0, 10);
        $timestamp = time();
        $signature = $this->signature([
            $this->config['token'],
            $timestamp,
            $nonce,
            $encrypted
        ]);

        $response = [
            'Encrypt' => $encrypted,
            'MsgSignature' => $signature,
            'TimeStamp' => $timestamp,
            'Nonce' => $nonce,
        ];

        return $this->arrayToXml($response);
    }

    protected function decrypt(Request $request): array
    {
        $xml = $this->xmlToArray($request->getContent());
        $key = base64_decode($this->config['aes_key'] . '=', true);

        $decrypted = openssl_decrypt(
            base64_decode($xml['Encrypt'], true),
            'aes-' . (8 * strlen($key)) . '-cbc',
            $key,
            OPENSSL_NO_PADDING,
            substr($key, 0, 16)
        );

        $pad = ord(substr($decrypted, -1));
        $pad < 1 || $pad > 32 and $pad = 0;

        $result = substr($decrypted, 0, (strlen($decrypted) - $pad));

        $content = substr($result, 16, strlen($result));
        $contentLen = unpack('N', substr($content, 0, 4))[1];

        if (trim(substr($content, $contentLen + 4)) !== $this->config['appid']) {
            throw new \Exception('验证 APPID 失败.', 400);
        }

        $decrypted = substr($content, 4, $contentLen);
        return $this->xmlToArray($decrypted);
    }

    protected function cdata(string $str): string
    {
        return sprintf('<![CDATA[%s]]>', $str);
    }

    protected function response($result): Response
    {
        if ($result === null) {
            return $this->hub['response']::create('success');
        }

        if (is_array($result)) {
            $result = $this->encrypt($this->arrayToXml($result));
            return $this->hub['response']::create(
                $result,
                200,
                ['Content-Type' => 'application/xml']
            );
        }

        return $this->hub['response']::create($result);
    }
}
