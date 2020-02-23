<?php
/**
 * @see       https://github.com/laminas/laminas-diactoros for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Diactoros\Response;

use Laminas\Diactoros\Exception\InvalidArgumentException;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\DownloadResponse;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class DownloadResponseTest extends TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;

    public function setUp()
    {
        $validCSVString = <<<EOF
name,address,email,street,city,state,country
Victoria Freeman,1770 Urfe Pass,ba@ermel.dz,Ipziw Grove,Uzfujnic,SD,KN
Kathryn Reynolds,861 Nircu Mill,matanpil@cionoko.cu,Kaid Court,Botpupe,CA,WS
Dora Schmidt,703 Laufo Heights,baku@rahot.id,Sowdu Key,Pojzudiz,FL,TN
Delia Harrison,1409 Abeeli Loop,ganrade@epo.sr,Dulgi Heights,Eswooke,MN,DZ
Jordan Lane,1409 Zihoce Plaza,ginsinof@pijkad.tv,Acfuh View,Dulwawa,NY,NF
Danny Holmes,502 Fubjib Parkway,je@dagcar.nf,Apsa Loop,Etaweza,CO,MQ
Clarence Brewer,1085 Jacpa View,utde@aw.aw,Baroh Highway,Tikawi,WA,LS
Elmer Cohen,1556 Netjol Heights,ami@da.al,Dacbap Trail,Kihiguk,UT,WS
EOF;

        $directoryStructure = [
            'files' => [
                'empty.csv' => "",
                'valid.csv' => $validCSVString,
                'non-readable-file.csv' => vfsStream::newFile('non-readable-file.csv', 0000)
            ]
        ];
        $this->root = vfsStream::setup('root', null, $directoryStructure);
    }

    public function testCanCreateResponseFromString()
    {
        $body = file_get_contents($this->root->url() . '/files/valid.csv');
        $response = new DownloadResponse($body);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($body, (string) $response->getBody());
        $this->assertEquals(619, $response->getBody()->getSize());
        $this->assertHasValidResponseHeaders($response);
        $this->assertSame('attachment; filename=download', $response->getHeaderLine('content-disposition'));
    }

    public function testCanCreateResponseFromFilename()
    {
        $body = new Stream($this->root->url() . '/files/valid.csv');
        $response = new DownloadResponse($body);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(
            file_get_contents($this->root->url() . '/files/valid.csv'),
            (string) $response->getBody()
        );
        $this->assertHasValidResponseHeaders($response);
        $this->assertSame('attachment; filename=download', $response->getHeaderLine('content-disposition'));
    }

    public function testCanSendResponseWithCustomFilename()
    {
        $body = new Stream($this->root->url() . '/files/valid.csv');
        $response = new DownloadResponse($body, 200, 'valid.csv');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(
            file_get_contents($this->root->url() . '/files/valid.csv'),
            (string) $response->getBody()
        );
        $this->assertHasValidResponseHeaders($response, 'valid.csv');
        $this->assertSame('attachment; filename=valid.csv', $response->getHeaderLine('content-disposition'));
    }

    public function testCanSendResponseWithCustomContentType()
    {
        $body = new Stream($this->root->url() . '/files/valid.csv');
        $response = new DownloadResponse($body, 200, 'valid.csv', 'text/csv');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(
            file_get_contents($this->root->url() . '/files/valid.csv'),
            (string) $response->getBody()
        );
        $this->assertHasValidResponseHeaders($response, 'valid.csv', 'text/csv');
        $this->assertSame('attachment; filename=valid.csv', $response->getHeaderLine('content-disposition'));
    }

    /**
     * @param DownloadResponse $response
     * @param string $filename
     * @param string $contentType
     */
    private function assertHasValidResponseHeaders(
        DownloadResponse $response,
        $filename = 'download',
        $contentType = 'application/octet-stream'
    ) : void {
        $requiredHeaders = [
            'cache-control' => 'must-revalidate',
            'content-description' => 'file transfer',
            'content-disposition' => sprintf('attachment; filename=%s', $filename),
            'content-transfer-encoding' => 'binary',
            'content-type' => $contentType,
            'expires' => '0',
            'pragma' => 'public'
        ];
        foreach ($requiredHeaders as $header => $value) {
            $this->assertTrue($response->hasHeader($header));
            $this->assertSame($value, $response->getHeaderLine($header));
        }
    }

    /**
     * @dataProvider overrideDownloadHeaderDataProvider
     * @param array $headers A list of headers to use to initialise the DownloadResponse object
     */
    public function testCannotOverrideDownloadHeaders(array $headers = [])
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot override download headers');

        $body = new Stream($this->root->url() . '/files/valid.csv');
        new DownloadResponse(
            $body,
            200,
            DownloadResponse::DEFAULT_DOWNLOAD_FILENAME,
            DownloadResponse::DEFAULT_CONTENT_TYPE,
            $headers
        );
    }

    public function overrideDownloadHeaderDataProvider() : array
    {
        return [
            [
                [
                    'cache-control' => 'must-revalidate',
                    'content-description' => 'file transfer',
                    'content-disposition' => sprintf('attachment; filename=%s', DownloadResponse::DEFAULT_DOWNLOAD_FILENAME),
                    'content-transfer-encoding' => 'binary',
                    'content-type' => DownloadResponse::DEFAULT_CONTENT_TYPE,
                    'expires' => '0',
                    'pragma' => 'public'
                ]
            ],
            [
                []
            ],
            [
                [
                    'cache-control' => 'must-revalidate',
                ]
            ],
            [
                [
                    'accept-language' => 'en-AU'
                ]
            ],
            [
                [
                    'accept' => "text/html",
                    'accept-language' => 'en-AU'
                ]
            ],
        ];
    }
}
