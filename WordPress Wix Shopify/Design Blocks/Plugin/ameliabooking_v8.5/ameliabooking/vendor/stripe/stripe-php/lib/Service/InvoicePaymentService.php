<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class InvoicePaymentService extends AbstractService
{
    /**
     * When retrieving an invoice, there is an includable payments property containing
     * the first handful of those items. There is also a URL where you can retrieve the
     * full (paginated) list of payments.
     *
     * @param null|array{ending_before?: string, expand?: string[], invoice?: string, limit?: int, payment?: array{payment_intent?: string, type: string}, starting_after?: string, status?: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Collection<\AmeliaStripe\InvoicePayment>
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/invoice_payments', $params, $opts);
    }

    /**
     * Retrieves the invoice payment with the given ID.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\InvoicePayment
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/invoice_payments/%s', $id), $params, $opts);
    }
}
