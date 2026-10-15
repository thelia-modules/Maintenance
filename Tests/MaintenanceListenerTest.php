<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Maintenance\Tests;

use Maintenance\EventListener\MaintenanceListener;
use Maintenance\Service\MaintenanceConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Thelia\Core\Security\SecurityContext;

/**
 * Unit tests: no database, no kernel. Run from the root of the Thelia project the module is installed in:
 *   vendor/bin/phpunit --bootstrap vendor/autoload.php vendor/thelia/modules/Maintenance/Tests
 */
final class MaintenanceListenerTest extends TestCase
{
    private const PAGE = '<!doctype html><title>Maintenance</title>';

    public function testTheShopIsOpenWhileTheSwitchIsOff(): void
    {
        self::assertNull($this->responseTo(Request::create('/'), enabled: false));
    }

    public function testAVisitorGetsTheMaintenancePageWithA503(): void
    {
        $response = $this->responseTo(Request::create('/fr/casques.html'));

        self::assertNotNull($response);
        self::assertSame(503, $response->getStatusCode());
        self::assertSame(self::PAGE, $response->getContent());
        self::assertSame('3600', $response->headers->get('Retry-After'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        self::assertStringStartsWith('text/html', (string) $response->headers->get('Content-Type'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function backOfficePaths(): iterable
    {
        yield 'home' => ['/admin'];
        yield 'login' => ['/admin/login'];
        yield 'module page' => ['/admin/module/Maintenance'];
    }

    #[DataProvider('backOfficePaths')]
    public function testTheBackOfficeStaysReachable(string $path): void
    {
        self::assertNull($this->responseTo(Request::create($path, 'POST')));
    }

    public function testAPathThatOnlyStartsLikeTheBackOfficeIsClosed(): void
    {
        self::assertNotNull($this->responseTo(Request::create('/administration-des-ventes')));
    }

    public function testASignedInAdministratorBrowsesTheFront(): void
    {
        self::assertNull($this->responseTo(Request::create('/fr/casques.html'), adminSignedIn: true));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function paths(): iterable
    {
        yield 'allowed path' => ['/payment/callback', true];
        yield 'sub-path of an allowed path' => ['/module/paypal/notify', true];
        yield 'allowed path written with a trailing slash' => ['/module/paypal', true];
        yield 'path that only starts like an allowed one' => ['/module/paypalx', false];
        yield 'other path' => ['/fr/panier', false];
    }

    #[DataProvider('paths')]
    public function testOnlyTheAllowedPathsGetThrough(string $path, bool $open): void
    {
        $response = $this->responseTo(Request::create($path, 'POST'), allowedPaths: ['/payment/callback', '/module/paypal/', '/']);

        self::assertSame($open, null === $response);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function addresses(): iterable
    {
        yield 'allowed address' => ['192.0.2.10', true];
        yield 'address in an allowed range' => ['198.51.100.77', true];
        yield 'allowed IPv6 range' => ['2001:db8::1', true];
        yield 'other address' => ['203.0.113.5', false];
    }

    #[DataProvider('addresses')]
    public function testOnlyTheAllowedAddressesGetThrough(string $address, bool $open): void
    {
        $request = Request::create('/', server: ['REMOTE_ADDR' => $address]);

        $response = $this->responseTo($request, allowedIps: ['192.0.2.10', '198.51.100.0/24', '2001:db8::/32']);

        self::assertSame($open, null === $response);
    }

    public function testASubRequestIsLeftAlone(): void
    {
        self::assertNull($this->responseTo(Request::create('/'), requestType: HttpKernelInterface::SUB_REQUEST));
    }

    /**
     * @param list<string> $allowedPaths
     * @param list<string> $allowedIps
     */
    private function responseTo(
        Request $request,
        bool $enabled = true,
        bool $adminSignedIn = false,
        array $allowedPaths = [],
        array $allowedIps = [],
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): ?\Symfony\Component\HttpFoundation\Response {
        $configuration = new class($enabled, $allowedPaths, $allowedIps, self::PAGE) implements MaintenanceConfiguration {
            /**
             * @param list<string> $paths
             * @param list<string> $ips
             */
            public function __construct(private bool $enabled, private array $paths, private array $ips, private string $page)
            {
            }

            public function isEnabled(): bool
            {
                return $this->enabled;
            }

            public function allowedPaths(): array
            {
                return $this->paths;
            }

            public function allowedIps(): array
            {
                return $this->ips;
            }

            public function page(): string
            {
                return $this->page;
            }
        };

        $securityContext = new class(new RequestStack(), $adminSignedIn) extends SecurityContext {
            public function __construct(RequestStack $requestStack, private readonly bool $adminSignedIn)
            {
                parent::__construct($requestStack);
            }

            public function hasAdminUser(): bool
            {
                return $this->adminSignedIn;
            }
        };

        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, $requestType);
        (new MaintenanceListener($configuration, $securityContext))->closeShop($event);

        return $event->getResponse();
    }
}
