<?php

namespace AmeliaBooking\Infrastructure\Services\LessonSpace;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;

class LiteLessonSpaceService extends AbstractLessonSpaceService
{
    /**
     * @param Appointment|Event $appointment
     * @param int $entity
     * @param Collection $periods
     * @param array $booking
     *
     * @return void
     */
    public function handle($appointment, $entity, $periods = null, $booking = null)
    {
    }

    /**
     * @param $apiKey
     *
     * @return array
     *
     */
    public function getCompanyId($apiKey)
    {
        return [];
    }

    /**
     * @param $apiKey
     * @param $companyId
     * @param null $searchTerm
     *
     * @return array
     */
    public function getAllSpaces($apiKey, $companyId, $searchTerm = null)
    {
        return [];
    }

    /**
     * @param $apiKey
     * @param $companyId
     * @param $spaceId
     *
     * @return array
     */
    public function getSpaceUsers($apiKey, $companyId, $spaceId)
    {
        return [];
    }

    /**
     * @param $apiKey
     * @param $companyId
     * @param $spaceId
     *
     * @return array
     */
    public function getSpace($apiKey, $companyId, $spaceId)
    {
        return [];
    }

    /**
     * @param $apiKey
     * @param $companyId
     *
     * @return array
     */
    public function getAllTeachers($apiKey, $companyId)
    {
        return [];
    }

    /**
     * @param $lessonSpaceApiKey
     * @param $data
     * @param $requestUrl
     * @param $method
     *
     * @return array
     */
    public function execute($lessonSpaceApiKey, $data, $requestUrl, $method)
    {
        return [];
    }
}
