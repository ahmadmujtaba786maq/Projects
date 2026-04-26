<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\WhatsNew;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;
use DOMDocument;
use DOMXPath;
use stdClass;

/**
 * Class GetWhatsNewCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\WhatsNew
 */
class GetWhatsNewCommandHandler extends CommandHandler
{
    /**
     * @param GetWhatsNewCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function handle(GetWhatsNewCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::DASHBOARD)) {
            throw new AccessDeniedException('You are not allowed to read news.');
        }

        $blogPosts = null;

        $result = new CommandResult();

        $blogPageContent = $this->getPageContent('https://wpamelia.com/blog/');

        $blogPostsElements = $blogPageContent->query('//article[contains(@class, "post-list-post")]');

        /** @var \DOMElement $blogPostElement */
        foreach ($blogPostsElements as $blogPostElement) {
            $post = new stdClass();

            $post->href = $blogPostElement->getElementsByTagName('a')[0]->getAttribute('href');

            $post->title = $blogPostElement->getElementsByTagName('h2')->item(0)->textContent;

            $blogPosts[] = $post;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved news.');
        $result->setData(
            [
                'blogPosts'  => $blogPosts
            ]
        );

        return $result;
    }

    /**
     * @param string $URL
     * @return DOMXPath
     */
    public function getPageContent(string $URL)
    {
        $curl = curl_init($URL);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $pageData = curl_exec($curl);
        curl_close($curl);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($pageData);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }
}
