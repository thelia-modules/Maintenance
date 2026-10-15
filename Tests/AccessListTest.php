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

use Maintenance\Service\AccessList;
use PHPUnit\Framework\TestCase;

final class AccessListTest extends TestCase
{
    public function testOneEntryPerLineBlankLinesAndSpacesDropped(): void
    {
        self::assertSame(['/a', '/b'], AccessList::lines("  /a \r\n\n/b\n   "));
    }

    public function testAPathStartsWithASlashAndIsNotTheWholeShop(): void
    {
        self::assertSame(['payment/callback', '/', '/a b'], AccessList::invalidPaths("/payment/callback\npayment/callback\n/\n/a b\n/module/paypal/"));
    }

    public function testAnAddressIsAnIpOrACidrRange(): void
    {
        self::assertSame(
            ['192.0.2.300', '192.0.2.0/33', 'example.com', '2001:db8::/129', '10.0.0.0/x'],
            AccessList::invalidIps("192.0.2.10\n192.0.2.300\n198.51.100.0/24\n192.0.2.0/33\nexample.com\n2001:db8::/32\n2001:db8::/129\n10.0.0.0/x"),
        );
    }
}
