<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Zoom;

use AmeliaBooking\Domain\Services\Settings\SettingsService;

/**
 * Class StarterZoomService
 *
 * @package AmeliaBooking\Infrastructure\Services\Zoom
 */
class StarterZoomService extends AbstractZoomService
{
    /**
     * StarterZoomService constructor.
     *
     * @param SettingsService $settingsService
     */
    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @param string     $requestUrl
     * @param array|null $data
     * @param string     $method
     *
     * @return array
     */
    public function execute($requestUrl, $data, $method)
    {
        return [];
    }

    /**
     *
     * @return array
     */
    public function getUsers()
    {
        return [];
    }

    /**
     * @param int   $userId
     * @param array $data
     *
     * @return array
     */
    public function createMeeting($userId, $data)
    {
        return [];
    }

    /**
     * @param int   $meetingId
     * @param array $data
     *
     * @return array
     */
    public function updateMeeting($meetingId, $data)
    {
        return [];
    }

    /**
     * @param int   $meetingId
     *
     * @return array
     */
    public function deleteMeeting($meetingId)
    {
        return [];
    }

    /**
     * @param int $meetingId
     *
     * @return array
     */
    public function getMeeting($meetingId)
    {
        return [];
    }
}
