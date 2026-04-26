<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Entity\Notification\NotificationLog;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Repository\User\UserRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\NotificationStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationLogRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class WhatsAppNotificationService
 *
 * @package AmeliaBooking\Application\Services\Notification
 */
class WhatsAppNotificationService extends AbstractWhatsAppNotificationService
{
    /**
     * @return bool
     */
    public function checkRequiredFields()
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $notificationsSettings = $settingsService->getCategorySettings('notifications');
        return !empty($notificationsSettings['whatsAppEnabled']) && !empty($notificationsSettings['whatsAppPhoneID']) &&
            !empty($notificationsSettings['whatsAppAccessToken']) && !empty($notificationsSettings['whatsAppBusinessID']);
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        /** @var WhatsAppService $whatsAppService */
        $whatsAppService = $this->container->get('application.whatsApp.service');

        $whatsAppTemplatesLang = [];

        $whatsAppTemplates = [];

        $templates = [];
        do {
            $templates = $whatsAppService->getTemplates(null, !empty($templates['paging']['next']) ? $templates['paging']['next'] : null);
            if (empty($templates['error']) && !empty($templates['data'])) {
                $whatsAppTemplates     = array_merge($whatsAppTemplates, $templates['data']);
                $whatsAppTemplatesLang = array_merge($whatsAppTemplatesLang, $this->addTemplates($templates));
            }
        } while (!empty($templates['paging']['next']));

        return [$whatsAppTemplates, $whatsAppTemplatesLang];
    }

    /**
     * @param array $whatsAppTemplates
     *
     * @return array
     */
    private function addTemplates($whatsAppTemplates)
    {
        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $defaultLanguage = $settingsService->getSetting('notifications', 'whatsAppLanguage');

        $usedLanguages     = $settingsService->getSetting('general', 'usedLanguages');
        $defaultWpLanguage = AMELIA_LOCALE;
        if (!in_array($defaultWpLanguage, $usedLanguages)) {
            $usedLanguages[] = $defaultWpLanguage;
        }

        $whatsAppTemplatesLang = [];
        foreach ($whatsAppTemplates['data'] as $item) {
            $similarLanguages = $helperService->getLocaleLanguage($usedLanguages, $item['language']);
            if (!$defaultLanguage || $item['language'] === $defaultLanguage || in_array($defaultLanguage, $similarLanguages)) {
                $whatsAppTemplatesLang[] = $item;
            }
        }

        return $whatsAppTemplatesLang;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array        $appointmentArray
     * @param Notification $notification
     * @param bool         $logNotification
     * @param int|null     $bookingKey
     *
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function sendNotification(
        $appointmentArray,
        $notification,
        $logNotification,
        $bookingKey = null,
        $allBookings = null,
        $invoice = []
    ) {
        /** @var \AmeliaBooking\Application\Services\Settings\SettingsService $settingsAS */
        $settingsAS = $this->container->get('application.settings.service');
        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get("application.placeholder.{$appointmentArray['type']}.service");
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $defaultLanguage = $settingsDS->getSetting('notifications', 'whatsAppLanguage');

        if (!$notification->getWhatsAppTemplate()) {
            return;
        }

        $data = $placeholderService->getPlaceholdersData(
            $appointmentArray,
            $bookingKey,
            'whatsapp',
            null,
            null,
            false,
            $notification->getName()->getValue()
        );

        $isCustomerPackage = isset($appointmentArray['isForCustomer']) && $appointmentArray['isForCustomer'];

        if ($appointmentArray['type'] === Entities::PACKAGE) {
            if (!empty($appointmentArray['recurring'][0]['booking']['info']) && $isCustomerPackage) {
                $info = $appointmentArray['recurring'][0]['booking']['info'];

                $infoArray = json_decode($info, true);

                if (!empty($infoArray['phone'])) {
                    $appointmentArray['customer']['phone'] = $infoArray['phone'];
                }
            } else {
                $info = $isCustomerPackage ? json_encode($appointmentArray['customer']) : null;
            }
        } else {
            $info = $bookingKey !== null ? $appointmentArray['bookings'][$bookingKey]['info'] : null;
        }

        $template   = $notification->getWhatsAppTemplate();
        $components = $this->getComponentData($notification, $data);

        $language = $info && json_decode($info, true) ? json_decode($info, true)['locale'] : null;
        $language = $language ?: $defaultLanguage;

        $users = $this->getUsersInfo(
            $notification->getSendTo()->getValue(),
            $appointmentArray,
            $bookingKey,
            $data
        );

        foreach ($users as $user) {
            if ($user['phone']) {
                try {
                    $this->saveAndSend(
                        $notification,
                        $user,
                        $appointmentArray,
                        $logNotification,
                        $user['phone'],
                        $template,
                        $components,
                        $language
                    );

                    $additionalPhoneNumbers = $settingsAS->getBccSms();

                    foreach ($additionalPhoneNumbers as $phoneNumber) {
                        $this->saveAndSend(
                            $notification,
                            null,
                            $appointmentArray,
                            $logNotification,
                            $phoneNumber,
                            $template,
                            $components,
                            $language
                        );
                    }
                } catch (QueryExecutionException $e) {
                } catch (ContainerException $e) {
                }
            }
        }
    }

    /**
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendUndeliveredNotifications()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');
        /** @var NotificationLogRepository $notificationLogRepository */
        $notificationLogRepository = $this->container->get('domain.notificationLog.repository');

        /** @var Collection $undeliveredNotifications */
        $undeliveredNotifications = $notificationLogRepository->getUndeliveredNotifications('whatsapp');

        /** @var NotificationLog $undeliveredNotification */
        foreach ($undeliveredNotifications->getItems() as $undeliveredNotification) {
            try {
                /** @var AbstractUser $user */
                $user = $userRepository->getById($undeliveredNotification->getUserId()->getValue());
                $data = json_decode($undeliveredNotification->getData()->getValue(), true);
                $this->sendAndUpdate(
                    $user->getPhone()->getValue(),
                    $data['template'],
                    $data['components'],
                    $data['language'],
                    $undeliveredNotification->getId()->getValue()
                );
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function sendBirthdayGreetingNotifications()
    {
        /** @var Collection $notifications */
        $notifications = $this->getByNameAndType('customer_birthday_greeting', $this->type);

        if (!$notifications || $notifications->length() === 0) {
            return;
        }

        /** @var Notification $notification */
        $notification = $notifications->getItem($notifications->keys()[0]);

        if (!$notification->getWhatsAppTemplate()) {
            return;
        }

        // Check if notification is enabled and it is time to send notification
        if (
            $notification->getStatus()->getValue() === NotificationStatus::ENABLED &&
            $notification->getTime() &&
            DateTimeService::getNowDateTimeObject() >=
            DateTimeService::getCustomDateTimeObject($notification->getTime()->getValue())
        ) {
            /** @var NotificationLogRepository $notificationLogRepo */
            $notificationLogRepo = $this->container->get('domain.notificationLog.repository');
            /** @var PlaceholderService $placeholderService */
            $placeholderService = $this->container->get('application.placeholder.appointment.service');

            $customers = $notificationLogRepo->getBirthdayCustomers($this->type);

            $companyData = $placeholderService->getCompanyData();

            $customersArray = $customers->toArray();

            foreach ($customersArray as $customerArray) {
                $data = [
                    'customer_email'      => $customerArray['email'],
                    'customer_first_name' => $customerArray['firstName'],
                    'customer_last_name'  => $customerArray['lastName'],
                    'customer_full_name'  => $customerArray['firstName'] . ' ' . $customerArray['lastName'],
                    'customer_phone'      => $customerArray['phone'],
                    'customer_id'         => $customerArray['id'],
                ];

                /** @noinspection AdditionOperationOnArraysInspection */
                $data += $companyData;

                $components = $this->getComponentData($notification, $data);
                if ($data['customer_phone']) {
                    try {
                        $logNotificationId = $notificationLogRepo->add(
                            $notification,
                            $customerArray ? $customerArray['id'] : null,
                            null,
                            null,
                            null,
                            json_encode(
                                [
                                    'template'   => $notification->getWhatsAppTemplate(),
                                    'components' => $components,
                                    'language'   => ''
                                ]
                            )
                        );
                        $this->sendAndUpdate($data['customer_phone'], $notification->getWhatsAppTemplate(), $components, null, $logNotificationId);
                    } catch (QueryExecutionException $e) {
                    }
                }
            }
        }
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Notification $notification
     * @param array $user
     * @param array $appointmentArray
     * @param string $template
     * @param bool $logNotification
     * @param string $sendTo
     *
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    private function saveAndSend($notification, $user, $appointmentArray, $logNotification, $sendTo, $template, $placeholders, $language)
    {
        /** @var NotificationLogRepository $notificationsLogRepository */
        $notificationsLogRepository = $this->container->get('domain.notificationLog.repository');

        if ($user && !empty($appointmentArray['isRetry'])) {
            /** @var Collection $sentNotifications */
            $sentNotifications = $notificationsLogRepository->getSentNotificationsByUserAndEntity(
                $user['id'],
                'whatsapp',
                $appointmentArray['type'],
                $appointmentArray['type'] === Entities::PACKAGE ?
                    $appointmentArray['packageCustomerId'] : $appointmentArray['id']
            );

            if ($sentNotifications->length()) {
                return;
            }
        }

        $logNotificationId = null;

        if ($logNotification) {
            $logNotificationId = $notificationsLogRepository->add(
                $notification,
                $user ? $user['id'] : null,
                $appointmentArray['type'] === Entities::APPOINTMENT ? $appointmentArray['id'] : null,
                $appointmentArray['type'] === Entities::EVENT ? $appointmentArray['id'] : null,
                $appointmentArray['type'] === Entities::PACKAGE ? $appointmentArray['packageCustomerId'] : null,
                json_encode(
                    [
                        'template'   => $template,
                        'components' => $placeholders,
                        'language'   => $language
                    ]
                )
            );
        }

        $this->sendAndUpdate($sendTo, $template, $placeholders, $language, $logNotificationId);
    }


    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param string $sendTo
     * @param string $template
     * @param $placeholders
     * @param $language
     * @param null $logNotificationId
     * @return mixed
     * @throws QueryExecutionException
     */
    private function sendAndUpdate($sendTo, $template, $placeholders, $language = null, $logNotificationId = null)
    {
        /** @var NotificationLogRepository $notificationsLogRepository */
        $notificationsLogRepository = $this->container->get('domain.notificationLog.repository');
        /** @var WhatsAppService $whatsAppService */
        $whatsAppService = $this->container->get('application.whatsApp.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $defaultLanguage = $settingsDS->getSetting('notifications', 'whatsAppLanguage');
        $sendInLanguage  = $language ?: $defaultLanguage;

        $templateLanguage = $this->getSimilarTemplateLanguages($template, $sendInLanguage);

        $apiResponse = $whatsAppService->send(
            $sendTo,
            $template,
            $placeholders,
            $templateLanguage ?: $sendInLanguage
        );

        if (!empty($apiResponse['messages'])) {
            if ($logNotificationId) {
                $notificationsLogRepository->updateFieldById((int)$logNotificationId, 1, 'sent');
            }
        } elseif ($apiResponse['error']) {
            // requested language doesn't exist for this template, try with default language
            if ($apiResponse['error']['code'] === 132001 && !empty($language)) {
                $apiResponse = $whatsAppService->send(
                    $sendTo,
                    $template,
                    $placeholders,
                    $defaultLanguage
                );
                if (!empty($apiResponse['messages'])) {
                    if ($logNotificationId) {
                        $notificationsLogRepository->updateFieldById((int)$logNotificationId, 1, 'sent');
                    }
                }
            }
        }
        return $apiResponse;
    }

    /**
     * @param $sendTo
     * @param Notification $notification
     * @param $dummyData
     * @return mixed
     * @throws QueryExecutionException
     */
    public function sendTestNotification($sendTo, $notification, $dummyData)
    {
        $placeholders = $this->getComponentData($notification, $dummyData);
        return $this->sendAndUpdate($sendTo, $notification->getWhatsAppTemplate(), $placeholders, null, null);
    }


    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param $notification
     * @param $data
     * @return array
     */
    private function getComponentData($notification, $data)
    {
        $placeholdersBody   = $this->getPlaceholdersObject($notification->getContent()->getValue(), $data);
        $placeholdersHeader = $notification->getSubject() ? $this->getPlaceholdersObject($notification->getSubject()->getValue(), $data, true) : null;

        $components = [];
        if ($placeholdersHeader) {
            $components[] = [
                "type" => "header",
                "parameters" => $placeholdersHeader
            ];
        }
        $components[] = [
            "type" => "body",
            "parameters" => $placeholdersBody
        ];
        return $components;
    }

    /**
     * @param $content
     * @param $data
     * @param $isHeader
     * @return array
     */
    private function getPlaceholdersObject($content, $data, $isHeader = false)
    {
        $isLocationHeader = false;
        if (strpos($content, 'location:') !== false) {
            $isLocationHeader = true;
            $content          = str_replace('location:', '', $content);
        }
        $isImageHeader = !$isLocationHeader && $isHeader && !empty($content) && $content[0] !== '%';
        $parameters    = explode('%', $content);
        $parameters    = array_values(
            array_filter(
                $parameters,
                function ($parameter) {
                    return !empty($parameter) && !empty(trim($parameter));
                }
            )
        );
        $placeholders  = [];
        foreach ($parameters as $parameter) {
            $parameter = trim($parameter);
            if (!empty($parameter)) {
                if ($isImageHeader) {
                    $placeholders[] = [
                        'type'  =>  'image',
                        'image' =>  [
                            'link' => $parameter
                        ]
                    ];
                } elseif ($isLocationHeader) {
                    $placeholders[] = [
                        'type'  =>  'location',
                        'location' => [
                            'name' => $data[$parameters[0]],
                            'address' => $data[$parameters[1]],
                            'latitude' => $data[$parameters[2]],
                            'longitude' => $data[$parameters[3]],
                        ]
                    ];

                    break;
                } else {
                    $data[$parameter] = !empty($data[$parameter]) ? $data[$parameter] : ' ';
                    $placeholders[]   = [
                        'type'  =>  'text',
                        'text'  =>  $data[$parameter]
                    ];
                }
            }
        }
        return $placeholders;
    }

    /**
     * @param Customer $customer
     * @param string   $locale
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws \Slim\Exception\ContainerException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     */
    public function sendRecoveryWhatsApp($customer, $locale, $cabinetType)
    {
        /** @var Collection $notifications */
        $notifications = $cabinetType === 'customer' ?
            $this->getByNameAndType('customer_account_recovery', 'whatsapp') :
            $this->getByNameAndType('provider_panel_recovery', 'whatsapp');


        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get('application.placeholder.appointment.service');
        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        $sendTo = $cabinetType === 'customer' ? 'customer' : 'employee';
        foreach ($notifications->getItems() as $notification) {
            if ($notification->getStatus()->getValue() === NotificationStatus::ENABLED && !empty($notification->getWhatsAppTemplate())) {
                $data = [
                    $sendTo . '_email'      => $customer->getEmail() ? $customer->getEmail()->getValue() : '',
                    $sendTo . '_first_name' => $customer->getFirstName()->getValue(),
                    $sendTo . '_last_name'  => $customer->getLastName() ? $customer->getLastName()->getValue() : '',
                    $sendTo . '_full_name'  =>
                        $customer->getFirstName()->getValue() . ' ' .
                        ($customer->getLastName() ? $customer->getLastName()->getValue() : ''),
                    $sendTo . '_phone'      => $customer->getPhone() ? $customer->getPhone()->getValue() : '',
                    $sendTo . '_panel_url'  => $cabinetType === 'customer' ? $helperService->getCustomerCabinetUrl(
                        $customer->getEmail()->getValue(),
                        'email',
                        null,
                        null,
                        $locale
                    ) : $helperService->getProviderCabinetUrl(
                        $customer->getEmail()->getValue(),
                        'email',
                        null,
                        null
                    )
                ];

                /** @noinspection AdditionOperationOnArraysInspection */
                $data += $placeholderService->getCompanyData();

                $components = $this->getComponentData($notification, $data);
                $phone      = $sendTo . '_phone';
                try {
                    $customerDefaultLanguage = $cabinetType === 'customer' && $customer->getTranslations() ?
                        json_decode($customer->getTranslations()->getValue(), true)['defaultLanguage'] :
                        null;

                    $this->sendAndUpdate($data[$phone], $notification->getWhatsAppTemplate(), $components, $customerDefaultLanguage);
                } catch (QueryExecutionException $e) {
                }
            }
        }
    }

    /**
     * @param Provider $provider
     *
     * @param $plainPassword
     * @return void
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendEmployeePanelAccess($provider, $plainPassword)
    {
        /** @var Collection $notifications */
        $notifications = $this->getByNameAndType('provider_panel_access', 'whatsapp');

        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get('application.placeholder.appointment.service');

        foreach ($notifications->getItems() as $notification) {
            if ($notification->getStatus()->getValue() === NotificationStatus::ENABLED && !empty($notification->getWhatsAppTemplate())) {
                $data = [
                    'employee_email'      => $provider['email'],
                    'employee_first_name' => $provider['firstName'],
                    'employee_last_name'  => $provider['lastName'],
                    'employee_full_name'  =>
                        $provider['firstName'] . ' ' . $provider['lastName'],
                    'employee_phone'      => $provider['phone'],
                    'employee_password'   => $plainPassword,
                    'employee_panel_url'  => trim(
                        $this->container->get('domain.settings.service')->getSetting('roles', 'providerCabinet')['pageUrl']
                    )
                ];

                /** @noinspection AdditionOperationOnArraysInspection */
                $data += $placeholderService->getCompanyData();

                $components = $this->getComponentData($notification, $data);
                try {
                    $this->sendAndUpdate($data['employee_phone'], $notification->getWhatsAppTemplate(), $components);
                } catch (QueryExecutionException $e) {
                }
            }
        }
    }

    /**
     * @param string $name
     * @param string $language
     * @return mixed
     */
    private function getSimilarTemplateLanguages($name, $language)
    {
        /** @var WhatsAppService $whatsAppService */
        $whatsAppService = $this->container->get('application.whatsApp.service');

        $templates = $whatsAppService->getTemplates($name);

        $templateLang = null;
        if (!empty($templates['data'])) {
            foreach ($templates['data'] as $template) {
                if ($template['language'] === $language) {
                    $templateLang = $template['language'];
                    break;
                }
            }
            if ($templateLang === null) {
                foreach ($templates['data'] as $template) {
                    $languageBase = explode('_', $language)[0];
                    if (in_array($languageBase, [$template['language'], explode('_', $template['language'])[0]])) {
                        $templateLang = $template['language'];
                        break;
                    }
                }
            }
        }

        return $templateLang;
    }


    /**
     * @param string $to
     *
     * @throws QueryExecutionException
     */
    public function sendMessage($to)
    {
        /** @var WhatsAppService $whatsAppService */
        $whatsAppService = $this->container->get('application.whatsApp.service');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $message = $settingsService->getSetting('notifications', 'whatsAppReplyMsg');

        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get('application.placeholder.appointment.service');

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');

        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        $customers = $customerRepository->getByPhoneNumber($to);

        $companyData = $placeholderService->getCompanyData();

        $data = [
            'customer_email'      => $customers[0]['email'],
            'customer_first_name' => $customers[0]['firstName'],
            'customer_last_name'  => $customers[0]['lastName'],
            'customer_full_name'  => $customers[0]['firstName'] . ' ' . $customers[0]['lastName'],
            'customer_phone'      => $customers[0]['phone'],
            'customer_note'       => $customers[0]['note'],
            'customer_id'         => $customers[0]['id'],
            'customer_panel_url'  => $helperService->getCustomerCabinetUrl(
                $customers[0]['email'],
                'whatsapp',
                null,
                null,
                null
            )
        ];

        /** @noinspection AdditionOperationOnArraysInspection */
        $data += $companyData;

        $text = $placeholderService->applyPlaceholders(
            $message,
            $data
        );

        $whatsAppService->sendMessage($to, $text);
    }

    /**
     * @return void
     */
    public function sendPreparedNotifications()
    {
    }
}
