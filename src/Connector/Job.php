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
    ) {
        $request = $this->prepareGetListRequest($uri, $myJobs, $limit, $whichJobs, $subset);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }

    /**
     * @param $jobUri
     * @param bool $subset
     * @param string $attributesGroup
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function getAttributes($jobUri, $subset = false, $attributesGroup = 'all')
    {
        $request = $this->prepareGetAttributesRequest($jobUri, $subset, $attributesGroup);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }

    /**
     * @param string $uri
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function cancel($uri)
    {
        $request = $this->prepareCancelRequest($uri);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }

    /**
     * @param string $uri
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function release($uri)
    {
        $request = $this->prepareReleaseRequest($uri);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }

    /**
     * @param string $uri
     * @param string $until
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function hold($uri, $until = 'indefinite')
    {
        $request = $this->prepareHoldRequest($uri, $until);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }

    /**
     * @param string $uri
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function restart($uri)
    {
        $request = $this->prepareRestartRequest($uri);
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
     * @param bool $subset
     * @param string $attributesGroup
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareGetAttributesRequest($uri, $subset = false, $attributesGroup = 'all')
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildJobURI($uri);

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x09) // Get-Job-Attributes | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$jobUri
          .$username;

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
        } elseif ($attributesGroup) {
            switch ($attributesGroup) {
                case 'job-template':
                    break;
                case 'job-description':
                    break;
                case 'all':
                    break;
                default:
                    trigger_error(_('not a valid attribute group: ').$attributesGroup, E_USER_NOTICE);
                    $attributesGroup = '';
                    break;
            }

            $content .=
              chr(0x44) // keyword
              .$this->getStringLength('requested-attributes')
              .'requested-attributes'
              .$this->getStringLength($attributesGroup)
              .$attributesGroup;
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
    protected function prepareCancelRequest($uri)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildJobURI($uri);

        // Needs a build function call.
        $requestBodyMalformed = '';
        $message = '';

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x08) // cancel-Job | operation-id
          .$operationId //           request-id
          .$requestBodyMalformed
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$jobUri
          .$username
          .$message
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param string $uri
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareReleaseRequest($uri)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildJobURI($uri);

        // Needs a build function call.
        $message = '';

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x0d) // release-Job | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$jobUri
          .$username
          .$message
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param string $uri
     * @param string $until
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareHoldRequest($uri, $until = 'indefinite')
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildJobURI($uri);

        // Needs a build function call.
        $message = '';

        $untilStrings = [
          'no-hold',
          'day-time',
          'evening',
          'night',
          'weekend',
          'second-shift',
          'third-shift',
        ];

        if (!in_array($until, $untilStrings)) {
            $until = 'indefinite';
        }

        $holdUntil = chr(0x42) // keyword
          .$this->getStringLength('job-hold-until')
          .'job-hold-until'
          .$this->getStringLength($until)
          .$until;

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x0C) // hold-Job | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$username
          .$jobUri
          .$message
          .$holdUntil
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param string $uri
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareRestartRequest($uri)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildJobURI($uri);

        // Needs a build function call.
        $message = '';

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x0E) // release-Job | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$jobUri
          .$username
          .$message
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }
}
