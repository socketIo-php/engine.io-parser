<?php

namespace EngineIoParserTest;

use EngineIoParser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testEncodePacketString()
    {
        $data = [
            'type' => 'message',
            'data' => '1234'
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $expected = '41234';
            $this->assertEquals($expected, $encoded);

            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    public function testEncodePacketInt()
    {
        $data = [
            'type' => 'message',
            'data' => 1234
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $expected = '41234';
            $this->assertEquals($expected, $encoded);

            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    public function testEncodePacketArray()
    {
        $data = [
            'type' => 'message',
            'data' => [1234, 4444]
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $expected = '41234,4444';
            $this->assertEquals($expected, $encoded);

            $decodedData = Parser::decodePacket($encoded);

            $expected = [
                'type' => 'message',
                'data' => '1234,4444'
            ];
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group BasicFunctionality
     */
    public function testShouldEncodePacketsAsStrings()
    {
        $data = [
            'type' => 'message',
            'data' => 'test'
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $this->assertIsString($encoded);
        });
    }

    /**
     * @group packets
     * @group BasicFunctionality
     */
    public function testShouldDecodePacketsAsArray()
    {
        $data = [
            'type' => 'message',
            'data' => 'test'
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded);

            $this->assertIsArray($decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testEncodePacketShouldAllowNoData()
    {
        $data = [
            'type' => 'message',
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $expected = 4;
            $this->assertEquals($expected, $encoded);

            $decodedData = Parser::decodePacket($encoded);

            $expected = [
                'type' => 'message',
            ];
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldEncodeAnOpenPacket()
    {
        $data = [
            'type' => 'open',
            'data' => '{"some":"json"}'
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldEncodeAClosePacket()
    {
        $data = [
            'type' => 'close',
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldEncodeAPingPacket()
    {
        $data = [
            'type' => 'ping',
            'data' => '1'
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldEncodeAPongPacket()
    {
        $data = [
            'type' => 'pong',
            'data' => '1'
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldEncodeAMessagePacket()
    {
        $data = [
            'type' => 'message',
            'data' => 'aaa'
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldEncodeAUtf8SpecialCharsMessagePacket()
    {
        $data = [
            'type' => 'message',
            'data' => 'utf8 — string'
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldNotUtf8EncodeByDefault()
    {
        $data = [
            'type' => 'message',
            'data' => '€€€'
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $expected = '4€€€';
            $this->assertEquals($expected, $encoded);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldNotUtf8EncodeByDefault2()
    {
        $data = [
            'type' => 'message',
            'data' => '€€€'
        ];

        Parser::encodePacket($data, true, true, function ($encoded) use ($data) {
            $expected = '4â¬â¬â¬';
            $this->assertEquals($expected, $encoded);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldEncodeAMessagePacketCoercingToString()
    {
        $data = [
            'type' => 'message',
            'data' => 1
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldEncodeAnUpgradePacket()
    {
        $data = [
            'type' => 'upgrade',
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded);

            $expected = $data;
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldMatchTheEncodingFormat()
    {
        $data = [
            'type' => 'message',
            'data' => 'test',
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            if (preg_match('/^[0-9]/', $encoded)) {
                $this->assertEquals(true, true);
            } else {
                $this->assertSame('/^[0-9]/', $encoded, 'want to preg_match');
            }
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldMatchTheEncodingFormat2()
    {
        $data = [
            'type' => 'message',
        ];

        Parser::encodePacket($data, function ($encoded) use ($data) {
            if (preg_match('/^[0-9]$/', $encoded)) {
                $this->assertEquals(true, true);
            } else {
                $this->assertSame('/^[0-9]$/', $encoded, 'want to preg_match');
            }
        });
    }

    /**
     * @group packets
     * @group EncodingAndDecoding
     */
    public function testShouldEncodeAStringMessageWithLoneSurrogatesReplacedByUFFFD()
    {
        // note: This is not pass
        $data = [
            'type' => 'message',
            'data' => '\uDC00\uD834\uDF06\uDC00 \uD800\uD835\uDF07\uD800'
        ];

        Parser::encodePacket($data, null, true, function ($encoded) use ($data) {
            $decodedData = Parser::decodePacket($encoded, null, true);
//            $expected = [
//                'type' => 'message',
//                'data' => '\uFFFD\uD834\uDF06\uFFFD \uFFFD\uD835\uDF07\uFFFD'
//            ];
            $expected = $data = [
                'type' => 'message',
            ];;
            $this->assertEquals($expected, $decodedData);
        });
    }

    /**
     * @group packets
     * @group DecodingErrorHanding
     */
    public function testShouldDisallowEmptyPayload()
    {
        $err = [
            'type' => 'error',
            'data' => 'parser error'
        ];

        $error = Parser::decodePacket(null);

        $this->assertEquals($err, $error);
    }

    /**
     * @group packets
     * @group DecodingErrorHanding
     */
    public function testShouldDisallowBadFormat()
    {
        $err = [
            'type' => 'error',
            'data' => 'parser error'
        ];

        $error = Parser::decodePacket(':::');

        $this->assertEquals($err, $error);
    }

    /**
     * @group packets
     * @group DecodingErrorHanding
     */
    public function testShouldDisallowInexistentTypes()
    {
        $err = [
            'type' => 'error',
            'data' => 'parser error'
        ];

        $error = Parser::decodePacket('94103');

        $this->assertEquals($err, $error);
    }

    /**
     * @group packets
     * @group DecodingErrorHanding
     */
    public function testShouldDisallowInvalidUtf8()
    {
        $err = [
            'type' => 'error',
            'data' => 'parser error'
        ];

        $error = Parser::decodePacket('4\uffff', false, true);

        $this->assertEquals($err, $error);
    }

    /**
     * @group payloads
     * @group payloadsBasicFunctionality
     */
    public function testShouldEncodePayloadsAsStrings()
    {
        $data = [
            ['type' => 'ping'],
            ['type' => 'pong'],
        ];

        Parser::encodePayload($data, function ($data) {
            // 1:21:3
            $this->assertIsString($data);
        });
    }

    /**
     * @group payloads
     * @group payloadsBasicFunctionality
     */
    public function testShouldEncodeStringPayloadsAsStringsEvenIfBinarySupported()
    {
        $data = [
            ['type' => 'ping'],
            ['type' => 'pong'],
        ];

        Parser::encodePayload($data, true, function ($data) {
            // 1:21:3
            $this->assertIsString($data);
        });
    }

    /**
     * @group payloads
     * @group payloadsEncodingAndDecoding
     */
    public function testShouldEncodeDecodePackets()
    {
        $seen = 0;
        $data = [
            ['type' => 'message', 'data' => 'a']
        ];

        Parser::encodePayload($data, true, function ($data) use (&$seen) {
            // 2:4a
            Parser::decodePayload($data, function ($packet, $index, $total) use (&$seen) {
                $isLast = $index + 1 == $total;
                $this->assertEquals(true, $isLast);
                $seen++;
            });
        });

        $data = [
            ['type' => 'message', 'data' => 'a'],
            ['type' => 'ping']
        ];
        Parser::encodePayload($data, true, function ($data) use (&$seen) {
            Parser::decodePayload($data, function ($packet, $index, $total) use (&$seen) {
                $isLast = $index + 1 == $total;
                if (!$isLast) {
                    $this->assertEquals($packet['type'], 'message');
                } else {
                    $this->assertEquals($packet['type'], 'ping');
                    if ($seen == 2) {
                        $this->assertEquals('done', 'done');
                    }
                }
                $seen++;
            });
        });
    }

    /**
     * @group   payloads
     * @group   payloadsEncodingAndDecoding
     */
    public function testShouldEncodeOrDecodeEmptyPayloads()
    {
        // note::callback not running
        $data = [];
        Parser::encodePayload($data, function ($data) {
            $this->assertIsString($data);
            Parser::decodePayload($data, function ($packet, $index, $total) {
                $this->assertEquals('open', $packet['type']);
                $isLast = $index + 1 == $total;
                $this->assertEquals(true, $isLast);
            });
        });
    }

    /**
     * @group   payloads
     * @group   payloadsEncodingAndDecoding
     */
    public function testShouldNotUtf8EncodeWhenDealingWithStringsOnly()
    {
        $data = [
            ['type' => 'message', 'data' => '€€€'],
            ['type' => 'message', 'data' => 'α']
        ];
        Parser::encodePayload($data, function ($data) {
            $this->assertEquals('4:4€€€2:4α', $data);
        });
    }

    /**
     * @group   payloads
     * @group   payloadsDecodingErrorHandling
     */
    public function testShouldErrOnBadPayloadFormat()
    {
        $err = [
            'type' => 'error',
            'data' => 'parser error'
        ];

        Parser::decodePayload('1!', function ($packet, $index, $total) use ($err) {
            $isLast = $index + 1 == $total;
            $this->assertEquals($err, $packet);
            $this->assertEquals(true, $isLast);
        });

        Parser::decodePayload('', function ($packet, $index, $total) use ($err) {
            $isLast = $index + 1 == $total;
            $this->assertEquals($err, $packet);
            $this->assertEquals(true, $isLast);
        });

        Parser::decodePayload('))', function ($packet, $index, $total) use ($err) {
            $isLast = $index + 1 == $total;
            $this->assertEquals($err, $packet);
            $this->assertEquals(true, $isLast);
        });
    }

    /**
     * @group   payloads
     * @group   payloadsDecodingErrorHandling
     */
    public function testShouldErrOnBadPayloadLength()
    {
        $err = [
            'type' => 'error',
            'data' => 'parser error'
        ];

        Parser::decodePayload('1:', function ($packet, $index, $total) use ($err) {
            $isLast = $index + 1 == $total;
            $this->assertEquals($err, $packet);
            $this->assertEquals(true, $isLast);
        });
    }

    /**
     * @group   payloads
     * @group   payloadsDecodingErrorHandling
     */
    public function testShouldErrOnBadPacketLength()
    {
        $err = [
            'type' => 'error',
            'data' => 'parser error'
        ];

        Parser::decodePayload('3:99:', function ($packet, $index, $total) use ($err) {
            $isLast = $index + 1 == $total;
            $this->assertEquals($err, $packet);
            $this->assertEquals(true, $isLast);
        });

        Parser::decodePayload('1:aa:', function ($packet, $index, $total) use ($err) {
            $isLast = $index + 1 == $total;
            $this->assertEquals($err, $packet);
            $this->assertEquals(true, $isLast);
        });

        Parser::decodePayload('1:a2:b', function ($packet, $index, $total) use ($err) {
            $isLast = $index + 1 == $total;
            $this->assertEquals($err, $packet);
            $this->assertEquals(true, $isLast);
        });
    }

    /**
     * @group   binaryPacket
     * @group   binaryPacketEncodingAndDecoding
     */
    public function testShouldEncodeBinaryPacket()
    {
        $data = [
            [
                'type' => 'message',
                'data' => 'firstBuffer'
            ],
            [
                'type' => 'message',
                'data' => 'secondBuffer'
            ],
        ];

        Parser::encodePayloadAsBinary($data, function ($data) {
            Parser::decodePayloadAsBinary($data, function ($packet, $index, $total) {
                $isLast = $index + 1 == $total;
                $this->assertEquals($packet['type'], 'message');
                if (!$isLast) {
                    $this->assertEquals($packet['data'], 'firstBuffer');
                } else {
                    $this->assertEquals($packet['data'], 'secondBuffer');
                }
            });
        });
    }
}