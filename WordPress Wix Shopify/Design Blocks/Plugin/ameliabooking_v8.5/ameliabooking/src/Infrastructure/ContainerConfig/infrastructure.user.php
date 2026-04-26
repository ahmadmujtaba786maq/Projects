<?php

/**
 * Assembling domain services:
 * Returning the current user entity based on WP user
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Permissions service
 *
 * @param $c
 *
 * @return \AmeliaBooking\Domain\Entity\User\AbstractUser|bool|null
 */
$entries['logged.in.user'] = function ($c) {
    $wpUserService = new AmeliaBooking\Infrastructure\WP\UserService\UserService($c);

    return $wpUserService->getCurrentUser();
};

/**
 * @param $c
 *
 * @return \AmeliaBooking\Infrastructure\WP\UserService\UserService
 */
$entries['users.service'] = function ($c) {
    return new AmeliaBooking\Infrastructure\WP\UserService\UserService($c);
};

/**
 * @return \AmeliaBooking\Infrastructure\WP\UserService\UserAvatar
 */
$entries['user.avatar'] = function () {
    return new AmeliaBooking\Infrastructure\WP\UserService\UserAvatar();
};


/**
 * Create WordPress user service
 *
 * @return \AmeliaBooking\Infrastructure\WP\UserService\CreateWPUser
 */
$entries['user.create.wp.user'] = function () {
    return new AmeliaBooking\Infrastructure\WP\UserService\CreateWPUser();
};
