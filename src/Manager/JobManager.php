<?php

namespace Smalot\Cups\Manager;

use Http\Client\HttpClient;
use Smalot\Cups\CupsException;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\JobInterface;
use Smalot\Cups\Model\PrinterInterface;
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
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     * @param bool $myJobs
     * @param int $limit
     * @param string $whichJobs
     * @param bool $subset
     *
     * @return \Smalot\Cups\Model\JobInterface[]
     */
    public function getList(
      PrinterInterface $printer,
      $myJobs = true,
      $limit = 0,
      $whichJobs = 'not-completed',
      $subset = false
    ) {
        $request = $this->prepareGetListRequest($printer->getUri(), $myJobs, $limit, $whichJobs, $subset);
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);
        $values = $result->getValues();

        $list = [];

        if (!empty($values['job-attributes'])) {
            foreach ($values['job-attributes'] as $item) {
                $job = new Job();
                $this->fillAttributes($job, $item);

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
    public function reloadAttributes(JobInterface $job, $subset = false, $attributesGroup = 'all')
    {
        if ($job->getUri()) {
            $request = $this->prepareReloadAttributesRequest($job->getUri(), $subset, $attributesGroup);
            $response = $this->client->sendRequest($request);
            $result = CupsResponse::parseResponse($response);
            $values = $result->getValues();

            if (isset($values['job-attributes'][0])) {
                $this->fillAttributes($job, $values['job-attributes'][0]);
            }
        }

        return $job;
    }

    /**
     * @param PrinterInterface $printer
     * @param JobInterface $job
     * @param int $timeout
     *
     * @return bool
     */
    public function create(PrinterInterface $printer, JobInterface $job, $timeout = 60)
    {
        // Create job.
        $request = $this->prepareCreateRequest($printer->getUri(), $job);
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);
        $values = $result->getValues();

        $success = false;

        if ($result->getStatusCode() == 'successfull-ok') {
            $job = $this->fillAttributes($job, $values['job-attributes'][0]);
            $job->setPrinterUri($printer->getUri());

            $success = (count($job->getContent()) > 0);

            // Send parts.
            foreach ($job->getContent() as $part) {
                //                $request = $this->prepareSendPartRequest($job->getUri(), $part);
                //                $response = $this->client->sendRequest($request);
                //                $result = CupsResponse::parseResponse($response);
                //
                //                if ($result->getStatusCode() == 'successfull-ok') {
                //                    $success = false;
                //                }
            }

            // Commit job.
            if ($success) {
                //                $request = $this->prepareCreateRequest($job->getUri());
                //                $response = $this->client->sendRequest($request);
                //                $result = CupsResponse::parseResponse($response);
            } else {
                // Cancel
            }
        }

        // Refresh attributes.
        $this->reloadAttributes($job);

        return $success;
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     */
    public function cancel(JobInterface $job)
    {
        $request = $this->prepareCancelRequest($job->getUri());
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return ($result->getStatusCode() == 'successfull-ok');
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     */
    public function release(JobInterface $job)
    {
        $request = $this->prepareReleaseRequest($job->getUri());
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return ($result->getStatusCode() == 'successfull-ok');
    }

    /**
     * @param JobInterface $job
     * @param string $until
     *
     * @return bool
     */
    public function hold(JobInterface $job, $until = 'indefinite')
    {
        $request = $this->prepareHoldRequest($job->getUri(), $until);
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return ($result->getStatusCode() == 'successfull-ok');
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     */
    public function restart(JobInterface $job)
    {
        $request = $this->prepareRestartRequest($job->getUri());
        $response = $this->client->sendRequest($request);
        $result = CupsResponse::parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return ($result->getStatusCode() == 'successfull-ok');
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
    protected function prepareReloadAttributesRequest($uri, $subset = false, $attributesGroup = 'all')
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
     * @param JobInterface $job
     * @param int $timeout
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareCreateRequest($uri, JobInterface $job, $timeout = 60)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername($job->getUsername());
        $printerUri = $this->buildPrinterURI($uri);
        $jobName = $this->buildJobName($job->getName());
        $fidelity = $this->buildFidelity($job->getFidelity());
        $timeoutAttribute = $this->buildTimeout($timeout);
        $copies = $this->buildCopies($job->getCopies());
        $sides = $this->buildSides($job->getSides());
        $pageRanges = $this->buildPageRanges($job->getPageRanges());

        $operationAttributes = $this->buildOperationAttributes();
        $jobAttributes = $this->buildJobAttributes();

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x05) // Create-Job | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$printerUri
          .$username
          .$jobName
          .$fidelity
          .$timeoutAttribute
          .$operationAttributes
          .chr(0x02) // start job-attributes | job-attributes-tag
          .$copies
          .$sides
          .$pageRanges
          .$jobAttributes
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/printers/', $headers, $content);
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
        $name = empty($item['job-name'][0]) ? 'Job #'.$job->getId() : $item['job-name'][0];
        $copies = empty($item['number-up'][0]) ? 1 : $item['number-up'][0];

        $job->setId($item['job-id'][0]);
        $job->setUri($item['job-uri'][0]);
        $job->setName($name);
        $job->setState($item['job-state'][0]);
        $job->setStateReason($item['job-state-reasons'][0]);
        $job->setCopies($copies);

        if (isset($item['job-printer-uri'][0])) {
            $job->setPrinterUri($item['job-printer-uri'][0]);
        }
        if (isset($item['job-originating-user-name'][0])) {
            $job->setUsername($item['job-originating-user-name'][0]);
        }

        // Merge with attributes already set.
        $attributes = $job->getAttributes();
        $attributes += $item;
        $job->setAttributes($attributes);

        return $job;
    }

    /**
     * @param string $jobName
     * @param bool $absolute
     *
     * @return string
     */
    protected function buildJobName($jobName, $absolute = false)
    {
        static $counter = 0;

        $value = '';

        if ($jobName) {
            $jobName .= ($absolute ? '' : date('-H:i:s-').str_pad(++$counter, 4, '0', STR_PAD_LEFT));
            $value = chr(0x42) // nameWithoutLanguage type || value-tag
              .chr(0x00).chr(0x08) //  name-length
              .'job-name' //  job-name || name
              .$this->getStringLength($jobName) // value-length
              .$jobName; // value
        }

        return $value;
    }

    /**
     * @param int $fidelity
     *
     * @return string
     */
    protected function buildFidelity($fidelity)
    {
        $value = '';

        if ($fidelity) {
            $value = chr(0x22) // boolean type  |  value-tag
              .chr(0x00).chr(0x16) //                  name-length
              .'ipp-attribute-fidelity' // ipp-attribute-fidelity | name
              .chr(0x00).chr(0x01) //  value-length
              .chr(0x01); //  true | value
        }

        return $value;
    }

    /**
     * @param int $timeout
     *
     * @return string
     */
    protected function buildTimeout($timeout)
    {
        $value = '';

        if ($timeout) {
            $integer = $this->buildInteger($timeout);
            $value = chr(0x21) // integer
              .$this->getStringLength('multiple-operation-time-out')
              .'multiple-operation-time-out'
              .$this->getStringLength($integer)
              .$integer;
        }

        return $value;
    }

    /**
     * @param int $copies
     *
     * @return string
     */
    protected function buildCopies($copies)
    {
        $value = '';

        if ($copies && $copies > 1) {
            $integer = $this->buildInteger($copies);
            $value = chr(0x21) // integer type | value-tag
              .chr(0x00).chr(0x06) //             name-length
              .'copies' // copies    |             name
              .$this->getStringLength($integer) // value-length
              .$integer;
        }

        return $value;
    }

    /**
     * @param int $sides
     *
     * @return string
     */
    protected function buildSides($sides)
    {
        $value = '';

        if ($sides) {
            switch ($sides) {
                case 2:
                    $sides = 'two-sided-long-edge';
                    break;

                case 3:
                    $sides = 'two-sided-short-edge';
                    break;

                case 1:
                default:
                    $sides = 'one-sided';
                    break;
            }

            $value = chr(0x44) // keyword type | value-tag
              .chr(0x00).chr(0x05) //        name-length
              .'sides' // sides |             name
              .$this->getStringLength($sides) //               value-length
              .$sides; // one-sided |          value
        }

        return $value;
    }

    /**
     * @param string $pageRanges
     *
     * @return string
     */
    protected function buildPageRanges($pageRanges)
    {
        $value = '';

        if ($pageRanges) {
            $pageRanges = trim(str_replace('-', ':', $pageRanges));
            $first = true;
            $ranges = explode(',', $pageRanges);

            foreach ($ranges as $range) {
                $tmp = $this->buildRangeOfInteger($range);

                if ($first) {
                    $value .= $this->tagsTypes['rangeOfInteger']['tag']
                      .$this->getStringLength('page-ranges')
                      .'page-ranges'
                      .$this->getStringLength($tmp)
                      .$tmp;
                } else {
                    $value .= $this->tagsTypes['rangeOfInteger']['tag']
                      .$this->getStringLength('')
                      .$this->getStringLength($tmp)
                      .$tmp;
                    $first = false;
                }
            }
        }

        return $value;
    }

    /**
     * @param string $range
     *
     * @return string
     */
    protected function buildRangeOfInteger($range)
    {
        $integers = preg_split('/[:,]/', $range);

        return $this->buildInteger($integers[0]).$this->buildInteger($integers[0]);
    }
}
