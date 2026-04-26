<?php

namespace AmeliaHttp\Message;

use AmeliaPsr\Http\Message\RequestInterface;
use AmeliaPsr\Http\Message\ResponseInterface;

/**
 * Formats a request and/or a response as a string.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface Formatter
{
    /**
     * Formats a request.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    public function formatRequest(RequestInterface $request);

    /**
     * Formats a response.
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    public function formatResponse(ResponseInterface $response);
}
