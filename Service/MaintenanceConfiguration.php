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

namespace Maintenance\Service;

interface MaintenanceConfiguration
{
    public function isEnabled(): bool;

    /**
     * @return list<string> path prefixes still served during maintenance
     */
    public function allowedPaths(): array;

    /**
     * @return list<string> addresses or CIDR ranges still served during maintenance
     */
    public function allowedIps(): array;

    /**
     * @return string the HTML page answered while the shop is closed
     */
    public function page(): string;
}
