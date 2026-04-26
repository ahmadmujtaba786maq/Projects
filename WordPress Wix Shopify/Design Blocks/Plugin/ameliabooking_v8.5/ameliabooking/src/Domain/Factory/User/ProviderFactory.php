<?php

namespace AmeliaBooking\Domain\Factory\User;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;

/**
 * Class ProviderFactory
 *
 * @package AmeliaBooking\Domain\Factory\User
 */
class ProviderFactory extends UserFactory
{
    /**
     * @param array $providers
     * @param array $services
     * @param array $providersServices
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function createCollection($providers, $services = [], $providersServices = [])
    {
        $providersCollection = new Collection();

        foreach ($providers as $providerKey => $providerArray) {
            if (!empty($providerArray['stripeConnect']) && !is_array($providerArray['stripeConnect'])) {
                $providerArray['stripeConnect'] = json_decode($providerArray['stripeConnect'], true);
            }

            if (!empty($providerArray['employeeAppleCalendar']) && !is_array($providerArray['employeeAppleCalendar'])) {
                $providerArray['employeeAppleCalendar'] = json_decode($providerArray['employeeAppleCalendar'], true);
            }

            $providersCollection->addItem(
                self::create($providerArray),
                $providerKey
            );

            if ($providersServices && array_key_exists($providerKey, $providersServices)) {
                foreach ((array)$providersServices[$providerKey] as $serviceKey => $providerService) {
                    if (array_key_exists($serviceKey, $services)) {
                        $providersCollection->getItem($providerKey)->getServiceList()->addItem(
                            ServiceFactory::create(
                                array_merge(
                                    $services[$serviceKey],
                                    $providerService
                                )
                            ),
                            $serviceKey
                        );
                    }
                }
            }
        }

        return $providersCollection;
    }
}
