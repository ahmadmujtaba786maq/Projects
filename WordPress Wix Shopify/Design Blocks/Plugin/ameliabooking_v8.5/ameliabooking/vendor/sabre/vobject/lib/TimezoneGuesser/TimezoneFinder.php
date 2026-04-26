<?php

namespace AmeliaSabre\VObject\TimezoneGuesser;

use DateTimeZone;

interface TimezoneFinder
{
    public function find(string $tzid, bool $failIfUncertain = false): ?DateTimeZone;
}
