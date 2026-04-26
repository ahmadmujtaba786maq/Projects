<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Zoom;

use AmeliaBooking\Domain\Services\Settings\SettingsService;

/**
 * Class AbstractZoomService
 *
 * @package AmeliaBooking\Infrastructure\Services\Zoom
 */
abstract class AbstractZoomService
{
    /**
     * @var SettingsService $settingsService
     */
    protected $settingsService;

    /**
     * @param string     $requestUrl
     * @param array|null $data
     * @param string     $method
     *
     * @return array
     */
    abstract public function execute($requestUrl, $data, $method);

    /**
     *
     * @return array
     */
    abstract public function getUsers();

    /**
     * @param int   $userId
     * @param array $data
     *
     * @return mixed
     */
    abstract public function createMeeting($userId, $data);

    /**
     * @param int   $meetingId
     * @param array $data
     *
     * @return mixed
     */
    abstract public function updateMeeting($meetingId, $data);

    /**
     * @param int   $meetingId
     *
     * @return mixed
     */
    abstract public function deleteMeeting($meetingId);

    /**
     * @param int $meetingId
     *
     * @return mixed
     */
    abstract public function getMeeting($meetingId);
}
