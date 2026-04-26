<?php

namespace AmeliaBooking\Infrastructure\Licence\Starter;

use AmeliaBooking\Infrastructure\Services as InfrastructureServices;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class InfrastructureService
 *
 * @package AmeliaBooking\Infrastructure\Licence\Starter
 */
class InfrastructureService extends \AmeliaBooking\Infrastructure\Licence\Lite\InfrastructureService
{
    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Recaptcha\AbstractRecaptchaService
     */
    public static function getRecaptchaService($c)
    {
        return new InfrastructureServices\Recaptcha\RecaptchaService(
            $c->get('domain.settings.service')
        );
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\LessonSpace\AbstractLessonSpaceService
     */
    public static function getLessonSpaceService($c)
    {
        return new InfrastructureServices\LessonSpace\LessonSpaceService(
            $c,
            $c->get('domain.settings.service')
        );
    }
}
