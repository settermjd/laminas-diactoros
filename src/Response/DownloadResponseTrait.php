<?php
/**
 * @see       https://github.com/laminas/laminas-diactoros for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Diactoros\Response;

use function array_keys;

trait DownloadResponseTrait
{
    /**
     * @var string The filename to be sent with the response
     */
    private $filename;

    /**
     * @var string The content type to be sent with the response
     */
    private $contentType;

    /**
     * A list of header keys required to be sent with a download response
     *
     * @var array
     */
    private $downloadResponseHeaders = [
        'cache-control',
        'content-description',
        'content-disposition',
        'content-transfer-encoding',
        'expires',
        'pragma'
    ];

    /**
     * Retrieve the download headers
     *
     * This function retrieves an array of the headers required to send a download response.
     * For this to work properly, once set, these headers should not be overridden, unless specifically required.
     *
     * @return array
     */
    private function getDownloadHeaders(): array
    {
        $contentDisposition = $this->filename ?? self::DEFAULT_DOWNLOAD_FILENAME;
        $contentType = $this->contentType ?? 'application/octet-stream';

        $headers = [];
        $headers['cache-control'] = 'must-revalidate';
        $headers['content-description'] = 'file transfer';
        $headers['content-disposition'] = sprintf('attachment; filename=%s', $contentDisposition);
        $headers['content-transfer-encoding'] = 'binary';
        $headers['content-type'] = $contentType;
        $headers['expires'] = '0';
        $headers['pragma'] = 'public';

        return $headers;
    }

    /**
     * Check if the extra headers contain any of the download headers
     *
     * @param array $headers
     * @return bool
     */
    public function overridesDownloadHeaders(array $headers = []) : bool
    {
        if (empty($headers)) {
            return false;
        }

        if (array_keys($this->getDownloadHeaders()) === array_keys($headers)) {
            return true;
        }

        $diff = array_diff(array_keys($this->getDownloadHeaders()), array_keys($headers));
        return (count($diff) >= 1 && count($diff) <= 7);
    }
}
