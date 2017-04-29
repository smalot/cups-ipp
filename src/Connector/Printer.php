<?php

namespace Smalot\Cups\Connector;

use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;
use Smalot\Cups\Transport\Response as CupsResponse;

/**
 * Class Printer
 *
 * @package Smalot\Cups\Connector
 */
class Printer extends ConnectorAbstract
{
    use CharsetAware;
    use LanguageAware;

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
        $this->client = $client;

        $this->setCharset('us-ascii');
        $this->setLanguage('en-us');
    }

    /**
     * @param array $attributes
     *
     * @return CupsResponse
     */
    public function getList($attributes = [])
    {
        // Charset
        $charset = strtolower($this->getCharset());
        $meta_charset = chr(0x47) // charset type | value-tag
          .chr(0x00).chr(0x12) // name-length
          ."attributes-charset" // attributes-charset | name
          .$this->getStringLength($charset) // value-length
          .$charset; // value

        // Language
        $language = strtolower($this->getLanguage());
        $meta_language = chr(0x48) // natural-language type | value-tag
          .chr(0x00).chr(0x1B) //  name-length
          ."attributes-natural-language" //attributes-natural-language
          .$this->getStringLength($language) // value-length
          .$language; // value

        // Operation ID
        $operation_id = rand(1000, 9999);
        $meta_operation_id = $this->buildInteger($operation_id);

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
          .$meta_operation_id //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$meta_charset
          .$meta_language
          .$meta_attributes
          .chr(0x03);

        $headers = ['Content-Type' => 'application/ipp',];
        $request = new \GuzzleHttp\Psr7\Request('POST', '/', $headers, $content);
        $response = $this->client->sendRequest($request);

        return CupsResponse::parseResponse($response);
    }
}
