<?php

declare(strict_types=1);

namespace AmeliaSabre\Uri;

/**
 * Invalid Uri.
 *
 * This is thrown when an attempt was made to use AmeliaSabre\Uri parse a uri that
 * it could not.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (https://evertpot.com/)
 * @license http://sabre.io/license/
 */
class InvalidUriException extends \Exception
{
}
