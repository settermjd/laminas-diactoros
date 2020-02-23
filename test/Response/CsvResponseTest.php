<?php
/**
 * @see       https://github.com/laminas/laminas-diactoros for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Diactoros\Response;

use InvalidArgumentException;
use Laminas\Diactoros\Response\CsvResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * Class CsvResponseTest
 * @package LaminasTest\Diactoros\Response
 * @coversDefaultClass \Laminas\Diactoros\Response\CsvResponse
 */
class CsvResponseTest extends TestCase
{
    const VALID_CSV_BODY = <<<EOF
"first","last","email","dob",
"john","citizen","john.citizen@afakeemailaddress.com","01/01/1970",
EOF;

    public function testConstructorAcceptsBodyAsString()
    {
        $response = new CsvResponse(self::VALID_CSV_BODY);
        $this->assertSame(self::VALID_CSV_BODY, (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConstructorAllowsPassingStatus()
    {
        $status = 404;

        $response = new CsvResponse(self::VALID_CSV_BODY, $status);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(self::VALID_CSV_BODY, (string) $response->getBody());
    }

    public function testConstructorAllowsPassingHeaders()
    {
        $status = 404;
        $headers = [
            'x-custom' => [ 'foo-bar' ],
        ];
        $filename = '';

        $response = new CsvResponse(self::VALID_CSV_BODY, $status, $headers);
        $this->assertSame(['foo-bar'], $response->getHeader('x-custom'));
        $this->assertSame('text/csv; charset=utf-8', $response->getHeaderLine('content-type'));
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(self::VALID_CSV_BODY, (string) $response->getBody());
    }

    public function testAllowsStreamsForResponseBody()
    {
        $stream = $this->prophesize(StreamInterface::class);
        $body   = $stream->reveal();
        $response = new CsvResponse($body);
        $this->assertSame($body, $response->getBody());
    }

    public function invalidContent()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['php://temp']],
            'object'     => [(object) ['php://temp']],
        ];
    }

    /**
     * @dataProvider invalidContent
     */
    public function testRaisesExceptionforNonStringNonStreamBodyContent($body)
    {
        $this->expectException(InvalidArgumentException::class);

        new CsvResponse($body);
    }

    /**
     * @group 115
     */
    public function testConstructorRewindsBodyStream()
    {
        $response = new CsvResponse(self::VALID_CSV_BODY);

        $actual = $response->getBody()->getContents();
        $this->assertSame(self::VALID_CSV_BODY, $actual);
    }
}
