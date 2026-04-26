<?php

namespace AmeliaBooking\Infrastructure\Services\Mailchimp;

use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use MailchimpMarketing\ApiClient;

/**
 * Class MailchimpService
 *
 * @package AmeliaBooking\Infrastructure\Services\Mailchimp
 */
class MailchimpService extends AbstractMailchimpService
{
    /** @var SettingsService */
    private $settings;

    /**
     * MailchimpService constructor.
     *
     * @param Container $container
     *
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->settings = $this->container->get('domain.settings.service')->getCategorySettings('mailchimp');
    }
    public function createAuthUrl()
    {
        return 'https://login.mailchimp.com/oauth2/authorize?' .
            http_build_query([
                'response_type' => 'code',
                'client_id'     => AMELIA_MAILCHIMP_CLIENT_ID,
                'redirect_uri'  => 'https://middleware.wpamelia.com/mailchimp/redirect',
                'state'         => urlencode(admin_url('admin-ajax.php', ''))
            ]);
    }

    private function getClient()
    {
        $mailchimpAccessToken = $this->settings['accessToken'];
        $mailchimpServer = $this->settings['server'];

        if (!$mailchimpAccessToken) {
            return null;
        }

        $client = new ApiClient();
        $client->setConfig([
            'accessToken' => $mailchimpAccessToken,
            'server'      => $mailchimpServer,
        ]);

        return $client;
    }

    public function getLists()
    {
        try {
            $client = $this->getClient();

            if (!$client) {
                return [];
            }

            $lists = $client->lists->getAllLists()->lists;

            return array_map(function ($list) {
                return [
                    'id'   => $list->id,
                    'name' => $list->name,
                ];
            }, $lists);
        } catch (\Exception $e) {
            return [];
        }
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
        try {
            $ch = curl_init('https://login.mailchimp.com/oauth2/metadata');

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Authorization: OAuth ' . $accessToken,
                ]
            );

            $response = curl_exec($ch);

            curl_close($ch);

            if ($response) {
                $response = json_decode($response, true);
            }

            return $response['dc'];
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Add subscriber to the mailing list or update existing subscriber.
     *
     * @param array $customer
     * @param string $email
     * @param bool $add
     *
     * @return void
     */
    public function addOrUpdateSubscriber($email, $customer, $add = true)
    {
        $client = $this->getClient();
        if (!$client) {
            return;
        }

        $mailchimpList = $this->settings['list'];

        if (!$mailchimpList) {
            return;
        }

        $birthday = '';
        if (!empty($customer['birthday'])) {
            $birthday = $customer['birthday'];
            if (is_string($customer['birthday'])) {
                $birthday = DateTimeService::getCustomDateTimeObject($birthday);
            }
            $birthday = $birthday->format('m/d');
        }

        $mergeFields = [
            'FNAME' => $customer['firstName'],
            'LNAME' => !empty($customer['lastName']) ? $customer['lastName'] : '',
            'PHONE' => !empty($customer['phone']) ? $customer['phone'] : '',
            'BIRTHDAY' => $birthday,
        ];

        try {
            if ($add) {
                $client->lists->setListMember($mailchimpList, md5(strtolower($customer['email'])), [
                    'email_address' => $customer['email'],
                    'status' => 'subscribed',
                    'merge_fields'  => $mergeFields
                ]);
            } else {
                $client->lists->updateListMember($mailchimpList, md5(strtolower($email)), [
                    'email_address' => $customer['email'],
                    'merge_fields' => $mergeFields
                ]);
            }
        } catch (\Exception $e) {
        }
    }

    public function deleteSubscriber($email)
    {
        $client = $this->getClient();
        if (!$client) {
            return;
        }

        $mailchimpList = $this->settings['list'];
        if (!$mailchimpList) {
            return;
        }

        try {
            $client->lists->deleteListMember($mailchimpList, md5(strtolower($email)));
        } catch (\Exception $e) {
        }
    }
}
