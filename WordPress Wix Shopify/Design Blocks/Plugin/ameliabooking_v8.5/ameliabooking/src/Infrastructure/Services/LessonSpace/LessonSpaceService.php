<?php

namespace AmeliaBooking\Infrastructure\Services\LessonSpace;

use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventPeriodsRepository;
use Interop\Container\Exception\ContainerException;

class LessonSpaceService extends AbstractLessonSpaceService
{
    /**
     * @param Appointment|Event $appointment
     * @param int $entity
     * @param Collection $periods
     *
     * @return void
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function handle($appointment, $entity, $periods = null)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get("domain.booking.appointment.repository");

        /** @var EventPeriodsRepository $eventPeriodsRepository */
        $eventPeriodsRepository = $this->container->get('domain.booking.event.period.repository');

        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get('application.placeholder.' . $entity . '.service');

        $lessonSpaceApiKey  = $this->settingsService->getSetting('lessonSpace', 'apiKey');
        $lessonSpaceEnabled = $this->settingsService->getSetting('lessonSpace', 'enabled');

        $enabledForEntity = $this->settingsService
            ->getEntitySettings($entity === Entities::APPOINTMENT ? $appointment->getService()->getSettings() : $appointment->getSettings())
            ->getLessonSpaceSettings()
            ->getEnabled();

        if (!$lessonSpaceEnabled || empty($lessonSpaceApiKey) || !$enabledForEntity) {
            return;
        }

        $requestUrl = 'https://api.thelessonspace.com/v2/spaces/launch/';

        $placeholderData = $placeholderService->getPlaceholdersData($appointment->toArray());

        if ($entity === Entities::APPOINTMENT) {
            $lessonSpaceName = $this->settingsService->getSetting('lessonSpace', 'spaceNameAppointments');
            $lessonSpaceName = $placeholderService->applyPlaceholders($lessonSpaceName, $placeholderData);

            $createForPending  = $this->settingsService->getSetting('lessonSpace', 'pendingAppointments');
            $shouldCreateSpace = $appointment->getStatus()->getValue() === BookingStatus::APPROVED ||
                (
                    $appointment->getStatus()->getValue() === BookingStatus::PENDING &&
                    $createForPending
                );

            $lastBookingCustomerId = null;

            /** @var CustomerBooking $customerBooking */
            foreach ($appointment->getBookings()->getItems() as $customerBooking) {
                if ($customerBooking->isLastBooking() && $customerBooking->isLastBooking()->getValue()) {
                    $lastBookingCustomerId = $customerBooking->getCustomerId()->getValue();
                }
            }

            if ($shouldCreateSpace && !$appointment->getLessonSpace() && $lastBookingCustomerId) {
                $previousAppointment = $appointmentRepository->getFiltered(
                    [
                    'services' => [$appointment->getServiceId()->getValue()],
                    'providerId' => $appointment->getProvider()->getId()->getValue(),
                    'customerId' => $lastBookingCustomerId,
                    'skipServices' => true,
                    'skipProviders' => true,
                    'skipCustomers' => true,
                    'skipPayments' => true,
                    'skipExtras' => true,
                    'skipCoupons' => true,
                    ]
                );
                $lessonSpaceLinks    = array_filter(
                    array_column(
                        array_map(
                            function ($item) {
                                return $item->toArray();
                            },
                            $previousAppointment->getItems()
                        ),
                        'lessonSpace'
                    )
                );
                if (count($lessonSpaceLinks)) {
                    $appointment->setLessonSpace(end($lessonSpaceLinks));
                    $appointmentRepository->updateFieldById(
                        $appointment->getId()->getValue(),
                        $appointment->getLessonSpace(),
                        'lessonSpace'
                    );
                }
            }

            if ($shouldCreateSpace && !$appointment->getLessonSpace()) {
                $resultArray = $this->execute($lessonSpaceApiKey, ['id' => $appointment->getId()->getValue(), 'name' => $lessonSpaceName], $requestUrl, 'POST');
                if (isset($resultArray['client_url'])) {
                    $clientUrl = $this->getInviteUrl($resultArray);
                    $appointment->setLessonSpace($clientUrl);

                    $appointmentRepository->updateFieldById(
                        $appointment->getId()->getValue(),
                        $clientUrl,
                        'lessonSpace'
                    );
                }
            }
        } elseif ($entity === Entities::EVENT) {
            $lessonSpaceName = $this->settingsService->getSetting('lessonSpace', 'spaceNameEvents');
            $lessonSpaceName = $placeholderService->applyPlaceholders($lessonSpaceName, $placeholderData);

            $eventPeriodsRepository->beginTransaction();

            /** @var EventPeriod $period */
            foreach ($periods->getItems() as $period) {
                if ($period->getLessonSpace()) {
                    continue;
                }
                $resultArray = $this->execute($lessonSpaceApiKey, ['id' => $period->getId()->getValue(), 'name' => $lessonSpaceName], $requestUrl, 'POST');
                if (isset($resultArray['client_url'])) {
                    $clientUrl = $this->getInviteUrl($resultArray);
                    $period->setLessonSpace($clientUrl);

                    $eventPeriodsRepository->updateFieldById(
                        $period->getId()->getValue(),
                        $clientUrl,
                        'lessonSpace'
                    );
                }
            }
            $eventPeriodsRepository->commit();
            $appointment->setPeriods($periods);
        }
    }

    /**
     * @param $apiKey
     *
     * @return array
     *
     */
    public function getCompanyId($apiKey)
    {
        $requestUrl = 'https://api.thelessonspace.com/v2/my-organisation/';
        return $this->execute($apiKey, [], $requestUrl, 'GET');
    }

    /**
     * @param $apiKey
     * @param $companyId
     * @param $searchTerm
     *
     * @return array
     */
    public function getAllSpaces($apiKey, $companyId, $searchTerm = null)
    {
        if ($companyId) {
            $requestUrl  =
                'https://api.thelessonspace.com/v2/organisations/' .
                $companyId . '/spaces/?sort_by=new-old' .
                ($searchTerm ? '&search=' . $searchTerm : '');
            $resultArray = $this->execute($apiKey, [], $requestUrl, 'GET');
            $allSpaces   = !empty($resultArray['results']) ? $resultArray['results'] : [];

            $i = 0;
            while (!empty($resultArray['next']) && ($searchTerm || $i < 1)) {
                $resultArray = $this->execute($apiKey, [], $resultArray['next'], 'GET');
                $allSpaces   = array_merge($allSpaces, !empty($resultArray['results']) ? $resultArray['results'] : []);
                $i++;
            }

            return $allSpaces;
        }
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
        $requestUrl = 'https://api.thelessonspace.com/v2/organisations/' . $companyId . '/spaces/' . $spaceId . '/users/';
        return $this->execute($apiKey, [], $requestUrl, 'GET');
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
        $requestUrl = 'https://api.thelessonspace.com/v2/organisations/' . $companyId . '/spaces/' . $spaceId;
        return $this->execute($apiKey, [], $requestUrl, 'GET');
    }

    /**
     * @param $apiKey
     * @param $companyId
     *
     * @return array
     */
    public function getAllTeachers($apiKey, $companyId)
    {
        $requestUrl  = "https://api.thelessonspace.com/v2/organisations/' . $companyId .'/spaces/?role=teacher";
        $resultArray = $this->execute($apiKey, [], $requestUrl, 'GET');
        return !empty($resultArray) ? $resultArray['results'] : [];
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
        $ch = curl_init($requestUrl);

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Authorization: Organisation ' . $lessonSpaceApiKey,
                'Content-Type: application/json'
            ]
        );

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_FORCE_OBJECT));
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if ($result === false) {
            return ['message' => curl_error($ch)];
        }

        curl_close($ch);

        return json_decode($result, true);
    }

    /**
     * @param $resultArray
     *
     * @return string
     */
    private function getInviteUrl($resultArray)
    {
        $inviteUrl = '';
        if (preg_match("/inviteUrl=([^&]*)/", $resultArray['client_url'], $match)) {
            $inviteUrl = rawurldecode($match[1]);
        }

        return $inviteUrl ?: $resultArray['client_url'];
    }
}
