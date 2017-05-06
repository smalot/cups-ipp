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
     * @return JobInterface
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getUri();

    /**
     * @param string $uri
     *
     * @return JobInterface
     */
    public function setUri($uri);

    /**
     * @return string
     */
    public function getPrinterUri();

    /**
     * @param string $printerUri
     *
     * @return JobInterface
     */
    public function setPrinterUri($printerUri);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return JobInterface
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getUsername();

    /**
     * @param string $username
     *
     * @return JobInterface
     */
    public function setUsername($username);

    /**
     * @return string
     */
    public function getPageRanges();

    /**
     * @param string $pageRanges
     *
     * @return JobInterface
     */
    public function setPageRanges($pageRanges);

    /**
     * @return int
     */
    public function getCopies();

    /**
     * @param int $copies
     *
     * @return JobInterface
     */
    public function setCopies($copies);

    /**
     * @return int
     */
    public function getSides();

    /**
     * @param int $sides
     *
     * @return JobInterface
     */
    public function setSides($sides);

    /**
     * @return int
     */
    public function getFidelity();

    /**
     * @param int $fidelity
     *
     * @return JobInterface
     */
    public function setFidelity($fidelity);

    /**
     * @return array
     */
    public function getContent();

    /**
     * @param string $filename
     * @param string $mimeType
     * @param string $name
     *
     * @return JobInterface
     */
    public function addFile($filename, $mimeType = 'application/octet-stream', $name = '');

    /**
     * @param string $text
     * @param string $name
     *
     * @return JobInterface
     */
    public function addText($text, $name = '');

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @param array $attributes
     *
     * @return JobInterface
     */
    public function setAttributes($attributes);

    /**
     * @return string
     */
    public function getState();

    /**
     * @param string $state
     *
     * @return JobInterface
     */
    public function setState($state);

    /**
     * @return string
     */
    public function getStateReason();

    /**
     * @param string $stateReason
     *
     * @return JobInterface
     */
    public function setStateReason($stateReason);
}
