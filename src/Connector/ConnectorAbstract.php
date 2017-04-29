<?php


namespace Smalot\Cups\Connector;

use Smalot\Cups\CupsException;

/**
 * Class ConnectorAbstract
 *
 * @package Smalot\Cups\Connector
 */
class ConnectorAbstract
{
    /**
     * @param string $string
     *
     * @return string
     * @throws \Smalot\Cups\CupsException
     */
    protected function getStringLength($string)
    {
        $length = strlen($string);

        if ($length > ((0xFF << 8) + 0xFF)) {
            $message = sprintf(
              'max string length for an ipp meta-information = %d, while here %d',
              ((0xFF << 8) + 0xFF),
              $length
            );

            throw new CupsException($message);
        }

        $int1 = $length & 0xFF;
        $length -= $int1;
        $length = $length >> 8;
        $int2 = $length & 0xFF;

        return chr($int2).chr($int1);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function buildInteger($value)
    {
        if ($value >= 2147483647 || $value < -2147483648) {
            trigger_error(
              _("Values must be between -2147483648 and 2147483647: assuming '0'"),
              E_USER_WARNING
            );

            return chr(0x00).chr(0x00).chr(0x00).chr(0x00);
        }

        $initial_value = $value;
        $int1 = $value & 0xFF;
        $value -= $int1;
        $value = $value >> 8;
        $int2 = $value & 0xFF;
        $value -= $int2;
        $value = $value >> 8;
        $int3 = $value & 0xFF;
        $value -= $int3;
        $value = $value >> 8;
        $int4 = $value & 0xFF; //64bits

        if ($initial_value < 0) {
            $int4 = chr($int4) | chr(0x80);
        } else {
            $int4 = chr($int4);
        }

        $value = $int4.chr($int3).chr($int2).chr($int1);

        return $value;
    }
}
