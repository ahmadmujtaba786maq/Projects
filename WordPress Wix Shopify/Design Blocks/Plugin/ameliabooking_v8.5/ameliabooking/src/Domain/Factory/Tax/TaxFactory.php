<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Tax;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Factory\Bookable\Service\ExtraFactory;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageFactory;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Float\FloatValue;
use AmeliaBooking\Domain\ValueObjects\String\AmountType;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class TaxFactory
 *
 * @package AmeliaBooking\Domain\Factory\Tax
 */
class TaxFactory
{
    /**
     * @param $data
     *
     * @return Tax
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $tax = new Tax();

        if (isset($data['id'])) {
            $tax->setId(new Id($data['id']));
        }

        if (isset($data['name'])) {
            $tax->setName(new Name($data['name']));
        }

        if (isset($data['amount'])) {
            $tax->setAmount(new FloatValue($data['amount']));
        }

        if (isset($data['status'])) {
            $tax->setStatus(new Status($data['status']));
        }

        if (isset($data['type'])) {
            $tax->setType(new AmountType($data['type']));
        }

        if (isset($data['excluded'])) {
            $tax->setExcluded(new BooleanValueObject($data['excluded']));
        }

        if (isset($data['allServices'])) {
            $tax->setAllServices(new BooleanValueObject($data['allServices']));
        }

        $serviceList = new Collection();

        if (isset($data['serviceList'])) {
            foreach ($data['serviceList'] as $key => $value) {
                $serviceList->addItem(
                    ServiceFactory::create($value),
                    $key
                );
            }
        }

        if (isset($data['allEvents'])) {
            $tax->setAllEvents(new BooleanValueObject($data['allEvents']));
        }

        $eventList = new Collection();

        if (isset($data['eventList'])) {
            foreach ($data['eventList'] as $key => $value) {
                $eventList->addItem(
                    EventFactory::create($value),
                    $key
                );
            }
        }

        if (isset($data['allPackages'])) {
            $tax->setAllPackages(new BooleanValueObject($data['allPackages']));
        }

        $packageList = new Collection();

        if (isset($data['packageList'])) {
            foreach ($data['packageList'] as $key => $value) {
                $packageList->addItem(
                    PackageFactory::create($value),
                    $key
                );
            }
        }

        if (isset($data['allExtras'])) {
            $tax->setAllExtras(new BooleanValueObject($data['allExtras']));
        }

        $extraList = new Collection();

        if (isset($data['extraList'])) {
            foreach ($data['extraList'] as $key => $value) {
                $extraList->addItem(
                    ExtraFactory::create($value),
                    $key
                );
            }
        }

        $tax->setServiceList($serviceList);
        $tax->setEventList($eventList);
        $tax->setPackageList($packageList);
        $tax->setExtraList($extraList);

        return $tax;
    }

    /**
     * @param array $rows
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function createCollection($rows)
    {
        $taxes = [];

        foreach ($rows as $row) {
            $taxId = $row['tax_id'];

            if (empty($taxes[$taxId])) {
                $taxes[$taxId] = [
                    'id'          => $taxId,
                    'name'        => $row['tax_name'],
                    'amount'      => $row['tax_amount'],
                    'type'        => $row['tax_type'],
                    'status'      => $row['tax_status'],
                    'allServices' => $row['tax_allServices'],
                    'allEvents'   => $row['tax_allEvents'],
                    'allPackages' => $row['tax_allPackages'],
                    'allExtras'   => $row['tax_allExtras'],
                    'serviceList' => [],
                    'eventList'   => [],
                    'packageList' => [],
                    'extraList'   => [],
                ];
            }

            if (isset($row['tax_entityId'], $row['tax_entityType']) && $row['tax_entityId']) {
                if ($row['tax_entityType'] === Entities::SERVICE) {
                    $taxes[$taxId]['serviceList'][$row['tax_entityId']]['id'] = $row['tax_entityId'];
                }

                if ($row['tax_entityType'] === Entities::EVENT) {
                    $taxes[$taxId]['eventList'][$row['tax_entityId']]['id'] = $row['tax_entityId'];
                }

                if ($row['tax_entityType'] === Entities::PACKAGE) {
                    $taxes[$taxId]['packageList'][$row['tax_entityId']]['id'] = $row['tax_entityId'];
                }

                if ($row['tax_entityType'] === Entities::EXTRA) {
                    $taxes[$taxId]['extraList'][$row['tax_entityId']]['id'] = $row['tax_entityId'];
                }
            }
        }

        $taxesCollection = new Collection();

        foreach ($taxes as $taxKey => $taxArray) {
            $taxesCollection->addItem(
                self::create($taxArray),
                $taxKey
            );
        }

        return $taxesCollection;
    }
}
