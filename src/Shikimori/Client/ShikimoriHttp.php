<?php

declare(strict_types=1);

namespace App\Shikimori\Client;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ShikimoriHttp implements Shikimori
{
    private Config $config;
    private HttpClientInterface $http;
    private RateLimiter $rateLimiter;
    private SerializerInterface $serializer;

    public function __construct(
        Config $config,
        HttpClientInterface $http,
        RateLimiter $rateLimiter,
        SerializerInterface $serializer,
    ) {
        $this->config = $config;
        $this->http = $http->withOptions([
            'base_uri' => $config->baseUrl,
            'headers' => [
                'User-Agent' => $config->app,
            ],
            'max_duration' => 10.0,
        ]);
        $this->rateLimiter = $rateLimiter;
        $this->serializer = $serializer;
    }

    public function request(Request $request): object|array
    {
        $this->rateLimiter->wait();

        $options = [];
        if ($request instanceof AuthenticatedRequest) {
            $options['auth_bearer'] = $request->token();
        }
        if ($request instanceof FormRequest) {
            $options['body'] = $request->form($this->config);
        }

        $response = $this->http->request($request->method(), $request->uri(), $options);
        $content = $response->getContent();

        $responseClass = $request->responseClass();
        if ($request instanceof ListRequest) {
            $responseClass .= '[]';
        }

        return $this->serializer->deserialize($content, $responseClass, 'json');
    }
}
