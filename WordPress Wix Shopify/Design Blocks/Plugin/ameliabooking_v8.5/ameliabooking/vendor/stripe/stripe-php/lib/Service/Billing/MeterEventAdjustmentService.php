<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\Billing;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class MeterEventAdjustmentService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Creates a billing meter event adjustment.
     *
     * @param null|array{cancel?: array{identifier?: string}, event_name: string, expand?: string[], type: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Billing\MeterEventAdjustment
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/billing/meter_event_adjustments', $params, $opts);
    }
}
