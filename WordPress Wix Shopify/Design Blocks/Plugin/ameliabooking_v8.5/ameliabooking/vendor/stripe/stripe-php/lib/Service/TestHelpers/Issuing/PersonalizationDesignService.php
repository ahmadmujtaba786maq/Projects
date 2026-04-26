<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\TestHelpers\Issuing;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class PersonalizationDesignService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Updates the <code>status</code> of the specified testmode personalization design
     * object to <code>active</code>.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Issuing\PersonalizationDesign
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function activate($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/issuing/personalization_designs/%s/activate', $id), $params, $opts);
    }

    /**
     * Updates the <code>status</code> of the specified testmode personalization design
     * object to <code>inactive</code>.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Issuing\PersonalizationDesign
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function deactivate($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/issuing/personalization_designs/%s/deactivate', $id), $params, $opts);
    }

    /**
     * Updates the <code>status</code> of the specified testmode personalization design
     * object to <code>rejected</code>.
     *
     * @param string $id
     * @param null|array{expand?: string[], rejection_reasons: array{card_logo?: string[], carrier_text?: string[]}} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Issuing\PersonalizationDesign
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function reject($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/issuing/personalization_designs/%s/reject', $id), $params, $opts);
    }
}
