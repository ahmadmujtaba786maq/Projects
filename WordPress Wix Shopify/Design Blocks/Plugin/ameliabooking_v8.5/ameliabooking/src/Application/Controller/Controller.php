<?php

namespace AmeliaBooking\Application\Controller;

use AmeliaBooking\Application\Commands\Command;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Permissions\PermissionsService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Domain\Events\DomainEventBus;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use AmeliaBooking\Domain\Common\Exceptions\CustomException;
use League\Tactician\CommandBus;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Controller
 *
 * @package AmeliaBooking\Application\Controller
 */
abstract class Controller
{
    public const STATUS_OK        = 200;
    public const STATUS_REDIRECT  = 302;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOT_FOUNT = 404;
    public const STATUS_CONFLICT  = 409;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;

    /**
     * @var CommandBus
     */
    protected $commandBus;
    /**
     * @var DomainEventBus
     */
    protected $eventBus;

    /**
     * @var PermissionsService
     */
    protected $permissionsService;
    protected $allowedFields = [
        'ameliaNonce',
        'wpAmeliaNonce',
    ];

    protected $sendJustData = false;
    /**
     * @var UserApplicationService
     */
    private $userApplicationService;

    /**
     * Base Controller constructor.
     *
     * @param Container $container
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __construct(Container $container, $fromApi = false)
    {
        $this->commandBus         = $container->getCommandBus();
        $this->eventBus           = $container->getEventBus();
        $this->permissionsService = $fromApi ? $container->getApiPermissionsService() : $container->getPermissionsService();
        $this->userApplicationService = $fromApi ? $container->getApiUserApplicationService() : $container->getUserApplicationService();
    }

    /**
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     */
    abstract protected function instantiateCommand(Request $request, $args);

    /**
     * Emit a success domain event, do nothing by default
     *
     * @param DomainEventBus $eventBus
     *
     * @param CommandResult  $result
     *
     * @return null
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        return null;
    }

    /**
     * Emit a failure domain event, do nothing by default
     *
     * @param DomainEventBus $eventBus
     *
     * @param CommandResult  $data
     *
     * @return null
     */
    protected function emitFailureEvent(DomainEventBus $eventBus, CommandResult $data)
    {
        return null;
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param          $args
     *
     * @return Response
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __invoke(Request $request, Response $response, $args, $validApiCall = false)
    {
        /** @var Command $command */
        $command = $this->instantiateCommand($request, $args);

        /** @var SettingsService $settingsService */
        $settingsService = new SettingsService(new SettingsStorage());

        if (!$validApiCall && !$command->validateNonce($request)) {
            return $response->withStatus(self::STATUS_FORBIDDEN);
        }

        $command->setPermissionService($this->permissionsService);
        $command->setUserApplicationService($this->userApplicationService);

        try {
            /** @var CommandResult $commandResult */
            $commandResult = $this->commandBus->handle($command);
        } catch (CustomException $e) {
            $response = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
            $response = $response->withStatus(self::STATUS_INTERNAL_SERVER_ERROR);

            $response =  $response->write(
                json_encode(
                    [
                    'data' => [
                    'message' => $e->getMessage()
                    ]
                    ]
                )
            );

            return $response;
        }

        if ($commandResult->getResult() === CommandResult::RESULT_ERROR) {
            if ($settingsService->getSetting('activation', 'responseErrorAsConflict')) {
                $commandResult->setResult(CommandResult::RESULT_CONFLICT);
            }
        }

        if ($commandResult->getUrl() !== null) {
            $this->emitSuccessEvent($this->eventBus, $commandResult);

            /** @var Response $response */
            $response = $response->withHeader('Location', $commandResult->getUrl());
            $response = $response->withStatus(self::STATUS_REDIRECT);

            return $response;
        }

        if ($commandResult->hasAttachment() === false) {
            $responseBody = [
                'message' => $commandResult->getMessage(),
                'data'    => $commandResult->getData()
            ];

            $this->emitSuccessEvent($this->eventBus, $commandResult);

            switch ($commandResult->getResult()) {
                case (CommandResult::RESULT_SUCCESS):
                    $response = $response->withStatus(self::STATUS_OK);

                    break;
                case (CommandResult::RESULT_CONFLICT):
                    $response = $response->withStatus(self::STATUS_CONFLICT);

                    break;
                default:
                    $response = $response->withStatus(self::STATUS_INTERNAL_SERVER_ERROR);

                    break;
            }

            /** @var Response $response */
            $response = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
            $response = $response->write(
                $this->sendJustData ? $commandResult->getData() :
                json_encode(
                    $commandResult->hasDataInResponse() ?
                        $responseBody : array_merge($responseBody, ['data' => []])
                )
            );
        }

        if (($file = $commandResult->getFile()) !== null) {
            /** @var Response $response */
            $response = $response->withHeader('Content-Type', $file['type']);
            $response = $response->withHeader('Content-Disposition', 'inline; filename=' . '"' . $file['name'] . '"');
            $response = $response->withHeader('Cache-Control', 'max-age=0');

            if (array_key_exists('size', $file)) {
                $response = $response->withHeader('Content-Length', $file['size']);
            }

            $response = $response->write($file['content']);
        }

        return $response;
    }

    /**
     * @param Command $command
     * @param         $requestBody
     */
    protected function setCommandFields($command, $requestBody)
    {
        foreach ($this->allowedFields as $field) {
            if (!isset($requestBody[$field])) {
                continue;
            }
            $command->setField($field, $requestBody[$field]);
        }
    }

    /**
     * @param mixed $params
     */
    protected function setArrayParams(&$params)
    {
        $names = [
            'customers',
            'categories',
            'services',
            'packages',
            'employees',
            'providers',
            'providerIds',
            'locations',
            'locationIds',
            'events',
            'dates',
            'types',
            'fields',
            'statuses',
        ];

        foreach ($names as $name) {
            if (!empty($params[$name])) {
                $params[$name] = is_array($params[$name]) ? $params[$name] : explode(',', $params[$name]);
            }
        }

        if (isset($params['dates'][0])) {
            $params['dates'][0] = preg_match("/^\d{4}-\d{2}-\d{2}$/", $params['dates'][0]) ?
                $params['dates'][0] : DateTimeService::getNowDate();
        }

        if (isset($params['dates'][1])) {
            $params['dates'][1] = preg_match("/^\d{4}-\d{2}-\d{2}$/", $params['dates'][1]) ?
                $params['dates'][1] : DateTimeService::getNowDate();
        }

        if (isset($params['date'])) {
            $params['date'] = preg_match("/^\d{4}-\d{2}-\d{2}$/", $params['date']) ?
                $params['date'] : DateTimeService::getNowDate();
        }
    }

    /**
     * @param array  $data
     * @param string $field
     * @param string $translationField
     *
     * @return void
     */
    private function filterField(&$data, $field, $translationField)
    {
        if (!empty($data[$field])) {
            global $allowedposttags;

            $data[$field] = wp_kses($data[$field], $allowedposttags);

            if (!empty($data['translations']) && ($translations = json_decode($data['translations'], true)) !== null) {
                if (!empty($translations[$translationField])) {
                    foreach ($translations[$translationField] as $lang => $translation) {
                        $translations[$translationField][$lang] = wp_kses(
                            $translations[$translationField][$lang],
                            $allowedposttags
                        );
                    }

                    $data['translations'] = json_encode($translations);
                }
            }
        }
    }

    /**
     * @param array $requestBody
     *
     * @return void
     */
    protected function filter(&$requestBody)
    {
        if (!current_user_can('unfiltered_html') && $requestBody) {
            $this->filterField($requestBody, 'description', 'description');
            $this->filterField($requestBody, 'label', 'name');

            foreach (!empty($requestBody['extras']) ? $requestBody['extras'] : [] as $index => $extra) {
                $this->filterField($requestBody['extras'][$index], 'description', 'description');
            }
        }
    }
}
