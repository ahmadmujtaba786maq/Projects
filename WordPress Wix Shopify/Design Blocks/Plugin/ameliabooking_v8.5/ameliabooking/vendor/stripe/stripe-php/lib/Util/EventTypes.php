<?php

namespace AmeliaStripe\Util;

class EventTypes
{
    const thinEventMapping = [
        // The beginning of the section generated from our OpenAPI spec
        \AmeliaStripe\Events\V1BillingMeterErrorReportTriggeredEvent::LOOKUP_TYPE => \AmeliaStripe\Events\V1BillingMeterErrorReportTriggeredEvent::class,
        \AmeliaStripe\Events\V1BillingMeterNoMeterFoundEvent::LOOKUP_TYPE => \AmeliaStripe\Events\V1BillingMeterNoMeterFoundEvent::class,
        \AmeliaStripe\Events\V2CoreEventDestinationPingEvent::LOOKUP_TYPE => \AmeliaStripe\Events\V2CoreEventDestinationPingEvent::class,
        // The end of the section generated from our OpenAPI spec
    ];
}
