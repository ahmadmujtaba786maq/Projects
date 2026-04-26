<?php

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Container;
use DOMDocument;
use DOMElement;

class NotificationHelperService
{
    /** @var Container */
    protected $container;

    /**
     * NotificationHelperService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function parseAndReplace($content)
    {
        $parsedContent = null;
        $newContent    = $content;

        try {
            $parsedContent = class_exists('DOMDocument') ? $this->parseContent($content) : $content;
        } catch (\Exception $e) {
            $newContent = $content;
        }

        $newContent = str_replace(
            [
                'class="ql-align-center"',
                'class="ql-align-right"',
                'class="ql-align-left"',
                'class="ql-align-justify"'
            ],
            [
                'style="text-align: center;"',
                'style="text-align: right;"',
                'style="text-align: left;"',
                'class="text-align: justify"'
            ],
            $parsedContent ?: $newContent
        );
        return array($parsedContent, $newContent);
    }

    /**
     * @param $content
     * @return string
     */
    private function parseContent($content)
    {
        $html = new DOMDocument();
        $html->loadHTML($content);

        $html->preserveWhiteSpace = false;

        $hasParsedContent = false;

        /** @var DOMElement $image */
        foreach ($html->getElementsByTagName('img') as $image) {
            $src = $image->getAttribute('src');

            if (strpos($src, 'data:image/') === 0) {
                $parts = explode(',', substr($src, 5), 2);

                $mimeSplitWithoutBase64 = explode(';', $parts[0], 2);
                $mimeSplit = explode('/', $mimeSplitWithoutBase64[0], 2);

                $outputFile = '';

                if (!in_array($mimeSplit[1], ['jpeg', 'jpg', 'png', 'gif'])) {
                    continue;
                }

                if (count($mimeSplit) === 2) {
                    $token = new Token();

                    $outputFile = $token->getValue() . '.' . (($mimeSplit[1] === 'jpeg') ? 'jpg' : $mimeSplit[1]);
                }

                $outputPath = AMELIA_UPLOADS_PATH . '/amelia/mail/';

                !is_dir($outputPath) && !mkdir($outputPath, 0755, true) && !is_dir($outputPath);

                file_put_contents($outputPath . $outputFile, base64_decode($parts[1]));

                $content = preg_replace(
                    '/<img(.*?)src="data:image(.*?)"(.*?)>/',
                    '<IMG src="' . AMELIA_UPLOADS_URL . '/amelia/mail/' . $outputFile . '">',
                    $content,
                    1
                );

                $hasParsedContent = true;
            }
        }

        if ($hasParsedContent) {
            return str_replace('<IMG src="', '<img src="', $content);
        }

        return null;
    }
}
