<?php

namespace EngineIoParser;

/**
 * Class Utf8
 *
 * @method static mixed encode(string $string, array $opts)
 * @method static mixed decode(string $byteString, array $opts)
 *
 * @package EngineIoParser
 */
class Utf8
{
    public const VERSION = '2.1.2';

    private static $byteArray;

    private static $byteCount;

    private static $byteIndex;

    private static function jsCharCodeAt($str, $index)
    {
        $utf16 = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');

        return ord($utf16[$index * 2]) + (ord($utf16[$index * 2 + 1]) << 8);
    }

    private static function jsStringFromCharCode($codes)
    {
        if (is_scalar($codes)) $codes = func_get_args();
        $str = '';
        foreach ($codes as $code) {
            preg_match_all("/(\d{2,5})/", $code, $a);
            $a = $a[0];
            $utf = '';
            foreach ($a as $dec) {
                if ($dec < 128) {
                    $utf .= chr($dec);
                } else if ($dec < 2048) {
                    $utf .= chr(192 + (($dec - ($dec % 64)) / 64));
                    $utf .= chr(128 + ($dec % 64));
                } else {
                    $utf .= chr(224 + (($dec - ($dec % 4096)) / 4096));
                    $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
                    $utf .= chr(128 + ($dec % 64));
                }
            }

            $str .= $utf;
        }

        return $str;
    }

    // Taken from https://mths.be/punycode
    private static function ucs2decode($string)
    {
        $output = [];
        $counter = 0;
        $length = mb_strlen($string);
        $value = null;
        $extra = null;

        while ($counter < $length) {
            $value = self::jsCharCodeAt($string, $counter++);
            if ($value >= 0xD800 && $value <= 0xDBFF && $counter < $length) {
                // high surrogate, and there is a next character
                $extra = self::jsCharCodeAt($string, $counter++);
                if (($extra & 0xFC00) == 0xDC00) { // low surrogate
                    array_push($output, (($value & 0x3FF) << 10) + ($extra & 0x3FF) + 0x10000);
                } else {
                    // unmatched surrogate; only append this code unit, in case the next
                    // code unit is the high surrogate of a surrogate pair
                    array_push($output, $value);
                    $counter--;
                }
            } else {
                array_push($output, $value);
            }
        }

        return $output;
    }

    // Taken from https://mths.be/punycode
    private static function ucs2encode($array)
    {
        $length = count($array);
        $index = -1;
        $value = null;
        $output = '';
        while (++$index < $length) {
            $value = $array[$index];
            if ($value > 0xFFFF) {
                $value -= 0x10000;
                $output .= self::jsStringFromCharCode(self::treeRightArrow($value, 10) & 0x3FF | 0xD800);
                $value = 0xDC00 | $value & 0x3FF;
            }
            $output .= self::jsStringFromCharCode($value);
        }

        return $output;
    }

    private static function checkScalarValue($codePoint, $strict)
    {
        if ($codePoint >= 0xD800 && $codePoint <= 0xDFFF) {
            if ($strict) {
                throw new \RuntimeException(
                    'Lone surrogate U+' . $codePoint . ' is not a scalar value'
                );
            }

            return false;
        }

        return true;
    }

    /*--------------------------------------------------------------------------*/

    private static function createByte($codePoint, $shift)
    {
        return self::jsStringFromCharCode((($codePoint >> $shift) & 0x3F) | 0x80);
    }

    private static function encodeCodePoint($codePoint, $strict)
    {
        if (($codePoint & 0xFFFFFF80) == 0) { // 1-byte sequence
            return self::jsStringFromCharCode($codePoint);
        }
        $symbol = '';
        if (($codePoint & 0xFFFFF800) == 0) { // 2-byte sequence
            $symbol = self::jsStringFromCharCode((($codePoint >> 6) & 0x1F) | 0xC0);
        } else if (($codePoint & 0xFFFF0000) == 0) { // 3-byte sequence
            if (!self::checkScalarValue($codePoint, $strict)) {
                $codePoint = 0xFFFD;
            }
            $symbol = self::jsStringFromCharCode((($codePoint >> 12) & 0x0F) | 0xE0);
            $symbol .= self::createByte($codePoint, 6);
        } else if (($codePoint & 0xFFE00000) == 0) { // 4-byte sequence
            $symbol = self::jsStringFromCharCode((($codePoint >> 18) & 0x07) | 0xF0);
            $symbol .= self::createByte($codePoint, 12);
            $symbol .= self::createByte($codePoint, 6);
        }
        $symbol .= self::jsStringFromCharCode(($codePoint & 0x3F) | 0x80);

        return $symbol;
    }

    private static function utf8encode($string, $opts)
    {
        $opts = empty($opts) ? [] : $opts;
        $strict = false !== $opts['strict'];

        $codePoints = self::ucs2decode($string);
        $length = count($codePoints);
        $index = -1;
        $codePoint = null;
        $byteString = '';
        while (++$index < $length) {
            $codePoint = $codePoints[$index];
            $byteString .= self::encodeCodePoint($codePoint, $strict);
        }

        return $byteString;
    }

    /*--------------------------------------------------------------------------*/

    private static function readContinuationByte()
    {
        if (self::$byteIndex >= self::$byteCount) {
            throw new \RuntimeException('Invalid byte index');
        }

        $continuationByte = self::$byteArray[self::$byteIndex] & 0xFF;
        self::$byteIndex++;

        if (($continuationByte & 0xC0) == 0x80) {
            return $continuationByte & 0x3F;
        }

        // If we end up here, it’s not a continuation byte
        throw new \RuntimeException('Invalid continuation byte');
    }

    private static function decodeSymbol($strict)
    {
        $byte1 = null;
        $byte2 = null;
        $byte3 = null;
        $byte4 = null;
        $codePoint = null;

        if (self::$byteIndex > self::$byteCount) {
            throw new \RuntimeException('Invalid byte index');
        }

        if (self::$byteIndex == self::$byteCount) {
            return false;
        }

        // Read first byte
        $byte1 = self::$byteArray[self::$byteIndex] & 0xFF;
        self::$byteIndex++;

        // 1-byte sequence (no continuation bytes)
        if (($byte1 & 0x80) == 0) {
            return $byte1;
        }

        // 2-byte sequence
        if (($byte1 & 0xE0) == 0xC0) {
            $byte2 = self::readContinuationByte();
            $codePoint = (($byte1 & 0x1F) << 6) | $byte2;
            if ($codePoint >= 0x80) {
                return $codePoint;
            } else {
                throw new \RuntimeException('Invalid continuation byte');
            }
        }

        // 3-byte sequence (may include unpaired surrogates)
        if (($byte1 & 0xF0) == 0xE0) {
            $byte2 = self::readContinuationByte();
            $byte3 = self::readContinuationByte();
            $codePoint = (($byte1 & 0x0F) << 12) | ($byte2 << 6) | $byte3;
            if ($codePoint >= 0x0800) {
                return self::checkScalarValue($codePoint, $strict) ? $codePoint : 0xFFFD;
            } else {
                throw new \RuntimeException('Invalid continuation byte');
            }
        }

        // 4-byte sequence
        if (($byte1 & 0xF8) == 0xF0) {
            $byte2 = self::readContinuationByte();
            $byte3 = self::readContinuationByte();
            $byte4 = self::readContinuationByte();
            $codePoint = (($byte1 & 0x07) << 0x12) | ($byte2 << 0x0C) |
                ($byte3 << 0x06) | $byte4;
            if ($codePoint >= 0x010000 && $codePoint <= 0x10FFFF) {
                return $codePoint;
            }
        }

        throw new \RuntimeException('Invalid UTF-8 detected');
    }

    private static function utf8decode($byteString, $opts)
    {
        $opts = empty($opts) ? [] : $opts;
        $strict = false !== $opts['strict'];

        self::$byteArray = self::ucs2decode($byteString);
        self::$byteCount = count(self::$byteArray);
        self::$byteIndex = 0;
        $codePoints = [];
        $tmp = null;
        while (($tmp = self::decodeSymbol($strict)) !== false) {
            array_push($codePoints, $tmp);
        }

        return self::ucs2encode($codePoints);
    }

    /**
     * Unsigned right move： >>>
     *
     * @param $value
     * @param $length
     *
     * @return int
     */
    private static function treeRightArrow($value, $length)
    {
        if ($value > 0) {
            return $value >> $length;
        } else {
            $c = 2147483647 >> ($length - 1);

            return $c & ($value >> $length);
        }
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $method = "utf8{$name}";

        if (!method_exists(self::class, $method)) {
            throw new \RuntimeException(sprintf("undefind %s::%s()", __CLASS__, $method));
        }

        return call_user_func([static::class, $method], ...$arguments);
    }
}