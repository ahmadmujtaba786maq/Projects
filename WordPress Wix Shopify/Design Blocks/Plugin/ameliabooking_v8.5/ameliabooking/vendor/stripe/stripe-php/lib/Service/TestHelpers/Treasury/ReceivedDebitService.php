<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\TestHelpers\Treasury;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class ReceivedDebitService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Use this endpoint to simulate a test mode ReceivedDebit initiated by a third
     * party. In live mode, you can’t directly create ReceivedDebits initiated by third
     * parties.
     *
     * @param null|array{amount: int, currency: string, description?: string, expand?: string[], financial_account: string, initiating_payment_method_details?: array{type: string, us_bank_account?: array{account_holder_name?: string, account_number?: string, routing_number?: string}}, network: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Treasury\ReceivedDebit
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/test_helpers/treasury/received_debits', $params, $opts);
    }
}
