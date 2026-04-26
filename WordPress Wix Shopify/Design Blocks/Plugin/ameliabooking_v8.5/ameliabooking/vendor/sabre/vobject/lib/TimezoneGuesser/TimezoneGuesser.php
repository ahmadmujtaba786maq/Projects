<?php

namespace AmeliaSabre\VObject\TimezoneGuesser;

use DateTimeZone;
use AmeliaSabre\VObject\Component\VTimeZone;

interface TimezoneGuesser
{
    public function guess(VTimeZone $vtimezone, bool $failIfUncertain = false): ?DateTimeZone;
}
