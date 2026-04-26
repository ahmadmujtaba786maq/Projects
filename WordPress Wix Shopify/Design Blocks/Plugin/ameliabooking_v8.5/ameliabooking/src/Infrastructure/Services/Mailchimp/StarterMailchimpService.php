<?php

namespace AmeliaBooking\Infrastructure\Services\Mailchimp;

use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class StarterMailchimpService
 *
 * @package AmeliaBooking\Infrastructure\Services\Mailchimp
 */
class StarterMailchimpService extends AbstractMailchimpService
{
    /**
     * StarterMailchimpService constructor.
     *
     * @param Container $container
     *
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Create a URL to obtain user authorization.
     *
     * @return string
     */
    public function createAuthUrl()
    {
        return '';
    }

    /**
     * Returns the mailing lists
     *
     * @return array
     */
    public function getLists()
    {
        return [];
    }

    /**
     * Get the metadata server name from Mailchimp.
     *
     * @param string $accessToken
     *
     * @return string|null
     */
    public function getMetadataServerName($accessToken)
    {
        return '';
    }

    /**
     * Add subscriber to the mailing list or update existing subscriber.
     *
     *
     * @param string $email
     * @param $customer
     * @param bool $add
     * @return void
     */
    public function addOrUpdateSubscriber($email, $customer, $add = true)
    {
    }

    /**
     * Delete subscriber from the mailing list.
     *
     * @param string $email
     *
     * @return void
     */
    public function deleteSubscriber($email)
    {
    }
}
