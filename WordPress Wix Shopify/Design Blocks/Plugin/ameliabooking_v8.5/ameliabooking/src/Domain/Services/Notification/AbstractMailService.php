<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Services\Notification;

/**
 * Class AbstractMailService
 *
 * @package AmeliaBooking\Domain\Services\Notification
 */
class AbstractMailService
{
    /** @var string */
    protected $from;

    /** @var string */
    protected $fromName;

    /** @var string */
    protected $replyTo;

    /**
     * AbstractMailService constructor.
     *
     * @param $from
     * @param $fromName
     */
    public function __construct($from, $fromName, $replyTo)
    {
        $this->from     = $from;
        $this->fromName = $fromName;
        $this->replyTo  = $replyTo;
    }
}
