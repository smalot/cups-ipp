<?php

namespace Smalot\Cups\Transport;

use Psr\Http\Message\ResponseInterface;

/**
 * Class Response
 *
 * @package Smalot\Cups\Transport
 */
class Response
{

    /**
     * @var string
     */
    protected $ippVersion;

    /**
     * @var string
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $requestId;

    /**
     * @var mixed
     */
    protected $body;

    /**
     * Response constructor.
     */
    public function __construct($ippVersion, $statusCode, $requestId, $body)
    {
        $this->ippVersion = $ippVersion;
        $this->statusCode = $statusCode;
        $this->requestId = $requestId;
        $this->body = $body;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public static function parseResponse(ResponseInterface $response)
    {
        $offset = 0;
        $content = $response->getBody()->getContents();

        $ippVersion = self::parseIppVersion($content, $offset);
        $statusCode = self::parseStatusCode($content, $offset);
        $requestId = self::parseRequestID($content, $offset);

        return new self($ippVersion, $statusCode, $requestId, '');
    }

    /**
     * @param string $content
     * @param int $offset
     *
     * @return string
     */
    protected static function parseIppVersion($content, &$offset)
    {
        $text = (ord($content[$offset]) * 256) + ord($content[$offset + 1]);
        $offset += 2;

        switch ($text) {
            case 0x0101:
                $ippVersion = '1.1';
                break;

            default:
                $ippVersion =
                  sprintf(
                    '%u.%u (Unknown)',
                    ord($content[$offset]) * 256,
                    ord($content[$offset + 1])
                  );
                break;
        }

        return $ippVersion;
    }

    /**
     * @param string $content
     * @param int $offset
     *
     * @return string
     */
    protected static function parseStatusCode($content, &$offset)
    {
        $status_code = (ord($content[$offset]) * 256) + ord($content[$offset + 1]);
        $status = 'NOT PARSED';
        $offset += 2;

        if (strlen($content) < $offset) {
            return false;
        }

        if ($status_code < 0x00FF) {
            $status = 'successfull';
        } elseif ($status_code < 0x01FF) {
            $status = 'informational';
        } elseif ($status_code < 0x02FF) {
            $status = 'redirection';
        } elseif ($status_code < 0x04FF) {
            $status = 'client-error';
        } elseif ($status_code < 0x05FF) {
            $status = 'server-error';
        }

        switch ($status_code) {
            case 0x0000:
                $status = 'successfull-ok';
                break;

            case 0x0001:
                $status = 'successful-ok-ignored-or-substituted-attributes';
                break;

            case 0x002:
                $status = 'successful-ok-conflicting-attributes';
                break;

            case 0x0400:
                $status = 'client-error-bad-request';
                break;

            case 0x0401:
                $status = 'client-error-forbidden';
                break;

            case 0x0402:
                $status = 'client-error-not-authenticated';
                break;

            case 0x0403:
                $status = 'client-error-not-authorized';
                break;

            case 0x0404:
                $status = 'client-error-not-possible';
                break;

            case 0x0405:
                $status = 'client-error-timeout';
                break;

            case 0x0406:
                $status = 'client-error-not-found';
                break;

            case 0x0407:
                $status = 'client-error-gone';
                break;

            case 0x0408:
                $status = 'client-error-request-entity-too-large';
                break;

            case 0x0409:
                $status = 'client-error-request-value-too-long';
                break;

            case 0x040A:
                $status = 'client-error-document-format-not-supported';
                break;

            case 0x040B:
                $status = 'client-error-attributes-or-values-not-supported';
                break;

            case 0x040C:
                $status = 'client-error-uri-scheme-not-supported';
                break;

            case 0x040D:
                $status = 'client-error-charset-not-supported';
                break;

            case 0x040E:
                $status = 'client-error-conflicting-attributes';
                break;

            case 0x040F:
                $status = 'client-error-compression-not-supported';
                break;

            case 0x0410:
                $status = 'client-error-compression-error';
                break;

            case 0x0411:
                $status = 'client-error-document-format-error';
                break;

            case 0x0412:
                $status = 'client-error-document-access-error';
                break;

            case 0x0413: // RFC3380
                $status = 'client-error-attributes-not-settable';
                break;

            case 0x0500:
                $status = 'server-error-internal-error';
                break;

            case 0x0501:
                $status = 'server-error-operation-not-supported';
                break;

            case 0x0502:
                $status = 'server-error-service-unavailable';
                break;

            case 0x0503:
                $status = 'server-error-version-not-supported';
                break;

            case 0x0504:
                $status = 'server-error-device-error';
                break;

            case 0x0505:
                $status = 'server-error-temporary-error';
                break;

            case 0x0506:
                $status = 'server-error-not-accepting-jobs';
                break;

            case 0x0507:
                $status = 'server-error-busy';
                break;

            case 0x0508:
                $status = 'server-error-job-canceled';
                break;

            case 0x0509:
                $status = 'server-error-multiple-document-jobs-not-supported';
                break;

            default:
                break;
        }

        return $status;
    }

    /**
     * @param string $content
     * @param int $offset
     *
     * @return int
     */
    protected static function parseRequestID($content, &$offset)
    {
        $requestId = self::interpretInteger(substr($content, $offset, 4));
        $offset += 4;

        return $requestId;
    }

    protected static function parseBody($content, &$offset)
    {
        $j = -1;
        $index = 0;
        $body = [];
        $response_completed = [];

        for ($i = $offset; $i < strlen($content); $i = $offset) {


            $tag = ord($content[$offset]);


            if ($tag > 0x0F) {

                self::_readAttribute($j);
                $index++;
                continue;
            }

            switch ($tag) {
                case 0x01:
                    $j += 1;
                    $body[$j]['attributes'] = "operation-attributes";
                    $index = 0;
                    $offset += 1;
                    break;
                case 0x02:
                    $j += 1;
                    $body[$j]['attributes'] = "job-attributes";
                    $index = 0;
                    $offset += 1;
                    break;
                case 0x03:
                    $j += 1;
                    $body[$j]['attributes'] = "end-of-attributes";
                    $response_completed[(count($response_completed) - 1)] = "completed";

                    return;
                case 0x04:
                    $j += 1;
                    $body[$j]['attributes'] = "printer-attributes";
                    $index = 0;
                    $offset += 1;
                    break;
                case 0x05:
                    $j += 1;
                    $body[$j]['attributes'] = "unsupported-attributes";
                    $index = 0;
                    $offset += 1;
                    break;
                default:
                    $j += 1;
                    $body[$j]['attributes'] = sprintf(
                      _("0x%x (%u) : attributes tag Unknown (reserved for future versions of IPP"),
                      $tag,
                      $tag
                    );
                    $index = 0;
                    $offset += 1;
                    break;
            }
        }

        return;
    }

    /**
     * @param string $value
     *
     * @return int
     */
    protected static function interpretInteger($value)
    {
        // They are _signed_ integers.
        $value_parsed = 0;

        for ($i = strlen($value); $i > 0; $i--) {
            $value_parsed += ((1 << (($i - 1) * 8)) * ord($value[strlen($value) - $i]));
        }

        if ($value_parsed >= 2147483648) {
            $value_parsed -= 4294967296;
        }

        return $value_parsed;
    }

    
}
