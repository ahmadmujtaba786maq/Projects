<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\V2\Billing;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class MeterEventAdjustmentService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Creates a meter event adjustment to cancel a previously sent meter event.
     *
     * @param null|array{cancel: array{identifier: string}, event_name: string, type: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\V2\Billing\MeterEventAdjustment
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v2/billing/meter_event_adjustments', $params, $opts);
    }
}
