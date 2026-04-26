<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Events;

/**
 * @property \AmeliaStripe\EventData\V1BillingMeterNoMeterFoundEventData $data data associated with the event
 */
class V1BillingMeterNoMeterFoundEvent extends \AmeliaStripe\V2\Event
{
    const LOOKUP_TYPE = 'v1.billing.meter.no_meter_found';

    public static function constructFrom($values, $opts = null, $apiMode = 'v2')
    {
        $evt = parent::constructFrom($values, $opts, $apiMode);
        if (null !== $evt->data) {
            $evt->data = \AmeliaStripe\EventData\V1BillingMeterNoMeterFoundEventData::constructFrom($evt->data, $opts, $apiMode);
        }

        return $evt;
    }
}
