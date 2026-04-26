<?php

namespace AmeliaBooking\Application\Controller\Settings;

use AmeliaBooking\Application\Commands\Settings\UpdateSettingsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateSettingsController
 *
 * @package AmeliaBooking\Application\Controller\Settings
 */
class UpdateSettingsController extends Controller
{
    /**
     * Fields for user that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'activation',
        'company',
        'customization',
        'customizedData',
        'daysOff',
        'general',
        'googleCalendar',
        'outlookCalendar',
        'appleCalendar',
        'labels',
        'notifications',
        'payments',
        'roles',
        'weekSchedule',
        'webHooks',
        'zoom',
        'facebookPixel',
        'googleAnalytics',
        'googleTag',
        'lessonSpace',
        'appointments',
        'sendAllCF',
        'usedLanguages',
        'ics',
        'apiKeys',
        'providerBadges',
        'socialLogin',
        'mailchimp'
    ];

    /**
     * @param Request $request
     * @param         $args
     *
     * @return UpdateSettingsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateSettingsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
