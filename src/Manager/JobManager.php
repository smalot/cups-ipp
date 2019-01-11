<?php

namespace Smalot\Cups\Manager;

use Http\Client\HttpClient;
use Smalot\Cups\Builder\Builder;
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
        $request = $this->prepareGetListRequest($printer, $myJobs, $limit, $whichJobs, $subset);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
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
            $request = $this->prepareReloadAttributesRequest($job, $subset, $attributesGroup);
            $response = $this->client->sendRequest($request);
            $result = $this->parseResponse($response);
            $values = $result->getValues();

            if (isset($values['job-attributes'][0])) {
                $this->fillAttributes($job, $values['job-attributes'][0]);
            }
        }

        return $job;
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     * @param JobInterface $job
     * @param int $timeout
     *
     * @return bool
     */
    public function send(PrinterInterface $printer, JobInterface $job, $timeout = 60)
    {
        // Create job.
        $request = $this->prepareSendRequest($printer, $job, $timeout);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
        $values = $result->getValues();

        $success = false;

        if ($result->getStatusCode() == 'successfull-ok') {
            $job = $this->fillAttributes($job, $values['job-attributes'][0]);
            $job->setPrinterUri($printer->getUri());

            $success = (count($job->getContent()) > 0);

            // Send parts.
            $content = $job->getContent();
            $count = count($job->getContent());

            foreach ($content as $part) {
                $request = $this->prepareSendPartRequest($job, $part, !(--$count));
                $response = $this->client->sendRequest($request);
                $result = $this->parseResponse($response);

                if ($result->getStatusCode() != 'successfull-ok') {
                    $success = false;
                    break;
                }
            }
        }

        return $success;
    }

    /**
     * @param \Smalot\Cups\Model\JobInterface $job
     * @param array $update
     * @param array $delete
     *
     * @return bool
     */
    public function update(JobInterface $job, $update = [], $delete = [])
    {
        $request = $this->prepareUpdateRequest($job, $update, $delete);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return ($result->getStatusCode() == 'successfull-ok');
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     */
    public function cancel(JobInterface $job)
    {
        $request = $this->prepareCancelRequest($job);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

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
        $request = $this->prepareReleaseRequest($job);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return ($result->getStatusCode() == 'successfull-ok');
    }

    /**
     * @param JobInterface $job
     * @param string $until
     * Can be:
     * - no-hold
     * - day-time
     * - evening
     * - night
     * - weekend
     * - second-shift
     * - third-shift
     *
     * @return bool
     */
    public function hold(JobInterface $job, $until = 'indefinite')
    {
        $request = $this->prepareHoldRequest($job, $until);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

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
        $request = $this->prepareRestartRequest($job);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return ($result->getStatusCode() == 'successfull-ok');
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     * @param bool $myJobs
     * @param int $limit
     * @param string $whichJobs
     * @param bool $subset
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareGetListRequest(
      PrinterInterface $printer,
      $myJobs = true,
      $limit = 0,
      $whichJobs = 'not-completed',
      $subset = false
    ) {
        $operationId = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printerUri = $this->buildProperty('printer-uri', $printer->getUri());
        $metaLimit = $this->buildProperty('limit', $limit, true);
        $metaMyJobs = $this->buildProperty('my-jobs', $myJobs, true);

        if ($whichJobs == 'completed') {
            $metaWhichJobs = $this->buildProperty('which-jobs', $whichJobs, true);
        } else {
            $metaWhichJobs = '';
        }

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x0A) // Get-Jobs | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$username
          .$printerUri
          .$metaLimit
          .$metaWhichJobs
          .$metaMyJobs;

        if ($subset) {
            $attributesGroup = [
              'job-uri',
              'job-name',
              'job-state',
              'job-state-reason',
            ];

            $content .= $this->buildProperty('requested-attributes', $attributesGroup);
        } else {
            // Cups 1.4.4 doesn't return much of anything without this.
            $content .= $this->buildProperty('requested-attributes', 'all');
        }

        $content .= chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param \Smalot\Cups\Model\JobInterface $job
     * @param bool $subset
     * @param string $attributesGroup
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareReloadAttributesRequest(JobInterface $job, $subset = false, $attributesGroup = 'all')
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildProperty('job-uri', $job->getUri());

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x09) // Get-Job-Attributes | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$jobUri
          .$username;

        if ($subset) {
            $attributesGroup = [
              'job-uri',
              'job-name',
              'job-state',
              'job-state-reason',
            ];

            $content .= $this->buildProperty('requested-attributes', $attributesGroup);
        } elseif ($attributesGroup) {
            switch ($attributesGroup) {
                case 'job-template':
                    break;
                case 'job-description':
                    break;
                case 'all':
                    break;
                default:
                    trigger_error('Invalid attribute group: "'.$attributesGroup.'"', E_USER_NOTICE);
                    $attributesGroup = '';
                    break;
            }

            $content .= $this->buildProperty('requested-attributes', $attributesGroup);
        }
        $content .= chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param \Smalot\Cups\Model\JobInterface $job
     * @param array $update
     * @param array $delete
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareUpdateRequest(JobInterface $job, $update = [], $delete = [])
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildProperty('job-uri', $job->getUri());
        $copies = $this->buildProperty('copies', $job->getCopies());
        $sides = $this->buildProperty('sides', $job->getSides());
        $pageRanges = $this->buildPageRanges($job->getPageRanges());

        $jobAttributes = $this->buildProperties($update);

        $deletedAttributes = '';

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x14) // Set-Job-Attributes | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$jobUri
          .$username
          .chr(0x02) // start job-attributes
          .$jobAttributes // setteds by setAttribute($attribute,$value)
          .$copies
          .$sides
          .$pageRanges
          .$deletedAttributes
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     * @param JobInterface $job
     * @param int $timeout
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareSendRequest(PrinterInterface $printer, JobInterface $job, $timeout = 60)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $printerUri = $this->buildProperty('printer-uri', $printer->getUri());
        $jobName = $this->buildProperty('job-name', $job->getName());
        $fidelity = $this->buildProperty('ipp-attribute-fidelity', $job->getFidelity());
        $timeoutAttribute = $this->buildProperty('multiple-operation-time-out', $timeout);
        $copies = $this->buildProperty('copies', $job->getCopies());
        $sides = $this->buildProperty('sides', $job->getSides());
        $pageRanges = $this->buildPageRanges($job->getPageRanges());

        // todo
        $operationAttributes = '';//$this->buildOperationAttributes();
        $jobAttributes = $this->buildProperties($job->getAttributes());

        $content = $this->getVersion() // 1.1  | version-number
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
     * @param \Smalot\Cups\Model\JobInterface $job
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareCancelRequest(JobInterface $job)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $requestBodyMalformed = '';
        $message = '';

        $content = $this->getVersion() // 1.1  | version-number
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
     * @param \Smalot\Cups\Model\JobInterface $job
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareReleaseRequest(JobInterface $job)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $message = '';

        $content = $this->getVersion() // 1.1  | version-number
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
     * @param \Smalot\Cups\Model\JobInterface $job
     * @param string $until
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareHoldRequest(JobInterface $job, $until = 'indefinite')
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildProperty('job-uri', $job->getUri());

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
          .$this->builder->formatStringLength('job-hold-until')
          .'job-hold-until'
          .$this->builder->formatStringLength($until)
          .$until;

        $content = $this->getVersion() // 1.1  | version-number
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
     * @param \Smalot\Cups\Model\JobInterface $job
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareRestartRequest(JobInterface $job)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $jobUri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $message = '';

        $content = $this->getVersion() // 1.1  | version-number
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
     * @param array $part
     * @param bool $isLast
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareSendPartRequest(JobInterface $job, $part, $isLast = false)
    {
        $operationId = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $jobUri = $this->buildProperty('job-uri', $job->getUri());
        $documentName = $this->buildProperty('document-name', $part['name']);
        $fidelity = $this->buildProperty('ipp-attribute-fidelity', $job->getFidelity(), true);
        $mimeMediaType = $this->buildProperty('document-format', $part['mimeType'], true);

        // @todo
        $operationAttributes = '';//$this->buildOperationAttributes();
        $lastDocument = $this->buildProperty('last-document', $isLast);

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x06) // Send-Document | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$jobUri
          .$username
          .$documentName
          .$fidelity
          .$mimeMediaType
          .$operationAttributes
          .$lastDocument
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        if ($part['type'] == Job::CONTENT_FILE) {
            $content .= $part['binary'];
        } else {
            $content .= chr(0x16); // datahead
            $content .= $part['text'];
            $content .= chr(0x0c); // datatail
        }

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/printers/', $headers, $content);
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

        if (isset($item['page-ranges'][0])) {
            $job->setPageRanges($item['page-ranges'][0]);
        }

        unset($item['job-id']);
        unset($item['job-uri']);
        unset($item['job-name']);
        unset($item['job-state']);
        unset($item['job-state-reasons']);
        unset($item['number-up']);
        unset($item['page-range']);
        unset($item['job-printer-uri']);
        unset($item['job-originating-user-name']);

        // Merge with attributes already set.
        $attributes = $job->getAttributes();
        $attributes += $item;
        $job->setAttributes($attributes);

        return $job;
    }

    /**
     * @param string $pageRanges
     *
     * @return string
     */
    protected function buildPageRanges($pageRanges)
    {
        $pageRanges = trim(str_replace('-', ':', $pageRanges));
        $pageRanges = explode(',', $pageRanges);

        return $this->buildProperty('page-ranges', $pageRanges);
    }
}
