<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\Billing;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class MeterEventService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Creates a billing meter event.
     *
     * @param null|array{event_name: string, expand?: string[], identifier?: string, payload: array<string, string>, timestamp?: int} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Billing\MeterEvent
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/billing/meter_events', $params, $opts);
    }
}
