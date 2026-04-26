<?php

namespace AmeliaBooking\Application\Services\CustomField;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\ForbiddenFileUploadException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AbstractCustomFieldApplicationService
 *
 * @package AmeliaBooking\Application\Services\CustomField
 */
abstract class AbstractCustomFieldApplicationService
{
    public static $allowedUploadedFileExtensions = [
        '.jpg'  => 'image/jpeg',
        '.jpeg' => 'image/jpeg',
        '.png'  => 'image/png',

        '.mp3'  => 'audio/mpeg',
        '.mpeg' => 'video/mpeg',
        '.mp4'  => 'video/mp4',

        '.txt'  => 'text/plain',
        '.csv'  => 'text/plain',
        '.xls'  => 'application/vnd.ms-excel',
        '.pdf'  => 'application/pdf',
        '.doc'  => 'application/msword',
        '.docx' => 'application/msword',
    ];

    protected $container;

    /**
     * CustomFieldApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param CustomField $customField
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    abstract public function delete($customField);

    /**
     * @param array $customFields
     *
     * @return array
     */
    abstract public function processCustomFields(&$customFields);

    /**
     * @param int    $bookingId
     * @param array  $uploadedCustomFieldFilesNames
     * @param string $folder
     * @param string $copy
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException
     * @throws ForbiddenFileUploadException
     * @throws ContainerException
     */
    abstract public function saveUploadedFiles($bookingId, $uploadedCustomFieldFilesNames, $folder, $copy);

    /**
     * @param Collection $bookings
     * @param Collection $oldBookings
     *
     * @return void
     * @throws ContainerException
     */
    abstract public function deleteUploadedFilesForDeletedBookings($bookings, $oldBookings);

    /**
     * @return string
     *
     * @throws ContainerException
     */
    abstract public function getUploadsPath();

    /**
     * @param Appointment|Event $entity
     *
     * @return string
     */
    abstract public function getCalendarEventLocation($entity);

    /**
     * @return Collection
     *
     * @throws QueryExecutionException
     */
    abstract public function getAll();
}
