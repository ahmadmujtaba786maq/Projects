<?php

namespace AmeliaBooking\Application\Controller\Bookable\Category;

use AmeliaBooking\Application\Commands\Bookable\Category\UpdateCategoryCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateCategoryController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Category
 */
class UpdateCategoryController extends Controller
{
    /**
     * Fields for category that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
        'name',
        'position',
        'translations',
        'color',
        'pictureFullPath',
        'pictureThumbPath',
    ];

    /**
     * Instantiates the Update Category command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateCategoryCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateCategoryCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        $eventBus->emit('bookable.category.updated', $result);
    }
}
