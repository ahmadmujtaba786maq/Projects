<?php

namespace AmeliaBooking\Application\Services\CustomField;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\ForbiddenFileUploadException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldEventRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldOptionRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldServiceRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class CustomFieldApplicationService
 *
 * @package AmeliaBooking\Application\Services\CustomField
 */
class CustomFieldApplicationService extends AbstractCustomFieldApplicationService
{
    /**
     * @param CustomField $customField
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function delete($customField)
    {
        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        /** @var CustomFieldServiceRepository $customFieldServiceRepository */
        $customFieldServiceRepository = $this->container->get('domain.customFieldService.repository');

        /** @var CustomFieldEventRepository $customFieldEventRepository */
        $customFieldEventRepository = $this->container->get('domain.customFieldEvent.repository');

        /** @var CustomFieldOptionRepository $customFieldOptionRepository */
        $customFieldOptionRepository = $this->container->get('domain.customFieldOption.repository');

        return
            $customFieldServiceRepository->deleteByEntityId($customField->getId()->getValue(), 'customFieldId') &&
            $customFieldEventRepository->deleteByEntityId($customField->getId()->getValue(), 'customFieldId') &&
            $customFieldOptionRepository->deleteByEntityId($customField->getId()->getValue(), 'customFieldId') &&
            $customFieldRepository->delete($customField->getId()->getValue());
    }

    /**
     * @param array $customFields
     *
     * @return array
     */
    public function processCustomFields(&$customFields)
    {
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $allowedUploadedFileExtensions = !empty($settingsDS->getSetting('general', 'customFieldsAllowedExtensions')) ?
            $settingsDS->getSetting('general', 'customFieldsAllowedExtensions') : self::$allowedUploadedFileExtensions;

        $uploadedFilesInfo = [];

        foreach ($customFields as $customFieldId => $customField) {
            if ($customField['type'] === 'file' && isset($customField['value'])) {
                foreach ((array)$customField['value'] as $index => $data) {
                    if (isset($_FILES['files']['tmp_name'][$customFieldId][$index])) {
                        $fileExtension = pathinfo(
                            $_FILES['files']['name'][$customFieldId][$index],
                            PATHINFO_EXTENSION
                        );

                        if (!array_key_exists('.' . strtolower($fileExtension), $allowedUploadedFileExtensions)) {
                            continue;
                        }

                        $token = new Token();

                        $fileName = $token->getValue() . '.' . $fileExtension;

                        $customFields[$customFieldId]['value'][$index]['fileName'] = $fileName;

                        $uploadedFilesInfo[$customFieldId]['value'][$index] = [
                            'tmpName'  => $_FILES['files']['tmp_name'][$customFieldId][$index],
                            'fileName' => $fileName
                        ];
                    }
                }
            }

            if (
                !array_key_exists('value', $customFields[$customFieldId]) &&
                $customFields[$customFieldId]['type'] === 'checkbox'
            ) {
                $customFields[$customFieldId]['value'] = [];
            }
        }

        return $uploadedFilesInfo;
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
     * @throws ForbiddenFileUploadException
     * @throws ContainerException
     */
    public function saveUploadedFiles($bookingId, $uploadedCustomFieldFilesNames, $folder, $copy)
    {
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $allowedUploadedFileExtensions = !empty($settingsDS->getSetting('general', 'customFieldsAllowedExtensions')) ?
            $settingsDS->getSetting('general', 'customFieldsAllowedExtensions') : self::$allowedUploadedFileExtensions;

        $uploadPath = $this->getUploadsPath() . $folder;

        do_action('amelia_before_cf_file_uploaded', $bookingId, $uploadPath, $uploadedCustomFieldFilesNames);

        if ($uploadedCustomFieldFilesNames) {
            !is_dir($uploadPath) && !mkdir($uploadPath, 0755, true) && !is_dir($uploadPath);

            if (!is_writable($uploadPath) || !is_dir($uploadPath)) {
                throw new ForbiddenFileUploadException('Error While Uploading File');
            }

            if (!file_exists("$uploadPath/index.html")) {
                file_put_contents("$uploadPath/index.html", '');
            }
        }

        foreach ($uploadedCustomFieldFilesNames ?: [] as $customFieldId => $customField) {
            foreach ((array)$uploadedCustomFieldFilesNames[$customFieldId]['value'] as $index => $data) {
                $fileExtension = pathinfo($data['fileName'], PATHINFO_EXTENSION);

                if (!array_key_exists('.' . strtolower($fileExtension), $allowedUploadedFileExtensions)) {
                    continue;
                }

                if (is_dir($uploadPath) && is_writable($uploadPath)) {
                    if ($copy) {
                        copy($data['tmpName'], "{$uploadPath}/{$bookingId}_{$data['fileName']}");
                    } else {
                        rename($data['tmpName'], "{$uploadPath}/{$bookingId}_{$data['fileName']}");
                    }

                    $uploadedCustomFieldFilesNames[$customFieldId]['value'][$index]['tmpName'] =
                        "{$uploadPath}/{$bookingId}_{$data['fileName']}";
                }
            }
        }

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
        $newBookingIds = [];

        /** @var CustomerBooking $booking */
        foreach ($bookings->getItems() as $booking) {
            $newBookingIds[] = $booking->getId()->getValue();
        }

        $deletedBookingIds = array_diff($oldBookings->keys(), $newBookingIds);

        /** @var CustomerBooking $oldBooking */
        foreach ($oldBookings->getItems() as $bookingId => $oldBooking) {
            if (in_array($bookingId, $deletedBookingIds, true) && $oldBooking->getCustomFields()) {
                $oldBookingCustomFields = json_decode($oldBooking->getCustomFields()->getValue(), true);

                foreach ((array)$oldBookingCustomFields as $customField) {
                    if (
                        $customField && array_key_exists('value', $customField) &&
                        array_key_exists('type', $customField) && $customField['type'] === 'file'
                    ) {
                        foreach ((array)$customField['value'] as $file) {
                            if (is_array($file) && array_key_exists('fileName', $file)) {
                                if (file_exists($this->getUploadsPath() . $bookingId . '_' . $file['fileName'])) {
                                    unlink($this->getUploadsPath() . $bookingId . '_' . $file['fileName']);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return string
     *
     * @throws ContainerException
     */
    public function getUploadsPath()
    {
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $path = $settingsDS->getSetting('general', 'customFieldsUploadsPath');

        if (trim($path) && substr($path, -1) !== '/') {
            return $path . '/';
        }

        return trim($path) ?: AMELIA_UPLOADS_FILES_PATH;
    }

    /**
     * @param Appointment|Event $entity
     *
     * @return string|null
     *
     */
    public function getCalendarEventLocation($entity)
    {
        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        if ($entity->getBookings()) {
            foreach ($entity->getBookings()->toArray() as $booking) {
                $customFields = !empty($booking['customFields']) ? json_decode($booking['customFields'], true) : [];
                foreach ($customFields as $customFieldId => $customField) {
                    if ($customField['type'] === 'address' && !empty($customField['value'])) {
                        /** @var CustomField|null $customFieldObject */
                        $customFieldObject = null;
                        try {
                            $customFieldObject = $customFieldRepository->getById($customFieldId);
                        } catch (NotFoundException $e) {
                        } catch (QueryExecutionException $e) {
                            continue;
                        }
                        if ($customFieldObject && $customFieldObject->getUseAsLocation() && $customFieldObject->getUseAsLocation()->getValue()) {
                            return $customField['value'];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return Collection
     *
     * @throws QueryExecutionException
     */
    public function getAll()
    {
        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        return $customFieldRepository->getAll();
    }
}
