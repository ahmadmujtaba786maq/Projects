<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Service\TestHelpers;

/**
 * @phpstan-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \AmeliaStripe\Util\RequestOptions
 */
class RefundService extends \AmeliaStripe\Service\AbstractService
{
    /**
     * Expire a refund with a status of <code>requires_action</code>.
     *
     * @param string $id
     * @param null|array{expand?: string[]} $params
     * @param null|RequestOptionsArray|\AmeliaStripe\Util\RequestOptions $opts
     *
     * @return \AmeliaStripe\Refund
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function expire($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/refunds/%s/expire', $id), $params, $opts);
    }
}
