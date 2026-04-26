<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class WooCommerceProductsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class WooCommerceProductsCommandHandler extends CommandHandler
{
    /**
     * @param WooCommerceProductsCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws Exception
     */
    public function handle(WooCommerceProductsCommand $command)
    {
        $result = new CommandResult();

        $params = $command->getField('params');

        $products = WooCommerceService::getAllProducts(
            [
                's'       => !empty($params['name']) ? $params['name'] : '',
                'include' => !empty($params['id']) ? $params['id'] : null
            ]
        );

        $products = apply_filters('amelia_get_wc_products_filter', $products);

        do_action('amelia_get_wc_products', $products);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setData(
            [
                'products' => $products
            ]
        );

        return $result;
    }
}
