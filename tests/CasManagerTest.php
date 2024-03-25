<?php

namespace Subfission\Cas\Tests;

use Faker\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Subfission\Cas\CasManager;
use PHPUnit\Framework\TestCase;
use Subfission\Cas\LogoutStrategy;
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
    /**
     * @var \Faker\Generator
     */
    private $faker;
    /**
     * @var MockObject|LogoutStrategy|LogoutStrategy&MockObject
     */
    private $logoutStrategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->casProxy = $this->createMock(PhpCasProxy::class);
        $this->sessionProxy = $this->createMock(PhpSessionProxy::class);
        $this->logoutStrategy = $this->createMock(LogoutStrategy::class);

        $this->faker = Factory::create();
    }

    public function testDoesNotSetLoggerIfNotProvided(): void
    {
        $this->casProxy->expects($this->never())->method('setLogger');

        $this->makeCasManager();
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

    /**
     * @dataProvider configureCasChecks
     */
    public function testConfiguresCasWithoutSaml(bool $proxy, string $version): void
    {
        $serverType = $proxy ? 'proxy' : 'client';
        $notServerType = $proxy ? 'client' : 'proxy';

        $config = [
            'cas_proxy' => $proxy,
            'cas_version' => $version,
            'cas_enable_saml' => false,
        ];

        $this->casProxy->expects($this->once())->method('serverTypeCas')
            ->with($this->equalTo($version))
            ->willReturn($version);

        $this->casProxy->expects($this->once())->method($serverType)
            ->with($this->equalTo($version));

        $this->casProxy->expects($this->never())->method($notServerType);

        $this->casProxy->expects($this->never())->method('handleLogoutRequests');

        $this->makeCasManager($config);
    }

    /**
     * @dataProvider configureCasChecks
     */
    public function testConfiguresCasWithSaml(bool $proxy, string $version): void
    {
        $serverType = $proxy ? 'proxy' : 'client';
        $notServerType = $proxy ? 'client' : 'proxy';

        $config = [
            'cas_proxy' => $proxy,
            'cas_version' => $version,
            'cas_enable_saml' => true,
        ];

        $this->casProxy->expects($this->once())->method('serverTypeSaml')
            ->willReturn('S1');

        $this->casProxy->expects($this->once())->method($serverType)
            ->with($this->equalTo('S1'));

        $this->casProxy->expects($this->never())->method($notServerType);

        $this->casProxy->expects($this->once())->method('handleLogoutRequests');

        $this->makeCasManager($config);
    }

    public function configureCasChecks(): array
    {
        return [
            'client' => [false, '2.0'],
            'proxy' => [true, '2.0'],
        ];
    }

    public function testConfiguresCasWithClientArguments(): void
    {
        $config = [
            'cas_enable_saml' => false,
            'cas_hostname' => $this->faker->domainName(),
            'cas_port' => $this->faker->numberBetween(1, 1024),
            'cas_uri' => $this->faker->url(),
            'cas_client_service' => $this->faker->url(),
            'cas_control_session' => $this->faker->boolean(),
        ];

        $this->casProxy->expects($this->once())->method('serverTypeCas')
            ->willReturnArgument(0);

        $this->casProxy->expects($this->once())->method('client')
            ->with(
                $this->anything(),
                $this->equalTo($config['cas_hostname']),
                $this->equalTo($config['cas_port']),
                $this->equalTo($config['cas_uri']),
                $this->equalTo($config['cas_client_service']),
                $this->equalTo($config['cas_control_session'])
            );

        $this->makeCasManager($config);
    }

    public function testConfiguresLogoutHandlingWhenUsingSaml(): void
    {
        $realhosts = [
            $this->faker->domainName(),
            $this->faker->domainName(),
        ];

        $config = [
            'cas_enable_saml' => true,
            'cas_real_hosts' => implode(',', $realhosts),
        ];

        $this->casProxy->expects($this->once())->method('handleLogoutRequests')
            ->with(
                $this->equalTo(true),
                $this->equalTo($realhosts)
            );

        $this->makeCasManager($config);
    }

    /**
     * @dataProvider casValidationChecks
     */
    public function testConfiguresCasValidation(?string $casValidation, bool $willValidate): void
    {
        $config = [
            'cas_validation' => $casValidation,
            'cas_cert' => $this->faker->filePath(),
            'cas_validate_cn' => $this->faker->boolean(),
        ];

        if ($willValidate) {
            $this->casProxy->expects($this->never())->method('setNoCasServerValidation');
            $this->casProxy->expects($this->once())->method('setCasServerCACert')
                ->with($this->equalTo($config['cas_cert']), $this->equalTo($config['cas_validate_cn']));
        } else {
            $this->casProxy->expects($this->once())->method('setNoCasServerValidation');
            $this->casProxy->expects($this->never())->method('setCasServerCACert');
        }

        $this->makeCasManager($config);
    }

    public function casValidationChecks(): array
    {
        return [
            'no validation' => [null, false],
            'ca validation' => ['ca', true],
            'self validation' => ['self', true],
        ];
    }

    public function testSetsServerLoginUrl(): void
    {
        $config = [
            'cas_login_url' => $this->faker->url(),
        ];

        $this->casProxy->expects($this->once())->method('setServerLoginURL')
            ->with($this->equalTo($config['cas_login_url']));

        $this->makeCasManager($config);
    }

    public function testSetsServerLogoutUrl(): void
    {
        $config = [
            'cas_logout_url' => $this->faker->url(),
        ];

        $this->casProxy->expects($this->once())->method('setServerLogoutURL')
            ->with($this->equalTo($config['cas_logout_url']));

        $this->makeCasManager($config);
    }

    /**
     * @dataProvider fixedServiceUrlChecks
     */
    public function testSetsFixedServiceUrlIfGiven(bool $willSet): void
    {
        $config = [
            'cas_redirect_path' => $willSet ? $this->faker->url() : null
        ];

        if ($willSet) {
            $this->casProxy->expects($this->once())->method('setFixedServiceURL')
                ->with($this->equalTo($config['cas_redirect_path']));
        } else {
            $this->casProxy->expects($this->never())->method('setFixedServiceURL');
        }

        $this->makeCasManager($config);
    }

    public function fixedServiceUrlChecks(): array
    {
        return [
            'no url' => [false],
            'url' => [true],
        ];
    }

    /**
     * @dataProvider masqueradeChecks
     */
    public function testSetsMasquerade(?string $masquerade): void
    {
        $config = [
            'cas_masquerade' => $masquerade
        ];

        $manager = $this->makeCasManager($config);

        $this->assertEquals(!empty($masquerade), $manager->isMasquerading());
    }

    public function masqueradeChecks(): array
    {
        return [
            'masquerade' => ['bob'],
            'no masquerade' => [null],
        ];
    }

    public function testAuthenticateUser(): void
    {
        $this->casProxy->expects($this->once())->method('forceAuthentication')
            ->willReturn(true);

        $manager = $this->makeCasManager();

        $this->assertTrue($manager->authenticate());
    }

    public function testAuthenticateUserWhenMasquerading(): void
    {
        $this->casProxy->expects($this->never())->method('forceAuthentication');

        $config = [
            'cas_masquerade' => 'bob'
        ];

        $manager = $this->makeCasManager($config);

        $this->assertTrue($manager->authenticate());
    }

    public function testGetUser(): void
    {
        $this->casProxy->expects($this->once())->method('getUser')
            ->willReturn('frank');

        $manager = $this->makeCasManager();

        $this->assertEquals('frank', $manager->user());
    }

    public function testGetCurrentUser(): void
    {
        $this->casProxy->expects($this->once())->method('getUser')
            ->willReturn('frank');

        $manager = $this->makeCasManager();

        $this->assertEquals('frank', $manager->getCurrentUser());
    }

    public function testGetUserWhenMasquerading(): void
    {
        $this->casProxy->expects($this->never())->method('getUser');

        $config = [
            'cas_masquerade' => 'bob'
        ];

        $manager = $this->makeCasManager($config);

        $this->assertEquals('bob', $manager->user());
        $this->assertEquals('bob', $manager->getCurrentUser());
    }

    public function testGetAttribute(): void
    {
        $this->casProxy->expects($this->once())->method('getAttribute')
            ->with($this->equalTo('name'))
            ->willReturn('frank');

        $manager = $this->makeCasManager();

        $this->assertEquals('frank', $manager->getAttribute('name'));
    }

    public function testHasAttribute(): void
    {
        $hasAttribute = $this->faker->boolean();

        $this->casProxy->expects($this->once())->method('hasAttribute')
            ->with($this->equalTo('name'))
            ->willReturn($hasAttribute);

        $manager = $this->makeCasManager();

        $this->assertEquals($hasAttribute, $manager->hasAttribute('name'));
    }

    public function testGetAttributes(): void
    {
        $attributes = [
            'name' => 'frank',
        ];

        $this->casProxy->expects($this->once())->method('getAttributes')
            ->willReturn($attributes);

        $manager = $this->makeCasManager();

        $this->assertEquals($attributes, $manager->getAttributes());
    }

    public function testGetAttributeWhenMasquerading(): void
    {
        $this->casProxy->expects($this->never())->method('getAttribute');

        $manager = $this->makeCasManager(['cas_masquerade' => 'bob']);
        $manager->setAttributes(['name' => 'frank']);

        $this->assertEquals('frank', $manager->getAttribute('name'));
    }

    public function testHasAttributeWhenMasquerading(): void
    {
        $hasAttribute = $this->faker->boolean();

        $attributes = $hasAttribute ? ['name' => 'frank'] : [];

        $this->casProxy->expects($this->never())->method('hasAttribute');

        $manager = $this->makeCasManager(['cas_masquerade' => 'bob']);
        $manager->setAttributes($attributes);

        $this->assertEquals($hasAttribute, $manager->hasAttribute('name'));
    }

    public function testGetAttributesWhenMasquerading(): void
    {
        $attributes = [
            'name' => 'frank',
        ];

        $this->casProxy->expects($this->never())->method('getAttributes');

        $manager = $this->makeCasManager(['cas_masquerade' => 'bob']);
        $manager->setAttributes($attributes);

        $this->assertEquals($attributes, $manager->getAttributes());
    }

    public function testLogoutOfCas(): void
    {
        $this->casProxy->expects($this->once())->method('logout');
        $this->logoutStrategy->expects($this->once())->method('completeLogout');

        $manager = $this->makeCasManager();

        $manager->logout();
    }

    /**
     * @dataProvider logoutParameterChecks
     */
    public function testLogoutWithParameters(string $url, string $service): void
    {
        $expects = [];

        if (!empty($url)) {
            $expects['url'] = $url;
        }

        if (!empty($service)) {
            $expects['service'] = $service;
        }

        $this->casProxy->expects($this->once())->method('logout')
            ->with($this->equalTo($expects));

        $manager = $this->makeCasManager();

        $manager->logout($url, $service);
    }

    public function logoutParameterChecks(): array
    {
        return [
            'url' => ['https://example.com', ''],
            'service' => ['', 'https://other.com'],
            'both' => ['https://example.com', 'https://other.com']
        ];
    }

    public function testLogoutUsesCasRedirect(): void
    {
        $redirectUrl = $this->faker->url();

        $this->casProxy->expects($this->once())->method('logout')
            ->with($this->equalTo(['service' => $redirectUrl]));

        $manager = $this->makeCasManager(['cas_logout_redirect' => $redirectUrl]);

        $manager->logout();
    }

    public function testLogoutWithUrl(): void
    {
        $url = $this->faker->url();

        $this->casProxy->expects($this->once())->method('logout')
            ->with($this->equalTo(['url' => $url]));

        $manager = $this->makeCasManager();

        $manager->logoutWithUrl($url);
    }

    /**
     * @dataProvider authenticatedChecks
     */
    public function testIsAuthenticated(bool $authenticated): void
    {
        $this->casProxy->expects($this->once())->method('isAuthenticated')
            ->willReturn($authenticated);

        $manager = $this->makeCasManager();

        $this->assertEquals($authenticated, $manager->isAuthenticated());
    }

    public function testIsAuthenticatedWhenMasquerading(): void
    {
        $this->casProxy->expects($this->never())->method('isAuthenticated');

        $manager = $this->makeCasManager(['cas_masquerade' => 'bob']);

        $this->assertTrue($manager->isAuthenticated());
    }

    /**
     * @dataProvider authenticatedChecks
     */
    public function testCheckAuthentication(bool $authenticated): void
    {
        $this->casProxy->expects($this->once())->method('checkAuthentication')
            ->willReturn($authenticated);

        $manager = $this->makeCasManager();

        $this->assertEquals($authenticated, $manager->checkAuthentication());
    }

    public function authenticatedChecks(): array
    {
        return [
            'is authenticated' => [true],
            'is not authenticated' => [false],
        ];
    }

    public function testCheckAuthenticationWhenMasquerading(): void
    {
        $this->casProxy->expects($this->never())->method('checkAuthentication');

        $manager = $this->makeCasManager(['cas_masquerade' => 'bob']);

        $this->assertTrue($manager->checkAuthentication());
    }

    private function makeCasManager(array $config = []): CasManager
    {
        return new CasManager($config, $this->casProxy, $this->sessionProxy, $this->logoutStrategy);
    }
}
