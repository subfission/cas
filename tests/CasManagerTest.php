<?php

namespace Subfission\Cas\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Subfission\Cas\CasManager;
use PHPUnit\Framework\TestCase;
use Subfission\Cas\PhpCasProxy;
use Subfission\Cas\PhpSessionProxy;

class CasManagerTest extends TestCase
{
    /**
     * @var MockObject|PhpCasProxy|PhpCasProxy&MockObject
     */
    private $casProxy;
    /**
     * @var MockObject|PhpSessionProxy|PhpSessionProxy&MockObject
     */
    private $sessionProxy;

    public function setUp(): void
    {
        parent::setUp();

        $this->casProxy = $this->createMock(PhpCasProxy::class);
        $this->sessionProxy = $this->createMock(PhpSessionProxy::class);
    }

    public function testDoesNotSetLoggerIfNotProvided(): void
    {
        $this->casProxy->expects($this->never())->method('setLogger');

        $this->makeCasManager();
    }

    public function testSetsLoggerWhenLoggerIsProvided(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $this->casProxy->expects($this->once())->method('setLogger')
            ->with($this->equalTo($logger));

        $this->makeCasManager([], $logger);
    }

    /**
     * @dataProvider setVerboseChecks
     */
    public function testSetsVerbose(bool $verbose): void
    {
        $this->casProxy->expects($this->once())->method('setVerbose')
            ->with($this->equalTo($verbose));

        $this->makeCasManager(['cas_verbose_errors' => $verbose]);
    }

    public function setVerboseChecks(): array
    {
        return [
            'verbose' => [true],
            'not verbose' => [false],
        ];
    }

    /**
     * @dataProvider setUpSessionChecks
     */
    public function testSetsUpSessionIfNeeded(bool $headersSent, string $sessionId, bool $shouldSetSession): void
    {
        $this->sessionProxy->expects($this->once())
            ->method('headersSent')
            ->willReturn($headersSent);

        $this->sessionProxy->expects($headersSent ? $this->never() : $this->once())
            ->method('sessionGetId')
            ->willReturn($sessionId);

        $this->sessionProxy->expects($shouldSetSession ? $this->once() : $this->never())
            ->method('sessionSetName');

        $this->sessionProxy->expects($shouldSetSession ? $this->once() : $this->never())
            ->method('sessionSetCookieParams');

        $this->makeCasManager();
    }

    public function setUpSessionChecks(): array
    {
        return [
            'headers not sent, no session id' => [false, '', true],
            'headers not sent, session id' => [false, 'abc123', false],
            'headers sent, no session id' => [true, '', false],
            'headers sent, session id' => [true, 'abc123', false],
        ];
    }

    private function makeCasManager(array $config = [], LoggerInterface $logger = null): CasManager
    {
        return new CasManager($config, $logger, $this->casProxy, $this->sessionProxy);
    }
}
