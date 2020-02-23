<?php
/**
 * @see       https://github.com/laminas/laminas-diactoros for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Diactoros\Response;

use Laminas\Diactoros\Exception;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\StreamInterface;

use function get_class;
use function gettype;
use function is_object;
use function is_string;
use function sprintf;

/**
 * CSV response.
 *
 * Allows creating a CSV response by passing a string to the constructor;
 * by default, sets a status code of 200 and sets the Content-Type header to
 * text/csv.
 */
class CsvResponse extends Response
{
    use InjectContentTypeTrait;

    const DEFAULT_DELIMITER = ',';
    const DEFAULT_ENCLOSURE = '"';
    const DEFAULT_ESCAPE = "\n";

    /**
     * Create a CSV response.
     *
     * Produces a CSV response with a Content-Type of text/csv and a default
     * status of 200.
     *
     * @param string|StreamInterface $text String or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     */
    public function __construct($text, int $status = 200, array $headers = [])
    {
        parent::__construct(
            $this->createBody($text),
            $status,
            $this->injectContentType('text/csv; charset=utf-8', $headers)
        );
    }

    public static function createBodyFromString(string $text) : CsvResponse
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($text);
        $body->rewind();

        return new CsvResponse($body);
    }

    public static function createBodyFromFile(string $fileName)
    {
        $resource = fopen($fileName, 'r');
        $body = new Stream($resource);
        $body->rewind();

        return new CsvResponse($body);
    }

    /**
     * @param array|\Traversable $content
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @return CsvResponse
     */
    public static function createBodyFromArray(
        $content,
        string $delimiter = self::DEFAULT_DELIMITER,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        string $escape = self::DEFAULT_ESCAPE
    ) {
        $text = '';
        $convertedRow = function ($row) use($enclosure) {
            $output = [];
            foreach ($row as $item) {
                $output[] = sprintf('%1$s%2$s%1$s', $enclosure, $item);
            }
            return $output;
        };

        foreach ($content as $row) {
            $text .= sprintf("%s%s", implode($delimiter, $convertedRow($row)), $escape);
        }

        return new CsvResponse($text);
    }

    /**
     * Create the CSV message body.
     *
     * @param string|StreamInterface $text
     * @return StreamInterface
     * @throws Exception\InvalidArgumentException if $text is neither a string or stream.
     */
    private function createBody($text) : StreamInterface
    {
        if ($text instanceof StreamInterface) {
            return $text;
        }

        if (! is_string($text)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid CSV content (%s) provided to %s',
                (is_object($text) ? get_class($text) : gettype($text)),
                __CLASS__
            ));
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($text);
        $body->rewind();
        return $body;
    }
}
