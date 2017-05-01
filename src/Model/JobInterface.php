<?php

namespace Smalot\Cups\Model;

/**
 * Interface JobInterface
 *
 * @package Smalot\Cups\Model
 */
interface JobInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     *
     * @return Job
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getUri();

    /**
     * @param string $uri
     *
     * @return Job
     */
    public function setUri($uri);

    /**
     * @return string
     */
    public function getPrinterUri();

    /**
     * @param string $printerUri
     *
     * @return Job
     */
    public function setPrinterUri($printerUri);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return Job
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getUsername();

    /**
     * @param string $username
     *
     * @return Job
     */
    public function setUsername($username);

    /**
     * @return string
     */
    public function getPageRanges();

    /**
     * @param string $pageRanges
     *
     * @return Job
     */
    public function setPageRanges($pageRanges);

    /**
     * @return int
     */
    public function getCopies();

    /**
     * @param int $copies
     *
     * @return Job
     */
    public function setCopies($copies);

    /**
     * @return int
     */
    public function getSides();

    /**
     * @param int $sides
     *
     * @return Job
     */
    public function setSides($sides);

    /**
     * @return array
     */
    public function getContent();

    /**
     * @param string $filename
     * @param string $mimetype
     * @param string $name
     *
     * @return Job
     */
    public function addFile($filename, $mimetype = 'application/octet-stream', $name = '');

    /**
     * @param string $text
     * @param string $name
     *
     * @return Job
     */
    public function addText($text, $name = '');

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @param array $attributes
     *
     * @return Job
     */
    public function setAttributes($attributes);

    /**
     * @return string
     */
    public function getState();

    /**
     * @param string $state
     *
     * @return Job
     */
    public function setState($state);

    /**
     * @return string
     */
    public function getStateReason();

    /**
     * @param string $stateReason
     *
     * @return Job
     */
    public function setStateReason($stateReason);
}
