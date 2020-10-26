<?php

namespace PhpHos\Hub\Middlewares;

/**
 * Class WeChatAccessTokenMiddleware.
 * 
 * $config = [
 *      'uri' => '/cgi-bin/token',
 *      'base_uri' => 'https://api.weixin.qq.com',
 *      'appid' => '',
 *      'secret' => '',
 *      'key' => 'access_token',
 *      'expires_in' => 7200,
 *      'cache_prefix' => 'hub.wechat.access_token.',
 * ];
 * 
 * @author sean <maoxfjob@163.com>
 */
class WeChatAccessTokenMiddleware extends Middleware
{
    protected function init(): void
    {
        $this->config = array_replace_recursive([
            'uri' => '/cgi-bin/token',
            'base_uri' => 'https://api.weixin.qq.com',
            'appid' => '',
            'secret' => '',
            'key' => 'access_token',
            'expires_in' => 7200,
            'cache_prefix' => 'hub.wechat.access_token.',
        ], $this->config);
    }

    protected function request(&$request, &$options): void
    {
        parse_str($request->getUri()->getQuery(), $query);

        $accessToken = $this->getAccessToken();
        $query[$this->config['key']] = $accessToken;
        $query = http_build_query($query);

        $uri = $request->getUri()->withQuery($query);
        $request = $request->withUri($uri);
    }

    public function getAccessToken(): string
    {
        $cache = $this->hub['cache'];
        $cacheKey = $this->cacheKey();

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey)[$this->config['key']];
        }

        return $this->fetchAccessToken();
    }

    public function fetchAccessToken(): string
    {
        $response = $this->hub['http']->request(
            'GET',
            $this->config['uri'],
            [
                'base_uri' => $this->config['base_uri'],
                'query' => [
                    'grant_type' => 'client_credential',
                    'appid' => $this->config['appid'],
                    'secret' => $this->config['secret'],
                ],
            ]
        );
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);

        if (empty($result[$this->config['key']])) {
            throw new \Exception('请求 access_token 失败: ' . json_encode($result));
        }

        $this->hub['cache']->set($this->cacheKey(), [
            'access_token' => $result[$this->config['key']],
            'expires_in' => $this->config['expires_in'],
        ], $this->config['expires_in'] - 500);

        return $result[$this->config['key']];
    }

    protected function cacheKey(): string
    {
        return $this->config['cache_prefix']
            . md5(json_encode([
                $this->config['appid'],
                $this->config['secret'],
            ]));
    }
}
