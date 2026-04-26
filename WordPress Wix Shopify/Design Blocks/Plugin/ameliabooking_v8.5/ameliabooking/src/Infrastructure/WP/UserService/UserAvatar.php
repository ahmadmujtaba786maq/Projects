<?php

namespace AmeliaBooking\Infrastructure\WP\UserService;

/**
 * Class UserAvatar
 *
 * @package AmeliaBooking\Infrastructure\WP\UserService
 */
class UserAvatar
{
    /**
     * @param int $wpUserId
     *
     * @return mixed
     */
    public function getAvatar($wpUserId)
    {
        return get_avatar_url($wpUserId);
    }
}
