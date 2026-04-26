<?php

namespace AmeliaHttp\Client\Common;

use AmeliaHttp\Client\HttpClient;
use Psr\Http\Client\ClientInterface;
use AmeliaPsr\Http\Message\RequestInterface;

/**
 * Decorates an HTTP Client.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait HttpClientDecorator
{
    /**
     * @var HttpClient|ClientInterface
     */
    protected $httpClient;

    /**
     * {@inheritdoc}
     *
     * @see HttpClient::sendRequest
     */
    public function sendRequest(RequestInterface $request)
    {
        return $this->httpClient->sendRequest($request);
    }
}
