<?php

namespace Smalot\Cups\Manager;

use Smalot\Cups\Model\Printer;
use Smalot\Cups\Model\PrinterInterface;
use Smalot\Cups\Transport\Response as CupsResponse;
use GuzzleHttp\Psr7\Request;

/**
 * Class Printer
 *
 * @package Smalot\Cups\Manager
 */
class PrinterManager extends ManagerAbstract
{

    /**
     * @param string $uri
     *
     * @return \Smalot\Cups\Model\Printer|false
     */
    public function findByUri($uri)
    {
        $printer = new Printer();
        $printer->setUri($uri);

        $this->reloadAttributes($printer);

        if ($printer->getName()) {
            return $printer;
        } else {
            return false;
        }
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     *
     * @return \Smalot\Cups\Model\PrinterInterface
     */
    public function reloadAttributes(PrinterInterface $printer)
    {
        $request = $this->prepareReloadAttributesRequest($printer);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
        $values = $result->getValues();

        if (isset($values['printer-attributes'][0])) {
            $this->fillAttributes($printer, $values['printer-attributes'][0]);
        }

        return $printer;
    }

    /**
     * @return \Smalot\Cups\Model\Printer|null
     */
    public function getDefault()
    {
        $request = $this->prepareGetDefaultRequest(['all']);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
        $values = $result->getValues();

        $printer = null;

        if (isset($values['printer-attributes'][0])) {
            $printer = new Printer();
            $this->fillAttributes($printer, $values['printer-attributes'][0]);
        }

        return $printer;
    }

    /**
     * @param array $attributes
     *
     * @return \Smalot\Cups\Model\Printer[]
     */
    public function getList($attributes = [])
    {
        $request = $this->prepareGetListRequest($attributes);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
        $values = $result->getValues();
        $list = [];

        if (!empty($values['printer-attributes'])) {
            foreach ($values['printer-attributes'] as $item) {
                $printer = new Printer();
                $this->fillAttributes($printer, $item);

                $list[] = $printer;
            }
        }

        return $list;
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     *
     * @return bool
     */
    public function pause(PrinterInterface $printer)
    {
        $request = $this->preparePauseRequest($printer);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Reload attributes to update printer status.
        $this->reloadAttributes($printer);

        return ($result->getStatusCode() == 'successfull-ok');
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     *
     * @return bool
     */
    public function resume(PrinterInterface $printer)
    {
        $request = $this->prepareResumeRequest($printer);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Reload attributes to update printer status.
        $this->reloadAttributes($printer);

        return ($result->getStatusCode() == 'successfull-ok');
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     *
     * @return bool
     */
    public function purge(PrinterInterface $printer)
    {
        $request = $this->preparePurgeRequest($printer);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        return ($result->getStatusCode() == 'successfull-ok');
    }

    /**
     * @param array $attributes
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareGetListRequest($attributes = [])
    {
        $operationId = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();

        $metaAttributes = $this->buildPrinterRequestedAttributes($attributes);

        $content = $this->getVersion() // IPP version 1.1
          .chr(0x40).chr(0x02) // operation:  cups vendor extension: get printers
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$metaAttributes
          .chr(0x03);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/', $headers, $content);
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareReloadAttributesRequest(PrinterInterface $printer)
    {
        $operationId = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printerUri = $this->buildProperty('printer-uri', $printer->getUri());
        // @todo
        $printerAttributes = '';//$this->buildPrinterAttributes();

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x0b) // Print-URI | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$username
          .$printerUri
          .$printerAttributes
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/', $headers, $content);
    }

    /**
     * @param array $attributes
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareGetDefaultRequest($attributes = [])
    {
        $operationId = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();

        $metaAttributes = $this->buildPrinterRequestedAttributes($attributes);

        $content = $this->getVersion() // IPP version 1.1
          .chr(0x40).chr(0x01) // operation:  cups vendor extension: get default printer
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$metaAttributes
          .chr(0x03);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/', $headers, $content);
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function preparePauseRequest(PrinterInterface $printer)
    {
        $operationId = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printerUri = $this->buildProperty('printer-uri', $printer->getUri());

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x10) // Pause-Printer | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$username
          .$printerUri
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/admin/', $headers, $content);
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareResumeRequest(PrinterInterface $printer)
    {
        $operationId = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printerUri = $this->buildProperty('printer-uri', $printer->getUri());

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x11) // Resume-Printer | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$username
          .$printerUri
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/admin/', $headers, $content);
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function preparePurgeRequest(PrinterInterface $printer)
    {
        $operationId = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printerUri = $this->buildProperty('printer-uri', $printer->getUri());
        $purgeJob = $this->buildProperty('purge-jobs', 1);

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x12) // purge-Jobs | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$username
          .$printerUri
          .$purgeJob
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/admin/', $headers, $content);
    }

    /**
     * @todo: move this method into a dedicated builder
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function buildPrinterRequestedAttributes($attributes = [])
    {
        if (empty($attributes)) {
            $attributes = [
              'printer-uri-supported',
              'printer-name',
              'printer-state',
              'printer-location',
              'printer-info',
              'printer-type',
              'printer-icons',
            ];
        }

        $metaAttributes = '';

        for ($i = 0; $i < count($attributes); $i++) {
            if ($i == 0) {
                $metaAttributes .= chr(0x44) // Keyword
                  .$this->builder->formatStringLength('requested-attributes')
                  .'requested-attributes'
                  .$this->builder->formatStringLength($attributes[0])
                  .$attributes[0];
            } else {
                $metaAttributes .= chr(0x44) // Keyword
                  .chr(0x0).chr(0x0) // zero-length name
                  .$this->builder->formatStringLength($attributes[$i])
                  .$attributes[$i];
            }
        }

        return $metaAttributes;
    }

    /**
     * @param \Smalot\Cups\Model\PrinterInterface $printer
     * @param $item
     *
     * @return \Smalot\Cups\Model\PrinterInterface
     */
    protected function fillAttributes(PrinterInterface $printer, $item)
    {
        $printer->setUri($item['printer-uri-supported'][0]);
        $printer->setName($item['printer-name'][0]);
        $printer->setStatus($item['printer-state'][0]);

        // Merge with attributes already set.
        $attributes = $printer->getAttributes();
        foreach ($item as $name => $value) {
            $attributes[$name] = $value;
        }
        $printer->setAttributes($attributes);

        return $printer;
    }
}
