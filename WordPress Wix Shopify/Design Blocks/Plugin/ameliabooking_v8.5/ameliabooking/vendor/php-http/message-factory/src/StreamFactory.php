<?php

namespace AmeliaHttp\Message;

use AmeliaPsr\Http\Message\StreamInterface;

/**
 * Factory for PSR-7 Stream.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 *
 * @deprecated since version 1.1, use AmeliaPsr\Http\Message\StreamFactoryInterface instead.
 */
interface StreamFactory
{
    /**
     * Creates a new PSR-7 stream.
     *
     * @param string|resource|StreamInterface|null $body
     *
     * @return StreamInterface
     *
     * @throws \InvalidArgumentException if the stream body is invalid
     * @throws \RuntimeException         if creating the stream from $body fails
     */
    public function createStream($body = null);
}
