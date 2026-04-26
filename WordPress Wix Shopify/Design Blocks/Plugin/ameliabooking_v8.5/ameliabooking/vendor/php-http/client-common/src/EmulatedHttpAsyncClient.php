<?php

namespace AmeliaHttp\Client\Common;

use AmeliaHttp\Client\HttpAsyncClient;
use AmeliaHttp\Client\HttpClient;
use Psr\Http\Client\ClientInterface;

/**
 * Emulates an async HTTP client.
 *
 * This should be replaced by an anonymous class in PHP 7.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class EmulatedHttpAsyncClient implements HttpClient, HttpAsyncClient
{
    use HttpAsyncClientEmulator;
    use HttpClientDecorator;

    /**
     * @param HttpClient|ClientInterface $httpClient
     */
    public function __construct($httpClient)
    {
        if (!($httpClient instanceof HttpClient) && !($httpClient instanceof ClientInterface)) {
            throw new \LogicException('Client must be an instance of AmeliaHttp\\Client\\HttpClient or Psr\\Http\\Client\\ClientInterface');
        }

        $this->httpClient = $httpClient;
    }
}
