<?php

namespace AmeliaBooking\Domain\Factory\Zoom;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Zoom\ZoomMeeting;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Url;

/**
 * Class ZoomFactory
 *
 * @package AmeliaBooking\Domain\Factory\Zoom
 */
class ZoomFactory
{
    /**
     * @param $data
     *
     * @return ZoomMeeting
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $zoomMeeting = new ZoomMeeting();

        if (isset($data['id'])) {
            $zoomMeeting->setId(new Id($data['id']));
        }

        if (isset($data['joinUrl'])) {
            $zoomMeeting->setJoinUrl(new Url($data['joinUrl']));
        }

        if (isset($data['startUrl'])) {
            $zoomMeeting->setStartUrl(new Url($data['startUrl']));
        }

        return $zoomMeeting;
    }
}
