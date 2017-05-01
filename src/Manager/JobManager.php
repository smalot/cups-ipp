<?php

namespace Smalot\Cups\Manager;

use Http\Client\HttpClient;
use Smalot\Cups\CupsException;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\JobInterface;
use Smalot\Cups\Transport\Response as CupsResponse;
use GuzzleHttp\Psr7\Request;

/**
 * Class Job
 *
 * @package Smalot\Cups\Manager
 */
class JobManager extends ManagerAbstract
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
     * @param string $uri
     * @param bool $myJobs
     * @param int $limit
     * @param string $whichJobs
     * @param bool $subset
     *
     * @return \Smalot\Cups\Model\JobInterface[]
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
        $result = CupsResponse::parseResponse($response);
        $values = $result->getValues();

        $list = [];

        if (!empty($values['job-attributes'])) {
            foreach ($values['job-attributes'] as $values) {
                $job = new Job();
                $this->fillAttributes($job, $values);

                $list[] = $job;
            }
        }

        return $list;
    }

    /**
     * @param \Smalot\Cups\Model\JobInterface $job
     * @param bool $subset
     * @param string $attributesGroup
     *
     * @return \Smalot\Cups\Model\JobInterface
     */
    public function loadAttributes(JobInterface $job, $subset = false, $attributesGroup = 'all')
    {
        $request = $this->prepareLoadAttributesRequest($job->getUri(), $subset, $attributesGroup);
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);
        $values = $result->getValues();

        if (isset($values['job-attributes'][0])) {
            $this->fillAttributes($job, $values['job-attributes'][0]);
        }

        return $job;
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     * @throws \Smalot\Cups\CupsException
     */
    public function cancel(JobInterface $job)
    {
        $request = $this->prepareCancelRequest($job->getUri());
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);

        if ($result->getStatusCode() != 'successfull-ok') {
            $message = $result->getStatusMessage() ?:$result->getStatusCode();
            throw new CupsException($message);
        }

        return true;
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     * @throws \Smalot\Cups\CupsException
     */
    public function release(JobInterface $job)
    {
        $request = $this->prepareReleaseRequest($job->getUri());
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);

        if ($result->getStatusCode() != 'successfull-ok') {
            $message = $result->getStatusMessage() ?:$result->getStatusCode();
            throw new CupsException($message);
        }

        return true;
    }

    /**
     * @param JobInterface $job
     * @param string $until
     *
     * @return bool
     * @throws \Smalot\Cups\CupsException
     */
    public function hold(JobInterface $job, $until = 'indefinite')
    {
        $request = $this->prepareHoldRequest($job->getUri(), $until);
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);

        if ($result->getStatusCode() != 'successfull-ok') {
            $message = $result->getStatusMessage() ?:$result->getStatusCode();
            throw new CupsException($message);
        }

        return true;
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     * @throws \Smalot\Cups\CupsException
     */
    public function restart(JobInterface $job)
    {
        $request = $this->prepareRestartRequest($job->getUri());
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);

        if ($result->getStatusCode() != 'successfull-ok') {
            $message = $result->getStatusMessage() ?:$result->getStatusCode();
            throw new CupsException($message);
        }

        return true;
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
    protected function prepareLoadAttributesRequest($uri, $subset = false, $attributesGroup = 'all')
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

    /**
     * @param \Smalot\Cups\Model\JobInterface $job
     * @param $item
     *
     * @return \Smalot\Cups\Model\JobInterface
     */
    protected function fillAttributes(JobInterface $job, $item)
    {
        $job->setId($item['job-id'][0]);
        $job->setUri($item['job-uri'][0]);
        $job->setName($item['job-name'][0]);
        $job->setPrinterUri($item['job-printer-uri'][0]);
        $job->setUsername($item['job-originating-user-name'][0]);
        $job->setState($item['job-state'][0]);
        $job->setStateReason($item['job-state-reasons'][0]);

        if (isset($item['number-up'][0])) {
            $job->setCopies($item['number-up'][0]);
        }

        // Merge with attributes already set.
        $attributes = $job->getAttributes();
        $attributes += $item;
        $job->setAttributes($attributes);

        return $job;
    }
}
