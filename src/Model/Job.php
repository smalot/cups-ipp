<?php

namespace Smalot\Cups\Model;

/**
 * Class Job
 *
 * @package Smalot\Cups\Model
 */
class Job implements JobInterface
{

    use Traits\AttributeAware;
    use Traits\UriAware;

    const CONTENT_FILE = 'file';

    const CONTENT_TEXT = 'text';

    const SIDES_TWO_SIDED_LONG_EDGE = 'two-sided-long-edge';

    const SIDES_TWO_SIDED_SHORT_EDGE = 'two-sided-short-edge';

    const SIDES_ONE_SIDED = 'one-sided';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $printerUri;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $pageRanges;

    /**
     * @var int
     */
    protected $copies;

    /**
     * @var int
     */
    protected $sides;

    /**
     * @var int
     */
    protected $fidelity;

    /**
     * @var array
     */
    protected $content = [];

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $stateReason;

    /**
     * Job constructor.
     */
    public function __construct()
    {
        $this->copies = 1;
        $this->sides = self::SIDES_ONE_SIDED;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Job
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrinterUri()
    {
        return $this->printerUri;
    }

    /**
     * @param string $printerUri
     *
     * @return Job
     */
    public function setPrinterUri($printerUri)
    {
        $this->printerUri = $printerUri;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Job
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return Job
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageRanges()
    {
        return $this->pageRanges;
    }

    /**
     * @param string $pageRanges
     *
     * @return Job
     */
    public function setPageRanges($pageRanges)
    {
        $this->pageRanges = $pageRanges;

        return $this;
    }

    /**
     * @return int
     */
    public function getCopies()
    {
        return $this->copies;
    }

    /**
     * @param int $copies
     *
     * @return Job
     */
    public function setCopies($copies)
    {
        $this->copies = $copies;

        return $this;
    }

    /**
     * @return int
     */
    public function getSides()
    {
        return ($this->sides ?: self::SIDES_ONE_SIDED);
    }

    /**
     * @param int $sides
     *
     * @return Job
     */
    public function setSides($sides)
    {
        $this->sides = $sides;

        return $this;
    }

    /**
     * @return int
     */
    public function getFidelity()
    {
        return $this->fidelity;
    }

    /**
     * @param int $fidelity
     *
     * @return Job
     */
    public function setFidelity($fidelity)
    {
        $this->fidelity = $fidelity;

        return $this;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $filename
     * @param string $name
     * @param string $mimeType
     *
     * @return Job
     */
    public function addFile($filename, $name = '', $mimeType = 'application/octet-stream')
    {
        if (empty($name)) {
            $name = basename($filename);
        }

        $this->content[] = [
          'type' => self::CONTENT_FILE,
          'name' => $name,
          'mimeType' => $mimeType,
          'filename' => $filename,
        ];

        return $this;
    }

    /**
     * @param string $text
     * @param string $name
     * @param string $mimeType
     *
     * @return Job
     */
    public function addText($text, $name = '', $mimeType = 'text/plain')
    {
        $this->content[] = [
          'type' => self::CONTENT_TEXT,
          'name' => $name,
          'mimeType' => $mimeType,
          'text' => $text,
        ];

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return Job
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getStateReason()
    {
        return $this->stateReason;
    }

    /**
     * @param string $stateReason
     *
     * @return Job
     */
    public function setStateReason($stateReason)
    {
        $this->stateReason = $stateReason;

        return $this;
    }
}
