<?php

namespace Smalot\Cups\Connector;

use Http\Client\HttpClient;
use Smalot\Cups\Transport\Response as CupsResponse;
use GuzzleHttp\Psr7\Request;

/**
 * Class Printer
 *
 * @package Smalot\Cups\Connector
 */
class Printer extends ConnectorAbstract
{

    use CharsetAware;
    use LanguageAware;
    use OperationIdAware;
    use UsernameAware;

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
     * @param array $attributes
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function getList($attributes = [])
    {
        $request = $this->prepareGetListRequest($attributes);
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
     * @param array $attributes
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareGetListRequest($attributes = [])
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();

        // Attributes.
        if (empty($attributes)) {
            $attributes = [
              'printer-uri-supported',
              'printer-location',
              'printer-info',
              'printer-type',
              'color-supported',
            ];
        }

        $meta_attributes = '';
        for ($i = 0; $i < count($attributes); $i++) {
            if ($i == 0) {
                $meta_attributes .= chr(0x44) // Keyword
                  .$this->getStringLength('requested-attributes')
                  .'requested-attributes'
                  .$this->getStringLength($attributes[0])
                  .$attributes[0];
            } else {
                $meta_attributes .= chr(0x44) // Keyword
                  .chr(0x0).chr(0x0) // zero-length name
                  .$this->getStringLength($attributes[$i])
                  .$attributes[$i];
            }
        }

        $content = chr(0x01).chr(0x01) // IPP version 1.1
          .chr(0x40).chr(0x02) // operation:  cups vendor extension: get printers
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$meta_attributes
          .chr(0x03);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/', $headers, $content);
    }

    /**
     * @param string $printerUri
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareGetAttributesRequest($printerUri)
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operationId = $this->buildOperationId();
        $username = $this->buildUsername();
        $printerAttributes = $this->buildPrinterAttributes();

        $content = chr(0x01).chr(0x01) // 1.1  | version-number
          .chr(0x00).chr(0x0b) // Print-URI | operation-id
          .$operationId //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$this->buildPrinterURI($printerUri)
          .$username
          .$printerAttributes
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/', $headers, $content);
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    protected function buildPrinterURI($uri)
    {
        $length = strlen($uri);
        $length = chr($length);

        while (strlen($length) < 2) {
            $length = chr(0x00).$length;
        }

        $metaPrinterUrl = chr(0x45) // uri type | value-tag
          .chr(0x00).chr(0x0B) // name-length
          ."printer-uri" // printer-uri | name
          .$length.$uri;

        return $metaPrinterUrl;
    }
}
