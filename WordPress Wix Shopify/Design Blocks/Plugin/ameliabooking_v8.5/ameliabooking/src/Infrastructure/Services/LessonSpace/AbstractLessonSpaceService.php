<?php

namespace AmeliaBooking\Infrastructure\Services\LessonSpace;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;

abstract class AbstractLessonSpaceService
{
    /**
     * @var SettingsService $settingsService
     */
    protected $settingsService;

    /** @var Container $container */
    protected $container;

    /**
     * AbstractLessonSpaceService constructor.
     *
     * @param Container $container
     * @param SettingsService $settingsService
     */
    public function __construct(Container $container, SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
        $this->container       = $container;
    }

    /**
     * @param Appointment|Event $appointment
     * @param int $entity
     * @param Collection $periods
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws ContainerException
     */
    abstract public function handle($appointment, $entity, $periods = null);

    /**
     * @param $apiKey
     *
     * @return array
     *
     */
    abstract public function getCompanyId($apiKey);

    /**
     * @param $apiKey
     * @param $companyId
     * @param null $searchTerm
     *
     * @return array
     */
    abstract public function getAllSpaces($apiKey, $companyId, $searchTerm = null);

    /**
     * @param $apiKey
     * @param $companyId
     * @param $spaceId
     *
     * @return array
     */
    abstract public function getSpaceUsers($apiKey, $companyId, $spaceId);

    /**
     * @param $apiKey
     * @param $companyId
     * @param $spaceId
     *
     * @return array
     */
    abstract public function getSpace($apiKey, $companyId, $spaceId);

    /**
     * @param $apiKey
     * @param $companyId
     *
     * @return array
     */
    abstract public function getAllTeachers($apiKey, $companyId);

    /**
     * @param $lessonSpaceApiKey
     * @param $data
     * @param $requestUrl
     * @param $method
     *
     * @return array
     */
    abstract public function execute($lessonSpaceApiKey, $data, $requestUrl, $method);
}
