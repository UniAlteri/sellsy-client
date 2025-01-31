<?php

/*
 * Sellsy Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/libraries/sellsy Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\Sellsy\HttpPlug\Transport;

use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Teknoo\Sellsy\HttpPlug\Transport\HttpPlug;
use Teknoo\Sellsy\Transport\TransportInterface;
use Teknoo\Tests\Sellsy\Transport\AbstractTransportTests;

/**
 * @covers \Teknoo\Sellsy\HttpPlug\Transport\HttpPlug
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class HttpPlugTest extends AbstractTransportTests
{
    /**
     * @return TransportInterface
     */
    public function buildTransport(): TransportInterface
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $uriFactory = $this->createMock(UriFactoryInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $uriFactory->expects($this->any())
            ->method('createUri')
            ->willReturn($this->createMock(UriInterface::class));

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->any())->method('withHeader')->willReturn($request);
        $request->expects($this->any())->method('getHeader')->willReturn(['multipart/form-data; boundary="fooBar"']);

        $requestFactory->expects($this->any())
            ->method('createRequest')
            ->willReturn($request);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->any())->method('getMetadata')->willReturn('foo');

        $streamFactory->expects($this->any())
            ->method('createStream')
            ->willReturn($stream);

        $client->expects($this->any())
            ->method('sendAsyncRequest')
            ->with($this->callback(function ($arg) {
                return $arg instanceof RequestInterface;
            }))
            ->willReturn($this->createMock(Promise::class));

        return new HttpPlug($client, $uriFactory, $requestFactory, $streamFactory);
    }

    public function testCreateStream()
    {
        $body = [
            ['name' => 'foo', 'contents' => 'bar']
        ];

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->any())->method('withHeader')->willReturn($request);
        $request->expects($this->any())->method('getHeader')->willReturn(['multipart/form-data; boundary="fooBar"']);

        self::assertInstanceOf(
            StreamInterface::class,
            $this->buildTransport()->createStream(
                $body,
                $request
            )
        );
    }

    public function testCreateStreamWithoutRequest()
    {
        $this->expectException(\RuntimeException::class);

        $body = [
            ['name' => 'foo', 'contents' => 'bar']
        ];

        self::assertInstanceOf(
            StreamInterface::class,
            $this->buildTransport()->createStream(
                $body
            )
        );
    }
}
