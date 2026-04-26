<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\Treasury;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class ReceivedDebitService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Returns a list of ReceivedDebits.
     *
     * @param null|array{ending_before?: string, expand?: string[], financial_account: string, limit?: int, starting_after?: string, status?: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Collection<\AmeliaStripe\Treasury\ReceivedDebit>
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/treasury/received_debits', $params, $opts);
    }

    /**
     * Retrieves the details of an existing ReceivedDebit by passing the unique
     * ReceivedDebit ID from the ReceivedDebit list.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Treasury\ReceivedDebit
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/treasury/received_debits/%s', $id), $params, $opts);
    }
}
