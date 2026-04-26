<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Gallery\GalleryImage;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Duration;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\PositiveDuration;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\Cycle;
use AmeliaBooking\Domain\ValueObjects\String\DepositType;
use AmeliaBooking\Domain\ValueObjects\String\EntityType;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Priority;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\Licence;

/**
 * Class ServiceFactory
 *
 * @package AmeliaBooking\Domain\Factory\Bookable\Service
 */
class ServiceFactory
{
    /**
     * @param $data
     *
     * @return Service
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        Licence\DataModifier::serviceFactory($data);

        $service = new Service();

        if (isset($data['id'])) {
            $service->setId(new Id($data['id']));
        }

        if (isset($data['name'])) {
            $service->setName(new Name($data['name']));
        }

        if (isset($data['price'])) {
            $service->setPrice(new Price($data['price']));
        }

        if (isset($data['status'])) {
            $service->setStatus(new Status($data['status']));
        }

        if (isset($data['categoryId'])) {
            $service->setCategoryId(new Id($data['categoryId']));
        }

        if (isset($data['minCapacity'])) {
            $service->setMinCapacity(new IntegerValue($data['minCapacity']));
        }

        if (isset($data['maxCapacity'])) {
            $service->setMaxCapacity(new IntegerValue($data['maxCapacity']));
        }

        if (isset($data['duration'])) {
            $service->setDuration(new PositiveDuration($data['duration']));
        }

        if (isset($data['description'])) {
            $service->setDescription(new Description($data['description']));
        }

        if (isset($data['color'])) {
            $service->setColor(new Color($data['color']));
        }

        if (!empty($data['timeBefore'])) {
            $service->setTimeBefore(new Duration($data['timeBefore']));
        }

        if (!empty($data['timeAfter'])) {
            $service->setTimeAfter(new Duration($data['timeAfter']));
        }

        if (isset($data['bringingAnyone'])) {
            $service->setBringingAnyone(new BooleanValueObject($data['bringingAnyone']));
        }

        if (isset($data['aggregatedPrice'])) {
            $service->setAggregatedPrice(new BooleanValueObject($data['aggregatedPrice']));
        }

        if (!empty($data['priority'])) {
            $service->setPriority(new Priority($data['priority']));
        }

        if (!empty($data['pictureFullPath']) && !empty($data['pictureThumbPath'])) {
            $service->setPicture(new Picture($data['pictureFullPath'], $data['pictureThumbPath']));
        }

        if (!empty($data['position'])) {
            $service->setPosition(new PositiveInteger($data['position']));
        }

        if (isset($data['show'])) {
            $service->setShow(new BooleanValueObject($data['show']));
        }

        if (!empty($data['settings'])) {
            $service->setSettings(new Json($data['settings']));
        }

        if (!empty($data['recurringCycle'])) {
            $service->setRecurringCycle(new Cycle($data['recurringCycle']));
        }

        if (!empty($data['recurringSub'])) {
            $service->setRecurringSub(new Name($data['recurringSub']));
        }

        if (isset($data['recurringPayment'])) {
            $service->setRecurringPayment(new WholeNumber($data['recurringPayment']));
        } else {
            $service->setRecurringPayment(new WholeNumber(0));
        }

        if (isset($data['translations'])) {
            $service->setTranslations(new Json($data['translations']));
        }

        if (!empty($data['customPricing'])) {
            $service->setCustomPricing(new Json($data['customPricing']));
        }

        if (isset($data['limitPerCustomer'])) {
            $service->setLimitPerCustomer(new Json($data['limitPerCustomer']));
        }

        if (isset($data['deposit'])) {
            $service->setDeposit(new Price($data['deposit']));
        }

        if (isset($data['depositPayment'])) {
            $service->setDepositPayment(new DepositType($data['depositPayment']));
        }

        if (isset($data['depositPerPerson'])) {
            $service->setDepositPerPerson(new BooleanValueObject($data['depositPerPerson']));
        }

        if (isset($data['mandatoryExtra'])) {
            $service->setMandatoryExtra(new BooleanValueObject($data['mandatoryExtra']));
        }

        if (!empty($data['minSelectedExtras'])) {
            $service->setMinSelectedExtras(new IntegerValue($data['minSelectedExtras']));
        }

        if (isset($data['fullPayment'])) {
            $service->setFullPayment(new BooleanValueObject($data['fullPayment']));
        }

        $gallery = new Collection();

        if (!empty($data['gallery'])) {
            foreach ((array)$data['gallery'] as $image) {
                $galleryImage = new GalleryImage(
                    new EntityType(Entities::SERVICE),
                    new Picture($image['pictureFullPath'], $image['pictureThumbPath']),
                    new PositiveInteger($image['position'])
                );

                if (!empty($image['id'])) {
                    $galleryImage->setId(new Id($image['id']));
                }

                if ($service->getId()) {
                    $galleryImage->setEntityId($service->getId());
                }

                $gallery->addItem($galleryImage);
            }
        }

        $service->setGallery($gallery);

        $extras = new Collection();
        if (!empty($data['extras'])) {
            /** @var array $extrasList */
            $extrasList = $data['extras'];
            foreach ($extrasList as $extraKey => $extra) {
                $extras->addItem(ExtraFactory::create($extra), $extraKey);
            }
        }
        $service->setExtras($extras);

        $coupons = new Collection();
        if (!empty($data['coupons'])) {
            /** @var array $couponsList */
            $couponsList = $data['coupons'];
            foreach ($couponsList as $couponKey => $coupon) {
                $coupons->addItem(CouponFactory::create($coupon), $couponKey);
            }
        }
        $service->setCoupons($coupons);

        if (isset($data['maxExtraPeople'])) {
            $service->setMaxExtraPeople(new IntegerValue($data['maxExtraPeople']));
        }

        return $service;
    }

    /**
     * @param array $rows
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function createCollection($rows)
    {
        $services = [];

        foreach ($rows as $row) {
            $serviceId = $row['service_id'];
            $extraId   = $row['extra_id'];
            $galleryId = isset($row['gallery_id']) ? $row['gallery_id'] : null;

            $services[$serviceId]['id']          = $row['service_id'];
            $services[$serviceId]['name']        = $row['service_name'];
            $services[$serviceId]['description'] = $row['service_description'];
            $services[$serviceId]['color']       = $row['service_color'];
            $services[$serviceId]['price']       = $row['service_price'];
            $services[$serviceId]['status']      = $row['service_status'];
            $services[$serviceId]['categoryId']  = $row['service_categoryId'];
            $services[$serviceId]['minCapacity'] = $row['service_minCapacity'];
            $services[$serviceId]['maxCapacity'] = $row['service_maxCapacity'];
            $services[$serviceId]['maxExtraPeople']   = isset($row['service_maxExtraPeople'])
                ? $row['service_maxExtraPeople'] : null;
            $services[$serviceId]['duration']         = $row['service_duration'];
            $services[$serviceId]['timeAfter']        = $row['service_timeAfter'];
            $services[$serviceId]['timeBefore']       = $row['service_timeBefore'];
            $services[$serviceId]['bringingAnyone']   = $row['service_bringingAnyone'];
            $services[$serviceId]['pictureFullPath']  = $row['service_picture_full'];
            $services[$serviceId]['pictureThumbPath'] = $row['service_picture_thumb'];
            $services[$serviceId]['position']         = isset($row['service_position']) ? $row['service_position'] : 0;
            $services[$serviceId]['show']            = isset($row['service_show']) ? $row['service_show'] : 0;
            $services[$serviceId]['aggregatedPrice'] = isset($row['service_aggregatedPrice']) ?
                $row['service_aggregatedPrice'] : 0;
            $services[$serviceId]['settings']        = isset($row['service_settings']) ?
                $row['service_settings'] : null;
            $services[$serviceId]['recurringCycle']  = isset($row['service_recurringCycle']) ?
                $row['service_recurringCycle'] : null;
            $services[$serviceId]['recurringSub']    = isset($row['service_recurringSub']) ?
                $row['service_recurringSub'] : null;
            $services[$serviceId]['recurringPayment']  = isset($row['service_recurringPayment']) ?
                $row['service_recurringPayment'] : null;
            $services[$serviceId]['translations']      = isset($row['service_translations']) ?
                $row['service_translations'] : null;
            $services[$serviceId]['customPricing']     = isset($row['service_customPricing']) ?
                $row['service_customPricing'] : null;
            $services[$serviceId]['limitPerCustomer']  = isset($row['service_limitPerCustomer']) ?
                $row['service_limitPerCustomer'] : null;
            $services[$serviceId]['deposit']           = isset($row['service_deposit']) ? $row['service_deposit'] : 0;
            $services[$serviceId]['depositPayment']    = isset($row['service_depositPayment']) ?
                $row['service_depositPayment'] : 'disabled';
            $services[$serviceId]['depositPerPerson']  = isset($row['service_depositPerPerson']) ?
                $row['service_depositPerPerson'] : 1;
            $services[$serviceId]['mandatoryExtra']    = isset($row['service_mandatoryExtra']) ?
                $row['service_mandatoryExtra'] : null;
            $services[$serviceId]['minSelectedExtras'] = isset($row['service_minSelectedExtras']) ?
                $row['service_minSelectedExtras'] : null;
            $services[$serviceId]['fullPayment']       = isset($row['service_fullPayment']) ?
                $row['service_fullPayment'] : null;

            if ($extraId) {
                $services[$serviceId]['extras'][$extraId]['id']          = $row['extra_id'];
                $services[$serviceId]['extras'][$extraId]['name']        = $row['extra_name'];
                $services[$serviceId]['extras'][$extraId]['description'] = isset($row['extra_description']) ?
                    $row['extra_description'] : null;
                $services[$serviceId]['extras'][$extraId]['price']       = $row['extra_price'];
                $services[$serviceId]['extras'][$extraId]['maxQuantity'] = $row['extra_maxQuantity'];
                $services[$serviceId]['extras'][$extraId]['duration']    = $row['extra_duration'];
                $services[$serviceId]['extras'][$extraId]['position']    = $row['extra_position'];
                $services[$serviceId]['extras'][$extraId]['aggregatedPrice'] = $row['extra_aggregatedPrice'];
                $services[$serviceId]['extras'][$extraId]['translations']    = $row['extra_translations'];
            }

            if ($galleryId) {
                $services[$serviceId]['gallery'][$galleryId]['id'] = $row['gallery_id'];
                $services[$serviceId]['gallery'][$galleryId]['pictureFullPath']  = $row['gallery_picture_full'];
                $services[$serviceId]['gallery'][$galleryId]['pictureThumbPath'] = $row['gallery_picture_thumb'];
                $services[$serviceId]['gallery'][$galleryId]['position']         = $row['gallery_position'];
            }
        }

        $servicesCollection = new Collection();

        foreach ($services as $serviceKey => $serviceArray) {
            if (!array_key_exists('extras', $serviceArray)) {
                $serviceArray['extras'] = [];
            }

            if (!array_key_exists('gallery', $serviceArray)) {
                $serviceArray['gallery'] = [];
            }

            $servicesCollection->addItem(
                self::create($serviceArray),
                $serviceKey
            );
        }

        return $servicesCollection;
    }
}
