<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Settings;

use AmeliaBooking\Domain\Entity\Settings\GeneralSettings;
use AmeliaBooking\Domain\Entity\Settings\GoogleMeetSettings;
use AmeliaBooking\Domain\Entity\Settings\LessonSpaceSettings;
use AmeliaBooking\Domain\Entity\Settings\MicrosoftTeamsSettings;
use AmeliaBooking\Domain\Entity\Settings\PaymentSettings;
use AmeliaBooking\Domain\Entity\Settings\Settings;
use AmeliaBooking\Domain\Entity\Settings\ZoomSettings;
use AmeliaBooking\Domain\ValueObjects\Json;

/**
 * Class SettingsFactory
 *
 * @package AmeliaBooking\Domain\Factory\Settings
 */
class SettingsFactory
{
    /**
     * @param Json  $entityJsonData
     * @param array $globalSettings
     *
     * @return Settings
     */
    public static function create($entityJsonData, $globalSettings)
    {
        $entitySettings         = new Settings();
        $generalSettings        = new GeneralSettings();
        $zoomSettings           = new ZoomSettings();
        $paymentSettings        = new PaymentSettings();
        $lessonSpaceSetings     = new LessonSpaceSettings();
        $googleMeetSettings     = new GoogleMeetSettings();
        $microsoftTeamsSettings = new MicrosoftTeamsSettings();

        $data = $entityJsonData ? json_decode($entityJsonData->getValue(), true) : [];

        $isOldEntitySettings = !isset($data['activation']['version']);

        if (isset($data['general']['defaultAppointmentStatus'])) {
            $generalSettings->setDefaultAppointmentStatus($data['general']['defaultAppointmentStatus']);
        } else {
            $generalSettings->setDefaultAppointmentStatus($globalSettings['general']['defaultAppointmentStatus']);
        }

        if (isset($data['general']['minimumTimeRequirementPriorToBooking'])) {
            $generalSettings->setMinimumTimeRequirementPriorToBooking(
                $data['general']['minimumTimeRequirementPriorToBooking']
            );
        } else {
            $generalSettings->setMinimumTimeRequirementPriorToBooking(
                $globalSettings['general']['minimumTimeRequirementPriorToBooking']
            );
        }

        if (isset($data['general']['minimumTimeRequirementPriorToCanceling'])) {
            $generalSettings->setMinimumTimeRequirementPriorToCanceling(
                $data['general']['minimumTimeRequirementPriorToCanceling']
            );
        } else {
            $generalSettings->setMinimumTimeRequirementPriorToCanceling(
                $globalSettings['general']['minimumTimeRequirementPriorToCanceling']
            );
        }

        if (isset($data['general']['minimumTimeRequirementPriorToRescheduling'])) {
            $generalSettings->setMinimumTimeRequirementPriorToRescheduling(
                $data['general']['minimumTimeRequirementPriorToRescheduling']
            );
        } else {
            $generalSettings->setMinimumTimeRequirementPriorToRescheduling(
                $globalSettings['general']['minimumTimeRequirementPriorToRescheduling']
            );
        }

        if ($isOldEntitySettings && !isset($globalSettings['general']['minimumTimeRequirementPriorToCanceling'])) {
            $generalSettings->setMinimumTimeRequirementPriorToRescheduling(
                $generalSettings->getMinimumTimeRequirementPriorToCanceling()
            );
        }

        if (!empty($data['general']['numberOfDaysAvailableForBooking'])) {
            $generalSettings->setNumberOfDaysAvailableForBooking(
                $data['general']['numberOfDaysAvailableForBooking']
            );
        } else {
            $generalSettings->setNumberOfDaysAvailableForBooking(
                $globalSettings['general']['numberOfDaysAvailableForBooking']
            );
        }

        if (isset($data['zoom']['enabled'])) {
            $zoomSettings->setEnabled($data['zoom']['enabled']);
        } else {
            $zoomSettings->setEnabled($globalSettings['zoom']['enabled']);
        }

        if (isset($data['lessonSpace']['enabled'])) {
            $lessonSpaceSetings->setEnabled($data['lessonSpace']['enabled']);
        } else {
            $lessonSpaceSetings->setEnabled($globalSettings['lessonSpace']['enabled']);
        }

        if (isset($data['googleMeet']['enabled'])) {
            $googleMeetSettings->setEnabled($data['googleMeet']['enabled']);
        } else {
            $googleMeetSettings->setEnabled($globalSettings['googleCalendar']['enableGoogleMeet']);
        }

        if (isset($data['microsoftTeams']['enabled'])) {
            $microsoftTeamsSettings->setEnabled($data['microsoftTeams']['enabled']);
        } else {
            $microsoftTeamsSettings->setEnabled($globalSettings['outlookCalendar']['enableMicrosoftTeams']);
        }

        $entitySettings->setGeneralSettings($generalSettings);
        $entitySettings->setZoomSettings($zoomSettings);
        $entitySettings->setLessonSpaceSettings($lessonSpaceSetings);
        $entitySettings->setGoogleMeetSettings($googleMeetSettings);
        $entitySettings->setMicrosoftTeamsSettings($microsoftTeamsSettings);

        return $entitySettings;
    }
}
