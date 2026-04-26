<?php

namespace AmeliaBooking\Infrastructure\Services\Mailchimp;

use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class AbstractMailchimpService
 *
 * @package AmeliaBooking\Infrastructure\Services\Mailchimp
 */
abstract class AbstractMailchimpService
{
    /** @var Container $container */
    protected $container;

    /**
     * Create a URL to obtain user authorization.
     *
     * @return string
     */
    abstract public function createAuthUrl();

    /**
     * Returns the mailing lists
     *
     * @return array
     */
    abstract public function getLists();

    /**
     * Get the metadata server name from Mailchimp.
     *
     * @param string $accessToken
     *
     * @return string|null
     */
    abstract public function getMetadataServerName(string $accessToken);


    /**
     * Add subscriber to the mailing list or update existing subscriber.
     *
     * @param array $customer
     * @param string $email
     * @param bool $add
     *
     * @return void
     */
    abstract public function addOrUpdateSubscriber($email, $customer, $add = true);


    /**
     * Delete subscriber from the mailing list.
     *
     * @param string $email
     *
     * @return void
     */
    abstract public function deleteSubscriber($email);
}
