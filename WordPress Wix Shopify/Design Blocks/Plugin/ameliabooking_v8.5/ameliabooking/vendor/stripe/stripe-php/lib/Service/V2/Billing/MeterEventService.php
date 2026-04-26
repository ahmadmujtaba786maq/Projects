<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\V2\Billing;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class MeterEventService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Creates a meter event. Events are validated synchronously, but are processed
     * asynchronously. Supports up to 1,000 events per second in livemode. For higher
     * rate-limits, please use meter event streams instead.
     *
     * @param null|array{event_name: string, identifier?: string, payload: array<string, string>, timestamp?: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\V2\Billing\MeterEvent
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v2/billing/meter_events', $params, $opts);
    }
}
