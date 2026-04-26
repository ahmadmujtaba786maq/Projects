<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\FinancialConnections;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class SessionService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * To launch the Financial Connections authorization flow, create a
     * <code>Session</code>. The session’s <code>client_secret</code> can be used to
     * launch the flow using Stripe.js.
     *
     * @param null|array{account_holder: array{account?: string, customer?: string, type: string}, expand?: string[], filters?: array{account_subcategories?: string[], countries?: string[]}, permissions: string[], prefetch?: string[], return_url?: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\FinancialConnections\Session
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/financial_connections/sessions', $params, $opts);
    }

    /**
     * Retrieves the details of a Financial Connections <code>Session</code>.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\FinancialConnections\Session
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/financial_connections/sessions/%s', $id), $params, $opts);
    }
}
