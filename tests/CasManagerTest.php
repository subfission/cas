<?php

namespace Subfission\Cas\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Subfission\Cas\CasManager;
use PHPUnit\Framework\TestCase;
use Subfission\Cas\PhpCasProxy;

class CasManagerTest extends TestCase
{
    /**
     * @var MockObject|PhpCasProxy|PhpCasProxy&MockObject
     */
    private $casProxy;

    public function setUp(): void
    {
        parent::setUp();

        $this->casProxy = $this->createMock(PhpCasProxy::class);
    }

    public function testDoesNotEnableCasDebugByDefault(): void
    {
        $this->casProxy->expects($this->never())->method('setLogger');

        new CasManager([], $this->casProxy);
    }

    public function testEnablesCasDebugWhenSet(): void
    {
        $this->casProxy->expects($this->once())->method('setDebug')
            ->willThrowException(new \Exception());

        $this->casProxy->expects($this->once())->method('setLogger');

        new CasManager(['cas_debug' => true], $this->casProxy);
    }
}
