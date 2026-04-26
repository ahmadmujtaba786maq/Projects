<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class TaxIdService extends AbstractService
{
    /**
     * Returns a list of tax IDs.
     *
     * @param null|array{ending_before?: string, expand?: string[], limit?: int, owner?: array{account?: string, customer?: string, type: string}, starting_after?: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Collection<\AmeliaStripe\TaxId>
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/tax_ids', $params, $opts);
    }

    /**
     * Creates a new account or customer <code>tax_id</code> object.
     *
     * @param null|array{expand?: string[], owner?: array{account?: string, customer?: string, type: string}, type: string, value: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\TaxId
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/tax_ids', $params, $opts);
    }

    /**
     * Deletes an existing account or customer <code>tax_id</code> object.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\TaxId
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function delete($id, $params = null, $opts = null)
    {
        return $this->request('delete', $this->buildPath('/v1/tax_ids/%s', $id), $params, $opts);
    }

    /**
     * Retrieves an account or customer <code>tax_id</code> object.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\TaxId
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/tax_ids/%s', $id), $params, $opts);
    }
}
