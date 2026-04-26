<?php

namespace AmeliaHttp\Client\Common;

use AmeliaHttp\Client\HttpAsyncClient;
use AmeliaHttp\Client\HttpClient;

/**
 * Emulates an HTTP client.
 *
 * This should be replaced by an anonymous class in PHP 7.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class EmulatedHttpClient implements HttpClient, HttpAsyncClient
{
    use HttpAsyncClientDecorator;
    use HttpClientEmulator;

    /**
     * @param HttpAsyncClient $httpAsyncClient
     */
    public function __construct(HttpAsyncClient $httpAsyncClient)
    {
        $this->httpAsyncClient = $httpAsyncClient;
    }
}
