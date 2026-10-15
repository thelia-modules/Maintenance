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

namespace Maintenance\EventListener;

use Maintenance\Service\MaintenanceConfiguration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Core\Security\SecurityContext;

/**
 * Closes the shop while maintenance is on: every request gets the maintenance page with a 503, except the back
 * office, an administrator signed in, and the paths and addresses allowed in the module configuration.
 *
 * Runs after the session is started and the "remember me" administrator restored (priority 128), before routing.
 */
final readonly class MaintenanceListener implements EventSubscriberInterface
{
    public const RETRY_AFTER_SECONDS = 3600;

    public function __construct(
        private MaintenanceConfiguration $configuration,
        private SecurityContext $securityContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['closeShop', 64]];
    }

    public function closeShop(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->configuration->isEnabled()) {
            return;
        }

        $request = $event->getRequest();
        $path = rawurldecode($request->getPathInfo());

        if (
            self::isBackOffice($path)
            || $this->isAllowedPath($path)
            || $this->isAllowedIp($request->getClientIp())
            || $this->securityContext->hasAdminUser()
        ) {
            return;
        }

        $event->setResponse(new Response($this->configuration->page(), Response::HTTP_SERVICE_UNAVAILABLE, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Retry-After' => (string) self::RETRY_AFTER_SECONDS,
            'Cache-Control' => 'no-store',
        ]));
    }

    private static function isBackOffice(string $path): bool
    {
        return self::isUnder($path, '/admin');
    }

    private function isAllowedPath(string $path): bool
    {
        foreach ($this->configuration->allowedPaths() as $prefix) {
            if (self::isUnder($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedIp(?string $clientIp): bool
    {
        $allowedIps = $this->configuration->allowedIps();

        return null !== $clientIp && [] !== $allowedIps && IpUtils::checkIp($clientIp, $allowedIps);
    }

    /**
     * "/module/paypal" covers "/module/paypal" and "/module/paypal/notify", not "/module/paypalx". A bare "/" covers
     * nothing: it would reopen the whole shop.
     */
    private static function isUnder(string $path, string $prefix): bool
    {
        $prefix = rtrim($prefix, '/');

        return '' !== $prefix && ($path === $prefix || str_starts_with($path, $prefix.'/'));
    }
}
