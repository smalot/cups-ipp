<?php

namespace Smalot\Cups\Connector;

use Http\Client\HttpClient;
use Smalot\Cups\Transport\Response as CupsResponse;
use GuzzleHttp\Psr7\Request;

/**
 * Class Job
 *
 * @package Smalot\Cups\Connector
 */
class Job extends ConnectorAbstract
{

    /**
     * @var \Http\Client\HttpClient
     */
    protected $client;

    /**
     * Printer constructor.
     *
     * @param \Http\Client\HttpClient $client
     */
    public function __construct(HttpClient $client)
    {
        parent::__construct();

        $this->client = $client;

        $this->setCharset('us-ascii');
        $this->setLanguage('en-us');
        $this->setOperationId(0);
        $this->setUsername('');
    }

    /**
     * @param $uri
     * @param bool $myJobs
     * @param int $limit
     * @param string $whichJobs
     * @param bool $subset
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function getList(
      $uri,
      $myJobs = true,
      $limit = 0,
      $whichJobs = 'not-completed',
      $subset = false
    )
    {
        $request = $this->prepareGetListRequest($uri, $myJobs, $limit, $whichJobs, $subset);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }

    /**
     * @param string $printerUri
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function getAttributes($printerUri)
    {
        $request = $this->prepareGetAttributesRequest($printerUri);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }

    /**
     * @param string $printerUri
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function pause($printerUri)
    {
        $request = $this->preparePauseRequest($printerUri);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }

    /**
     * @param string $printerUri
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function resume($printerUri)
    {
        $request = $this->prepareResumeRequest($printerUri);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }

    /**
     * @param string $uri
     * @param bool $myJobs
     * @param int $limit
     * @param string $whichJobs
     * @param bool $subset
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareGetListRequest(
      $uri,
      $myJobs = true,
      $limit = 0,
      $whichJobs = 'not-completed',
      $subset = false
    ) {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $printerUri = $this->buildPrinterURI($uri);

        if ($limit) {
            $limit = $this->buildInteger($limit);
            $metaLimit = chr(0x21) // integer
              .$this->getStringLength('limit')
              .'limit'
              .$this->getStringLength($limit)
              .$limit;
        } else {
            $metaLimit = '';
        }

        if ($whichJobs == 'completed') {
            $metaWhichJobs = chr(0x44) // keyword
              .$this->getStringLength('which-jobs')
              .'which-jobs'
              .$this->getStringLength($whichJobs)
              .$whichJobs;
        } else {
            $metaWhichJobs = '';
        }

        if ($myJobs) {
            $metaMyJobs = chr(0x22) // boolean
              .$this->getStringLength('my-jobs')
              .'my-jobs'
              .$this->getStringLength(chr(0x01))
              .chr(0x01);
        } else {
            $metaMyJobs = '';
        }

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x0A) // Get-Jobs | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$printerUri
          .$username
          .$metaLimit
          .$metaWhichJobs
          .$metaMyJobs;

        if ($subset) {
            $content .=
              chr(0x44) // keyword
              .$this->getStringLength('requested-attributes')
              .'requested-attributes'
              .$this->getStringLength('job-uri')
              .'job-uri'
              .chr(0x44) // keyword
              .$this->getStringLength('')
              .''
              .$this->getStringLength('job-name')
              .'job-name'
              .chr(0x44) // keyword
              .$this->getStringLength('')
              .''
              .$this->getStringLength('job-state')
              .'job-state'
              .chr(0x44) // keyword
              .$this->getStringLength('')
              .''
              .$this->getStringLength('job-state-reason')
              .'job-state-reason';
        } else { # cups 1.4.4 doesn't return much of anything without this
            $content .=
              chr(0x44) // keyword
              .$this->getStringLength('requested-attributes')
              .'requested-attributes'
              .$this->getStringLength('all')
              .'all';
        }
        $content .= chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param string $uri
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareGetAttributesRequest($uri)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $printerAttributes = $this->buildPrinterAttributes();
        $printerUri = $this->buildPrinterURI($uri);

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x0b) // Print-URI | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$printerUri
          .$username
          .$printerAttributes
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/', $headers, $content);
    }

    /**
     * @param string $uri
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function preparePauseRequest($uri)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $printerUri = $this->buildPrinterURI($uri);

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x10) // Pause-Printer | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$printerUri
          .$username
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/admin/', $headers, $content);
    }

    /**
     * @param string $uri
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareResumeRequest($uri)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $printerUri = $this->buildPrinterURI($uri);

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x11) // Resume-Printer | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$printerUri
          .$username
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/admin/', $headers, $content);
    }
}
