<?php

namespace EngineIoParser;

use EngineIoParser\Enums\PacketEnum;

class Parser
{
    /**
     * Current protocol version.
     */
    public const PROTOCOL = 3;

    public static function getPacketList()
    {
        static $buffer;

        if ($buffer === null) {
            $buffer = PacketEnum::getAllText();
        }

        return $buffer;
    }

    public static function getErr()
    {
        $data = [
            'type' => 'error',
            'data' => 'parser error'
        ];

        return json_encode($data);
    }

    /**
     * Encodes a packet.
     *
     *     <packet type id> [ <data> ]
     *
     * Example:
     *
     *     5hello world
     *     3
     *     4
     *
     * Binary is encoded in an identical principle
     *
     * @api private
     *
     * @param $packet
     * @param $supportsBinary
     * @param $utf8encode
     * @param $callback
     *
     * @return string
     */
    public static function encodePacket($packet, $supportsBinary, $utf8encode, $callback)
    {
        // is function
        if (is_callable($supportsBinary)) {
            $callback = $supportsBinary;
            $supportsBinary = null;
        }

        // is function
        if (is_callable($utf8encode)) {
            $callback = $utf8encode;
            $utf8encode = null;
        }

        /*
              if (Buffer.isBuffer(packet.data)) {
                    return encodeBuffer(packet, supportsBinary, callback);
              } else if (packet.data && (packet.data.buffer || packet.data) instanceof ArrayBuffer) {
                    return encodeBuffer({ type: packet.type, data: arrayBufferToBuffer(packet.data) }, supportsBinary, callback);
              }
        */

        if (is_string($packet['data'])) {
            return self::encodeBuffer($packet, $supportsBinary, $callback);
        } elseif (is_array($packet['data'])) {
//            return self::encodeBuffer($packet, $supportsBinary, $callback);
        }

        // Sending data as a utf-8 string
        $encoded = PacketEnum::getCodeByText($packet['type']);

        // data fragment is optional
        if (isset($packet['data']) && !empty($packet['data'])) {
            $encoded .= $utf8encode ? Utf8::encode($packet['data'], ['strict' => false]) : $packet['data'];
        }

        return $callback('' . $encoded);
    }


    /**
     * Encode Buffer data
     *
     * @param $packet
     * @param $supportsBinary
     * @param $callback
     *
     * @return string
     */
    public static function encodeBuffer($packet, $supportsBinary, $callback)
    {
        if (!$supportsBinary) {
            return self::encodeBase64Packet($packet, $callback);
        }

        $data = $packet['data'];
        $typeBuffer = chr(PacketEnum::getCodeByText($packet['type']));

        return $callback($typeBuffer . $data);
    }

    /**
     * /
     * Encodes a packet with binary data in a base64 string
     *
     * @param $packet , has `type` and `data`
     * @param $callback
     *
     * @return string base64 encoded message
     */
    public static function encodeBase64Packet($packet, $callback)
    {
        $data = is_array($packet['data']) ? ' arrayBufferToBuffer(packet.data)' : $packet['data'];
        $message = 'b' . PacketEnum::getCodeByText($packet['type']);
        $message .= base64_encode($data);

        return $callback($message);
    }
}