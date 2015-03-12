<?php

namespace TreeHouse\Feeder\Tests\Transport;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Event\Emitter;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use TreeHouse\Feeder\FeedEvents;
use TreeHouse\Feeder\Tests\Mock\EventDispatcherMock;
use TreeHouse\Feeder\Transport\Connection;
use TreeHouse\Feeder\Transport\HttpTransport;

class HttpTransportTest extends AbstractTransportTest
{
    /**
     * @var string
     */
    protected $url = 'http://ovh.net/files/md5sum.txt';

    /**
     * @var string
     */
    protected $largeUrl = 'http://ovh.net/files/1Mb.dat';

    public function testConstructor()
    {
        $transport = new HttpTransport(new Connection([]));

        $this->assertInstanceOf(HttpTransport::class, $transport);
    }

    public function testFactory()
    {
        $transport = HttpTransport::create($this->url);

        $this->assertInstanceOf(HttpTransport::class, $transport);
        $this->assertEquals($this->url, $transport->getUrl());
    }

    public function testFactoryWithCredentials()
    {
        $transport = HttpTransport::create($this->url, 'user', 'p@$$');

        $this->assertInstanceOf(HttpTransport::class, $transport);
        $this->assertEquals('user', $transport->getUser());
        $this->assertEquals('p@$$', $transport->getPass());
    }

    /**
     * @expectedException \LogicException
     */
    public function testNoUrlSet()
    {
        $transport = new HttpTransport(new Connection([]));
        $transport->getUrl();
    }

    public function testToString()
    {
        $transport = HttpTransport::create($this->url);

        $this->assertEquals($this->url, (string) $transport);
    }

    /**
     * @expectedException \TreeHouse\Feeder\Exception\TransportException
     */
    public function testRequestException()
    {
        /** @var RequestException|\PHPUnit_Framework_MockObject_MockObject $exception */
        $exception = $this->getMockBuilder(RequestException::class)->disableOriginalConstructor()->getMock();
        $request   = $this->getMockBuilder(RequestInterface::class)->getMockForAbstractClass();

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this
            ->getMockBuilder(ClientInterface::class)
            ->setMethods(['send'])
            ->getMockForAbstractClass()
        ;
        $client
            ->expects($this->once())
            ->method('send')
            ->will($this->throwException($exception))
        ;
        $client
            ->expects($this->any())
            ->method('getEmitter')
            ->will($this->returnValue(new Emitter()))
        ;

        /** @var HttpTransport|\PHPUnit_Framework_MockObject_MockObject $transport */
        $transport = $this
            ->getMockBuilder(HttpTransport::class)
            ->setMethods(['createRequest'])
            ->setConstructorArgs([new Connection([])])
            ->getMock()
        ;
        $transport
            ->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($request))
        ;

        $transport->setClient($client);
        $transport->getFile();
    }

    /**
     * @expectedException \TreeHouse\Feeder\Exception\TransportException
     */
    public function testEmptyBody()
    {
        $request  = $this->getMockBuilder(RequestInterface::class)->getMockForAbstractClass();
        $response = $this->getMockBuilder(ResponseInterface::class)->getMockForAbstractClass();

        $response
            ->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue(null))
        ;

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this
            ->getMockBuilder(ClientInterface::class)
            ->setMethods(['send'])
            ->getMockForAbstractClass()
        ;
        $client
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response))
        ;
        $client
            ->expects($this->any())
            ->method('getEmitter')
            ->will($this->returnValue(new Emitter()))
        ;

        /** @var HttpTransport|\PHPUnit_Framework_MockObject_MockObject $transport */
        $transport = $this
            ->getMockBuilder(HttpTransport::class)
            ->setMethods(['createRequest'])
            ->setConstructorArgs([new Connection([])])
            ->getMock()
        ;
        $transport
            ->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($request))
        ;

        $transport->setClient($client);
        $transport->getFile();
    }

    public function testEvents()
    {
        $dispatcher = new EventDispatcherMock();

        $transport = $this->getTransport();
        $transport->setUrl($this->largeUrl);
        $transport->setEventDispatcher($dispatcher);
        $transport->getFile();

        $events = $dispatcher->getDispatchedEvents();

        // test for a pre-fetch event
        $this->assertArrayHasKey(FeedEvents::PRE_FETCH, $events);
        $this->assertNotEmpty($events[FeedEvents::PRE_FETCH]);

        // test for a post-fetch event
        $this->assertArrayHasKey(FeedEvents::POST_FETCH, $events);
        $this->assertNotEmpty($events[FeedEvents::POST_FETCH]);

        // test for at least one fetch-progress event
        $this->assertArrayHasKey(FeedEvents::FETCH_PROGRESS, $events);
        $this->assertGreaterThan(0, $events[FeedEvents::FETCH_PROGRESS]);

        // test an individual progress event
        $event = end($events[FeedEvents::FETCH_PROGRESS]);
        $this->assertGreaterThan(0, $event->getBytesFetched());
        $this->assertGreaterThan(0, $event->getBytesTotal());
    }

    /**
     * @return HttpTransport
     */
    protected function getTransport()
    {
        return HttpTransport::create($this->url);
    }
}