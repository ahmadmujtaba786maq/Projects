<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\TestHelpers\Terminal;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class ReaderService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Presents a payment method on a simulated reader. Can be used to simulate
     * accepting a payment, saving a card or refunding a transaction.
     *
     * @param string $id
     * @param null|array{amount_tip?: int, card_present?: array{number?: string}, expand?: string[], interac_present?: array{number?: string}, type?: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Terminal\Reader
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function presentPaymentMethod($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/terminal/readers/%s/present_payment_method', $id), $params, $opts);
    }
}
