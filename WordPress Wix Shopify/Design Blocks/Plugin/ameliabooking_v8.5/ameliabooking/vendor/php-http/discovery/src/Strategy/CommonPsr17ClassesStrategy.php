<?php

namespace AmeliaHttp\Discovery\Strategy;

use AmeliaPsr\Http\Message\RequestFactoryInterface;
use AmeliaPsr\Http\Message\ResponseFactoryInterface;
use AmeliaPsr\Http\Message\ServerRequestFactoryInterface;
use AmeliaPsr\Http\Message\StreamFactoryInterface;
use AmeliaPsr\Http\Message\UploadedFileFactoryInterface;
use AmeliaPsr\Http\Message\UriFactoryInterface;

/**
 * @internal
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class CommonPsr17ClassesStrategy implements DiscoveryStrategy
{
    /**
     * @var array
     */
    private static $classes = [
        RequestFactoryInterface::class => [
            'AmeliaNyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\RequestFactory',
            'Http\Factory\Diactoros\RequestFactory',
            'Http\Factory\Guzzle\RequestFactory',
            'Http\Factory\Slim\RequestFactory',
        ],
        ResponseFactoryInterface::class => [
            'AmeliaNyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\ResponseFactory',
            'Http\Factory\Diactoros\ResponseFactory',
            'Http\Factory\Guzzle\ResponseFactory',
            'Http\Factory\Slim\ResponseFactory',
        ],
        ServerRequestFactoryInterface::class => [
            'AmeliaNyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\ServerRequestFactory',
            'Http\Factory\Diactoros\ServerRequestFactory',
            'Http\Factory\Guzzle\ServerRequestFactory',
            'Http\Factory\Slim\ServerRequestFactory',
        ],
        StreamFactoryInterface::class => [
            'AmeliaNyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\StreamFactory',
            'Http\Factory\Diactoros\StreamFactory',
            'Http\Factory\Guzzle\StreamFactory',
            'Http\Factory\Slim\StreamFactory',
        ],
        UploadedFileFactoryInterface::class => [
            'AmeliaNyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\UploadedFileFactory',
            'Http\Factory\Diactoros\UploadedFileFactory',
            'Http\Factory\Guzzle\UploadedFileFactory',
            'Http\Factory\Slim\UploadedFileFactory',
        ],
        UriFactoryInterface::class => [
            'AmeliaNyholm\Psr7\Factory\Psr17Factory',
            'Zend\Diactoros\UriFactory',
            'Http\Factory\Diactoros\UriFactory',
            'Http\Factory\Guzzle\UriFactory',
            'Http\Factory\Slim\UriFactory',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        $candidates = [];
        if (isset(self::$classes[$type])) {
            foreach (self::$classes[$type] as $class) {
                $candidates[] = ['class' => $class, 'condition' => [$class]];
            }
        }

        return $candidates;
    }
}
