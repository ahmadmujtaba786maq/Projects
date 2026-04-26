<?php

namespace AmeliaBooking\Application\Commands;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class CommandHandler
 *
 * @package AmeliaBooking\Application\Commands
 */
abstract class CommandHandler
{
    /**
     * @var Container
     */
    protected $container;

    protected $mandatoryFields = [];

    /**
     * @param Command $command
     *
     * @throws InvalidArgumentException
     */
    public function checkMandatoryFields($command)
    {
        $missingFields = [];

        foreach ($this->mandatoryFields as $field) {
            if ($command->getField($field) === null) {
                $missingFields[] = $field;
            }
        }
        if (!empty($missingFields)) {
            throw new InvalidArgumentException(
                'Mandatory fields not passed! Missing: ' . implode(', ', $missingFields)
            );
        }
    }

    /**
     * CommandHandler constructor.
     *
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}
