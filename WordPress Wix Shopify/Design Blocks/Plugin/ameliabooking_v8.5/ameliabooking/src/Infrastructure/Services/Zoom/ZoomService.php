<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Zoom;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaFirebase\JWT\JWT;

/**
 * Class ZoomService
 *
 * @package AmeliaBooking\Infrastructure\Services\Zoom
 */
class ZoomService extends AbstractZoomService
{
    /**
     * ZoomService constructor.
     *
     * @param SettingsService $settingsService
     */
    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @param string $apiKey
     * @param string $apiSecret
     *
     * @return string
     */
    private function getJwtToken($apiKey, $apiSecret)
    {
        $token = [
            'iss' => $apiKey,
            'exp' => time() + 3600
        ];

        return JWT::encode($token, $apiSecret);
    }

    /**
     * @param string $accountId
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return string
     */
    private function getAccessToken($accountId, $clientId, $clientSecret)
    {
        $ch = curl_init('https://zoom.us/oauth/token');

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
            ]
        );

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            [
                'grant_type' => 'account_credentials',
                'account_id' => $accountId,
            ]
        );

        $result = curl_exec($ch);

        if ($result === false) {
            return null;
        }

        curl_close($ch);

        $resultArray = json_decode($result, true);

        $this->settingsService->setSetting(
            'zoom',
            'accessToken',
            !empty($resultArray['access_token']) ? $resultArray['access_token'] : ''
        );

        return !empty($resultArray['access_token']) ? $resultArray['access_token'] : null;
    }

    /**
     * @param string     $requestUrl
     * @param array|null $data
     * @param string     $method
     *
     * @return array
     */
    private function request($requestUrl, $data, $method, $token)
    {
        $ch = curl_init($requestUrl);

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]
        );

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_FORCE_OBJECT));
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result === false || $code === 401) {
            return ['message' => curl_error($ch), 'code' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'users' => null];
        }

        curl_close($ch);

        return json_decode($result, true);
    }

    /**
     * @param string     $requestUrl
     * @param array|null $data
     * @param string     $method
     *
     * @return array
     */
    public function execute($requestUrl, $data, $method)
    {
        $zoomSettings = $this->settingsService->getCategorySettings('zoom');

        $token = $zoomSettings['accessToken'] ?: $this->getAccessToken($zoomSettings['accountId'], $zoomSettings['clientId'], $zoomSettings['clientSecret']);

        $resultArray = $this->request(
            $requestUrl,
            $data,
            $method,
            $token
        );

        if (
            !empty($resultArray['code']) &&
            ($resultArray['code'] === 401 || $resultArray['code'] === 4711)
        ) {
            $resultArray = $this->request(
                $requestUrl,
                $data,
                $method,
                $this->getAccessToken(
                    $zoomSettings['accountId'],
                    $zoomSettings['clientId'],
                    $zoomSettings['clientSecret']
                )
            );
        }

        if (
            isset($resultArray['join_url']) &&
            strpos($resultArray['join_url'], 'pwd=') === false &&
            isset($resultArray['encrypted_password'])
        ) {
            $limitParam = strpos($resultArray['join_url'], '?') === false ? '?' : '&';

            $resultArray['join_url'] = $resultArray['join_url'] .=
                $limitParam . 'pwd=' . $resultArray['encrypted_password'];
        }

        return $resultArray;
    }

    /**
     *
     * @return array
     */
    public function getUsers()
    {
        $users = [];

        for ($i = 1; $i <= 5; $i++) {
            $urlParams = 'page_size=2000' . '&page_number=' . $i;

            $response = $this->execute("https://api.zoom.us/v2/users?$urlParams", null, 'GET');

            if ($response['users'] === null && ($response['code'] === 124 || $response['code'] === 4711)) {
                return $response;
            }

            $users = array_merge($users, $response['users']);

            if (sizeof($response['users']) < 2000) {
                break;
            }
        }

        $response['users'] = $users;

        return $response;
    }

    /**
     * @param int   $userId
     * @param array $data
     *
     * @return array
     */
    public function createMeeting($userId, $data)
    {
        $data = apply_filters('amelia_before_zoom_meeting_created_filter', $data, $userId);

        do_action('amelia_before_zoom_meeting_created', $data, $userId);

        return $this->execute("https://api.zoom.us/v2/users/{$userId}/meetings", $data, 'POST');
    }

    /**
     * @param int   $meetingId
     * @param array $data
     *
     * @return array
     */
    public function updateMeeting($meetingId, $data)
    {
        $data = apply_filters('amelia_before_zoom_meeting_updated_filter', $data, $meetingId);

        do_action('amelia_before_zoom_meeting_updated', $data, $meetingId);

        return $this->execute("https://api.zoom.us/v2/meetings/{$meetingId}", $data, 'PATCH');
    }

    /**
     * @param int   $meetingId
     *
     * @return array
     */
    public function deleteMeeting($meetingId)
    {
        $meetingId = apply_filters('amelia_before_zoom_meeting_deleted_filter', $meetingId);

        do_action('amelia_before_zoom_meeting_deleted', $meetingId);

        return $this->execute("https://api.zoom.us/v2/meetings/{$meetingId}", null, 'DELETE');
    }

    /**
     * @param int $meetingId
     *
     * @return array
     */
    public function getMeeting($meetingId)
    {
        return $this->execute("https://api.zoom.us/v2/meetings/{$meetingId}", null, 'GET');
    }
}
