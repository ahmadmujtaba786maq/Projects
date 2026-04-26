<?php

namespace AmeliaBooking\Domain\Collection;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class AbstractCollection
 *
 * @package AmeliaBooking\Domain\Collection
 */
class AbstractCollection
{
    /** @var array|null */
    protected $items = [];

    /**
     * AbstractCollection constructor.
     *
     * @param array $items
     *
     * @throws InvalidArgumentException
     */
    public function __construct($items = null)
    {
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            $this->addItem($item);
        }

        $this->items = $items;
    }

    /**
     * Add an object to the collection by putting it in the $items array at a
     * specified location specified by $key (if no key is provided, we let
     * PHP pick the next available index). If an attempt is made to add an object
     * using a key that already exists, an exception should be thrown to prevent
     * inadvertently overwriting existing information
     *
     * @param      $item
     * @param null $key
     * @param bool $force
     *
     * @throws InvalidArgumentException
     */
    public function addItem($item, $key = null, $force = false)
    {
        if ($key !== null) {
            $this->placeItem($item, $key, $force);

            return;
        }

        $this->items[] = $item;
    }

    /**
     * Take the key as a parameter indicating which items are targeted for removal.
     *
     * @param $key
     *
     * @throws InvalidArgumentException
     */
    public function deleteItem($key)
    {
        if (!$this->keyExists($key)) {
            throw new InvalidArgumentException("Invalid key {$key}.");
        }

        unset($this->items[$key]);
    }

    /**
     * Take the key as a parameter indicating which items are targeted for retrieval.
     *
     * @param $key
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getItem($key)
    {
        if (!$this->keyExists($key)) {
            throw new InvalidArgumentException("Invalid key {$key}.");
        }

        return $this->items[$key];
    }

    /**
     * Returns items list
     *
     * @return array|null
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * List of keys to any external code
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->items);
    }

    /**
     * How many items are in the collection.
     *
     * @return int
     */
    public function length()
    {
        return count($this->items);
    }

    /**
     * Determining whether a given key exists in the collection
     *
     * @param $key
     *
     * @return bool
     */
    public function keyExists($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * Place an item on the specific position
     *
     * @param $item
     * @param $key
     * @param $force
     *
     * @throws InvalidArgumentException
     */
    public function placeItem($item, $key, $force)
    {
        if ($force === false && $this->keyExists($key)) {
            throw new InvalidArgumentException("Key {$key} already in use.");
        }

        $this->items[$key] = $item;
    }

    /**
     * Return an array of collection items
     *
     * @param $isFrontEndRequest
     *
     * @return array
     */
    public function toArray($isFrontEndRequest = false)
    {
        $array = [];

        foreach ($this->items as $item) {
            $array[] = $item->toArray($isFrontEndRequest);
        }

        return $array;
    }
}
