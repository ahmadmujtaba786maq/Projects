<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\V2\Billing;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class MeterEventSessionService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Creates a meter event session to send usage on the high-throughput meter event
     * stream. Authentication tokens are only valid for 15 minutes, so you will need to
     * create a new meter event session when your token expires.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\V2\Billing\MeterEventSession
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v2/billing/meter_event_session', $params, $opts);
    }
}
