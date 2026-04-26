<?php

defined('ABSPATH') or die('No script kiddies please!');

use AmeliaBooking\Infrastructure\Licence;

// @codingStandardsIgnoreStart
$entries['command.bus'] = function ($c) {
    return League\Tactician\Setup\QuickStart::create(
        Licence\Licence::getCommands($c)
    );
};
// @codingStandardsIgnoreEnd
