<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Frontend;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use Exception;
use Less_Exception_Parser;
use Less_Parser;

/**
 * Class LessParserService
 */
class LessParserService
{
    /** @var string */
    private $inputCssScript;

    /** @var string */
    private $outputPath;

    /** @var SettingsService */
    private $settingsService;

    /**
     * LessParserService constructor.
     *
     * @param string          $inputCssScript
     * @param string          $outputPath
     * @param SettingsService $settingsService
     */
    public function __construct($inputCssScript, $outputPath, $settingsService)
    {
        $this->inputCssScript  = $inputCssScript;
        $this->outputPath      = $outputPath;
        $this->settingsService = $settingsService;
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws Exception
     * @throws Less_Exception_Parser
     */
    public function compileAndSave($data)
    {
        $parser = new Less_Parser();

        $parser->parseFile($this->inputCssScript);

        $parser->ModifyVars($data);

        !is_dir($this->outputPath) && !mkdir($this->outputPath, 0755, true) && !is_dir($this->outputPath);

        $hash = $this->generateRandomString();

        file_put_contents($this->outputPath . '/amelia-booking.' . $hash . '.css', $parser->getCss());

        return $hash;
    }

    /**
     * @param int $length
     *
     * @return false|string
     */
    public function generateRandomString($length = 10)
    {
        return substr(
            str_shuffle(
                str_repeat(
                    $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    ceil($length / strlen($x))
                )
            ),
            1,
            $length
        );
    }
}
