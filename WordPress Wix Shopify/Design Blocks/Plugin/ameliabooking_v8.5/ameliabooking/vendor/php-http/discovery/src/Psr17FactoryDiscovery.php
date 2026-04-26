<?php

namespace AmeliaHttp\Discovery;

use AmeliaHttp\Discovery\Exception\DiscoveryFailedException;
use AmeliaPsr\Http\Message\RequestFactoryInterface;
use AmeliaPsr\Http\Message\ResponseFactoryInterface;
use AmeliaPsr\Http\Message\ServerRequestFactoryInterface;
use AmeliaPsr\Http\Message\StreamFactoryInterface;
use AmeliaPsr\Http\Message\UploadedFileFactoryInterface;
use AmeliaPsr\Http\Message\UriFactoryInterface;

/**
 * Finds PSR-17 factories.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class Psr17FactoryDiscovery extends ClassDiscovery
{
    private static function createException($type, Exception $e)
    {
        return new \AmeliaHttp\Discovery\Exception\NotFoundException(
            'No PSR-17 '.$type.' found. Install a package from this list: https://packagist.org/providers/psr/http-factory-implementation',
            0,
            $e
        );
    }

    /**
     * @return RequestFactoryInterface
     *
     * @throws Exception\NotFoundException
     */
    public static function findRequestFactory()
    {
        try {
            $messageFactory = static::findOneByType(RequestFactoryInterface::class);
        } catch (DiscoveryFailedException $e) {
            throw self::createException('request factory', $e);
        }

        return static::instantiateClass($messageFactory);
    }

    /**
     * @return ResponseFactoryInterface
     *
     * @throws Exception\NotFoundException
     */
    public static function findResponseFactory()
    {
        try {
            $messageFactory = static::findOneByType(ResponseFactoryInterface::class);
        } catch (DiscoveryFailedException $e) {
            throw self::createException('response factory', $e);
        }

        return static::instantiateClass($messageFactory);
    }

    /**
     * @return ServerRequestFactoryInterface
     *
     * @throws Exception\NotFoundException
     */
    public static function findServerRequestFactory()
    {
        try {
            $messageFactory = static::findOneByType(ServerRequestFactoryInterface::class);
        } catch (DiscoveryFailedException $e) {
            throw self::createException('server request factory', $e);
        }

        return static::instantiateClass($messageFactory);
    }

    /**
     * @return StreamFactoryInterface
     *
     * @throws Exception\NotFoundException
     */
    public static function findStreamFactory()
    {
        try {
            $messageFactory = static::findOneByType(StreamFactoryInterface::class);
        } catch (DiscoveryFailedException $e) {
            throw self::createException('stream factory', $e);
        }

        return static::instantiateClass($messageFactory);
    }

    /**
     * @return UploadedFileFactoryInterface
     *
     * @throws Exception\NotFoundException
     */
    public static function findUploadedFileFactory()
    {
        try {
            $messageFactory = static::findOneByType(UploadedFileFactoryInterface::class);
        } catch (DiscoveryFailedException $e) {
            throw self::createException('uploaded file factory', $e);
        }

        return static::instantiateClass($messageFactory);
    }

    /**
     * @return UriFactoryInterface
     *
     * @throws Exception\NotFoundException
     */
    public static function findUrlFactory()
    {
        try {
            $messageFactory = static::findOneByType(UriFactoryInterface::class);
        } catch (DiscoveryFailedException $e) {
            throw self::createException('url factory', $e);
        }

        return static::instantiateClass($messageFactory);
    }
}
