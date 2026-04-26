<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\Climate;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class ProductService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Lists all available Climate product objects.
     *
     * @param null|array{ending_before?: string, expand?: string[], limit?: int, starting_after?: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Collection<\AmeliaStripe\Climate\Product>
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/climate/products', $params, $opts);
    }

    /**
     * Retrieves the details of a Climate product with the given ID.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Climate\Product
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/climate/products/%s', $id), $params, $opts);
    }
}
