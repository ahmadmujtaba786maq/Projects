<?php

namespace AmeliaBooking\Application\Controller\Bookable\Category;

use AmeliaBooking\Application\Commands\Bookable\Category\AddCategoryCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class AddCategoryController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Category
 */
class AddCategoryController extends Controller
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
        'serviceList',
        'color',
        'pictureFullPath',
        'pictureThumbPath',
    ];

    /**
     * Instantiates the Add Category command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddCategoryCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new AddCategoryCommand($args);
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
        $eventBus->emit('bookable.category.added', $result);
    }
}
