<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\V2\Core;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class EventService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * List events, going back up to 30 days.
     *
     * @param null|array{limit?: int, object_id: string} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\V2\Collection<\AmeliaStripe\V2\Event>
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v2/core/events', $params, $opts);
    }

    /**
     * Retrieves the details of an event.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\V2\Event
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v2/core/events/%s', $id), $params, $opts);
    }
}
