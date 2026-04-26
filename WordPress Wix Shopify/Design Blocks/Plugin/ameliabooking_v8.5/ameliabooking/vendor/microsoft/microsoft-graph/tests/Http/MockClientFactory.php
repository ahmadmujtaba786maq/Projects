<?php

namespace Microsoft\Graph\Http\Test;

use AmeliaGuzzleHttp\Client;
use AmeliaGuzzleHttp\Handler\MockHandler;
use AmeliaGuzzleHttp\HandlerStack;

class MockClientFactory
{
    /**
     * Creates a mock Guzzle client with optional mock responses
     *
     * @param array $clientConfig - Guzzle client Request options
     * @param array $mockResponses - Accepts \AmeliaGuzzleHttp\Psr7\Response and \AmeliaGuzzleHttp\Exception
     * @return \AmeliaGuzzleHttp\Client
     */
    public static function create($clientConfig = [], $mockResponses = [])
    {
        if ($mockResponses)
        {
            $stack = HandlerStack::create(new MockHandler($mockResponses));
            $clientConfig['handler'] = $stack;
            return new Client($clientConfig);
        }
        return new Client($clientConfig);
    }
}