<?php

declare(strict_types=1);

namespace Doclify;

use GuzzleHttp\Client as HttpClient;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

use GuzzleHttp\Exception\ClientException;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemPoolInterface;


/**
* Doclify Client
*/
class Client
{
    /**
     * @var string
     */
    private $repository;

    /**
     * @var string
     */
    private $token;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $cacheConfig = [];

    /**
     * @param array $options
     */
    function __construct(array $options)
    {
        if (!isset($options['repository'])) {
            throw new InvalidArgumentException('repository option is missing.');
        }

        if (!isset($options['token'])) {
            throw new InvalidArgumentException('repository token is missing.');
        }

        $this->repository = $options['repository'];
        $this->token = $options['token'];

        $this->httpClient = new HttpClient();
    }

    public function getCache() {
        return $this->cache;
    }
    
    public function addCache(CacheItemPoolInterface $cache, array $config = [])
    {
        $this->cache = $cache;
        $this->cacheConfig = $config;
    }

    /**
     * @param string[] $query
     */
    private function getUri(string $path, string $host = null, array $query = []): UriInterface
    {
        $host = $host ? new Uri($host) : new Uri("https://{$this->repository}.cdn.doclify.io/api/v2/");
        $uri = UriResolver::resolve($host, new Uri($path));

        if ($query) {
            $uri = $uri->withQuery(\http_build_query(
                $query,
                '',
                '&',
                \PHP_QUERY_RFC3986
            ));
        }

        return $uri;
    }

    private function getHeaders($headers, $body = null)
    {
        $defaultHeaders = [
            'Accept-Encoding' => 'gzip',
            'Accept' => 'application/json',
            'User-Agent' => 'Doclify PHP SDK',
            'x-api-key' => $this->token
        ];

        if ($body) {
            $defaultHeaders['Content-Type'] = 'application/json';
        }

        return \array_merge($defaultHeaders, $headers);
    }

    public function sendRequest(string $endpoint, array $options = [])
    {
        $body = $options['body'] ?? null;

        $uri = $this->getUri(
            $endpoint,
            $options['host'] ?? null,
            $options['query'] ?? []
        );

        $headers = $this->getHeaders($options['headers'] ?? [], $body);

        $request = new Request('GET', $uri, $headers, $body);

        $exception = null;

        try {
            $response = $this->httpClient->send($request);
        } catch (ClientException $exception) {
            throw new Exception($exception);
        }
        
        $stream = $response->getBody()->getContents();

        return \json_decode($stream);
    }

    public function request(string $endpoint, array $options = [])
    {
        $data = $this->sendRequest($endpoint, $options);
        
        return $data;
    }

    private function getRequestKey(string $endpoint, array $query): string {
        return \sha1($endpoint . ":" . \json_encode($query));
    }

    public function requestWithCache(string $endpoint, array $options = [])
    {
        if (!$this->cache) {
            return $this->request($endpoint, $options);
        }

        $key = $this->getRequestKey($endpoint, $options['query'] ?? []);

        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        $data = $this->sendRequest($endpoint, $options);

        $item->set($data);
        $item->expiresAfter($this->cacheConfig['ttl'] ?? 5 * 60);

        $this->cache->save($item);

        return $data;
    }

    public function documents(): Documents {
        return new Documents($this);
    }
}
