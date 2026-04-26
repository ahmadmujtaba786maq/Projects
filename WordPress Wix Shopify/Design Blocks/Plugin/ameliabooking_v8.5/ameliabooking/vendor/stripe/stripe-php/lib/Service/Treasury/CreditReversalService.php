<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\Treasury;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class CreditReversalService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Returns a list of CreditReversals.
     *
     * @param null|array{ending_before?: string, expand?: string[], financial_account: string, limit?: int, received_credit?: string, starting_after?: string, status?: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Collection<\AmeliaStripe\Treasury\CreditReversal>
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/treasury/credit_reversals', $params, $opts);
    }

    /**
     * Reverses a ReceivedCredit and creates a CreditReversal object.
     *
     * @param null|array{expand?: string[], metadata?: array<string, string>, received_credit: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Treasury\CreditReversal
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/treasury/credit_reversals', $params, $opts);
    }

    /**
     * Retrieves the details of an existing CreditReversal by passing the unique
     * CreditReversal ID from either the CreditReversal creation request or
     * CreditReversal list.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Treasury\CreditReversal
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/treasury/credit_reversals/%s', $id), $params, $opts);
    }
}
