<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Resource;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\EntityType;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Status;

/**
 * Class ResourceFactory
 *
 * @package AmeliaBooking\Domain\Factory\Bookable\Service
 */
class ResourceFactory
{
    /**
     * @param $data
     *
     * @return Resource
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        /** @var Resource $resource */
        $resource = new Resource();

        if (isset($data['id'])) {
            $resource->setId(new Id($data['id']));
        }

        if (isset($data['name'])) {
            $resource->setName(new Name($data['name']));
        }

        if (!empty($data['quantity'])) {
            $resource->setQuantity(new PositiveInteger($data['quantity']));
        }

        if (!empty($data['status'])) {
            $resource->setStatus(new Status($data['status']));
        }

        if (isset($data['shared'])) {
            $resource->setShared(new EntityType($data['shared']));
        }

        if (isset($data['entities'])) {
            $resource->setEntities(($data['entities']));
        }

        if (!empty($data['countAdditionalPeople'])) {
            $resource->setCountAdditionalPeople(new BooleanValueObject($data['countAdditionalPeople']));
        }

        return $resource;
    }

    /**
     * @param array $rows
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function createCollection($rows)
    {
        $resources = [];

        foreach ($rows as $row) {
            $resourceId = $row['resource_id'];

            $resourceEntityId = $row['resource_entity_id'];

            $resources[$resourceId]['id'] = $row['resource_id'];

            $resources[$resourceId]['name'] = $row['resource_name'];

            $resources[$resourceId]['quantity'] = $row['resource_quantity'];

            $resources[$resourceId]['countAdditionalPeople'] = $row['resource_countAdditionalPeople'];

            $resources[$resourceId]['status'] = $row['resource_status'];

            $resources[$resourceId]['shared'] = $row['resource_shared'];

            if (!isset($resources[$resourceId]['entities'])) {
                $resources[$resourceId]['entities'] = [];
            }

            if ($resourceEntityId) {
                $resources[$resourceId]['entities'][$resourceEntityId]['id'] = (int)$resourceEntityId;

                $resources[$resourceId]['entities'][$resourceEntityId]['resourceId'] = (int)$resourceId;

                $resources[$resourceId]['entities'][$resourceEntityId]['entityId'] =
                    (int)$row['resource_entity_entityId'];

                $resources[$resourceId]['entities'][$resourceEntityId]['entityType'] =
                    $row['resource_entity_entityType'];
            }
        }

        $resourcesCollection = new Collection();

        foreach ($resources as $key => $resourceArray) {
            $resourcesCollection->addItem(
                self::create($resourceArray),
                $key
            );
        }

        return $resourcesCollection;
    }
}
