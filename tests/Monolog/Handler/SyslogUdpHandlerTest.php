<?php declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use Monolog\Test\TestCase;

/**
 * @requires extension sockets
 */
class SyslogUdpHandlerTest extends TestCase
{
    public function testWeValidateFacilities()
    {
        $this->expectException(\UnexpectedValueException::class);

        $handler = new SyslogUdpHandler("ip", 514, "invalidFacility");
    }

    public function testWeSplitIntoLines()
    {
        $time = '2014-01-07T12:34';
        $pid = getmypid();
        $host = gethostname();

        $handler = $this->getMockBuilder('\Monolog\Handler\SyslogUdpHandler')
            ->setConstructorArgs(array("127.0.0.1", 514, "authpriv"))
            ->setMethods(array('getDateTime'))
            ->getMock();

        $handler->method('getDateTime')
            ->willReturn($time);

        $handler->setFormatter(new \Monolog\Formatter\ChromePHPFormatter());

        $socket = $this->getMockBuilder('Monolog\Handler\SyslogUdp\UdpSocket')
            ->setMethods(['write'])
            ->setConstructorArgs(['lol'])
            ->getMock();
        $socket->expects($this->at(0))
            ->method('write')
            ->with("lol", "<".(LOG_AUTHPRIV + LOG_WARNING).">1 $time $host php $pid - - ");
        $socket->expects($this->at(1))
            ->method('write')
            ->with("hej", "<".(LOG_AUTHPRIV + LOG_WARNING).">1 $time $host php $pid - - ");

        $handler->setSocket($socket);

        $handler->handle($this->getRecordWithMessage("hej\nlol"));
    }

    public function testSplitWorksOnEmptyMsg()
    {
        $handler = new SyslogUdpHandler("127.0.0.1", 514, "authpriv");
        $handler->setFormatter($this->getIdentityFormatter());

        $socket = $this->getMockBuilder('Monolog\Handler\SyslogUdp\UdpSocket')
            ->setMethods(['write'])
            ->setConstructorArgs(['lol'])
            ->getMock();
        $socket->expects($this->never())
            ->method('write');

        $handler->setSocket($socket);

        $handler->handle($this->getRecordWithMessage(null));
    }


    public function testRfc()
    {
        $time = 'Mar 22 21:16:47';
        $pid = getmypid();
        $host = gethostname();

        $handler = $this->getMockBuilder('\Monolog\Handler\SyslogUdpHandler')
            ->setConstructorArgs(array("127.0.0.1", 514, "authpriv", 'debug', true, "php", \Monolog\Handler\SyslogUdpHandler::RFC3164))
            ->setMethods(array('getDateTime'))
            ->getMock();

        $handler->method('getDateTime')
            ->willReturn($time);

        $handler->setFormatter(new \Monolog\Formatter\ChromePHPFormatter());

        $socket = $this->getMockBuilder('\Monolog\Handler\SyslogUdp\UdpSocket')
            ->setConstructorArgs(array('lol', 999))
            ->setMethods(array('write'))
            ->getMock();
        $socket->expects($this->at(0))
            ->method('write')
            ->with("lol", "<".(LOG_AUTHPRIV + LOG_WARNING).">$time $host php[$pid]: ");
        $socket->expects($this->at(1))
            ->method('write')
            ->with("hej", "<".(LOG_AUTHPRIV + LOG_WARNING).">$time $host php[$pid]: ");

        $handler->setSocket($socket);

        $handler->handle($this->getRecordWithMessage("hej\nlol"));
    }

    protected function getRecordWithMessage($msg)
    {
        return ['message' => $msg, 'level' => \Monolog\Logger::WARNING, 'context' => null, 'extra' => [], 'channel' => 'lol'];
    }
}
