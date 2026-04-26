<?php

namespace AmeliaHttp\Message\MessageFactory;

use AmeliaGuzzleHttp\Psr7\Request;
use AmeliaGuzzleHttp\Psr7\Response;
use AmeliaHttp\Message\MessageFactory;

/**
 * Creates Guzzle messages.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class GuzzleMessageFactory implements MessageFactory
{
    /**
     * {@inheritdoc}
     */
    public function createRequest(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ) {
        return new Request(
            $method,
            $uri,
            $headers,
            $body,
            $protocolVersion
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(
        $statusCode = 200,
        $reasonPhrase = null,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ) {
        return new Response(
            $statusCode,
            $headers,
            $body,
            $protocolVersion,
            $reasonPhrase
        );
    }
}
