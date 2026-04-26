<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\Treasury;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class TransactionService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Retrieves a list of Transaction objects.
     *
     * @param null|array{created?: array|int, ending_before?: string, expand?: string[], financial_account: string, limit?: int, order_by?: string, starting_after?: string, status?: string, status_transitions?: array{posted_at?: array|int}} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Collection<\AmeliaStripe\Treasury\Transaction>
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/treasury/transactions', $params, $opts);
    }

    /**
     * Retrieves the details of an existing Transaction.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Treasury\Transaction
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/treasury/transactions/%s', $id), $params, $opts);
    }
}
