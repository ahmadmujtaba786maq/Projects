<?php

/**
 * Class for standardizing command results
 */

namespace AmeliaBooking\Application\Commands;

/**
 * Class CommandResult
 *
 * @package AmeliaBooking\Application\Commands
 */
class CommandResult
{
    public const RESULT_SUCCESS  = 'success';
    public const RESULT_ERROR    = 'error';
    public const RESULT_CONFLICT = 'conflict';

    private $data;
    private $message;

    private $result = self::RESULT_SUCCESS;

    private $attachment = false;
    private $file       = null;
    private $url;
    private $dataInResponse = true;

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param string $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function hasAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param mixed $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return mixed
     */
    public function hasDataInResponse()
    {
        return $this->dataInResponse;
    }

    /**
     * @param mixed $dataInResponse
     */
    public function setDataInResponse($dataInResponse)
    {
        $this->dataInResponse = $dataInResponse;
    }
}
