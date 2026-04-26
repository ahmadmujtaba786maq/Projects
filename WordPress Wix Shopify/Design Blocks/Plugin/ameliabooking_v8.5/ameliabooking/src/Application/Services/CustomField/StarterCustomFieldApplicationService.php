<?php

namespace AmeliaBooking\Application\Services\CustomField;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class StarterCustomFieldApplicationService
 *
 * @package AmeliaBooking\Application\Services\CustomField
 */
class StarterCustomFieldApplicationService extends AbstractCustomFieldApplicationService
{
    /**
     * @param CustomField $customField
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     */
    public function delete($customField)
    {
        return true;
    }

    /**
     * @param array $customFields
     *
     * @return array
     */
    public function processCustomFields(&$customFields)
    {
        return [];
    }

    /**
     * @param int    $bookingId
     * @param array  $uploadedCustomFieldFilesNames
     * @param string $folder
     * @param string $copy
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     */
    public function saveUploadedFiles($bookingId, $uploadedCustomFieldFilesNames, $folder, $copy)
    {
        return $uploadedCustomFieldFilesNames;
    }

    /**
     * @param Collection $bookings
     * @param Collection $oldBookings
     *
     * @return void
     * @throws ContainerException
     */
    public function deleteUploadedFilesForDeletedBookings($bookings, $oldBookings)
    {
    }

    /**
     * @return string
     *
     * @throws ContainerException
     */
    public function getUploadsPath()
    {
        return AMELIA_UPLOADS_FILES_PATH;
    }

    /**
     * @param Appointment|Event $entity
     *
     * @return string|null
     *
     */
    public function getCalendarEventLocation($entity)
    {
        return null;
    }

    /**
     * @return Collection
     */
    public function getAll()
    {
        return new Collection();
    }
}
