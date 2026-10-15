# Maintenance

Puts the shop in maintenance mode from the back office. While maintenance is on, visitors get a configurable
maintenance page with an HTTP 503 and a `Retry-After` header, so search engines keep their index. These requests
still get through:

- the back office (`/admin` and everything under it);
- an administrator signed in to the back office, who can browse the front office as usual;
- the paths listed in the module configuration, for instance the return URLs of payment providers;
- the IP addresses or CIDR ranges listed in the module configuration.

## Installation

```
composer require thelia/maintenance-module:^3.1
```

Then activate the module in the back office (Modules).

## Usage

Everything is on the module configuration page, `/admin/module/Maintenance`:

- **Enable / Disable maintenance** switches the shop. The state is stored in the module configuration
  (`maintenance_enabled`). No file is written to switch.
- **Access during maintenance**: one path per line (a path also lets its sub-paths through: `/module/paypal`
  covers `/module/paypal/notify`), one address or CIDR range per line. The page shows the address the shop sees
  for you. Behind a reverse proxy, declare it in Symfony's `trusted_proxies`, or every visitor shows up with the
  proxy's address.
- **Title, message and colors** of the maintenance page. The page is `public/maintenance.html`, created from
  `templates/maintenance.html` on activation; you can also replace it with your own file.

The switch is a `kernel.request` listener (priority 64: after the session and the "remember me" administrator are
loaded, before routing). The page is answered with `Cache-Control: no-store`, so an HTTP cache does not keep it
once maintenance is over.

## Upgrading from 3.0 or Thelia 2

Up to 3.0, enabling maintenance wrote a guard into the front controller (`public/index.php`, `web/index.php` on
Thelia 2), a block that starts with `//⚠--MAINTENANCE`. 3.1 never touches that file. If such a block is still
there, the update leaves it in place, logs a warning, and the configuration page shows an alert: remove the block
by hand, or the shop stays closed whatever the switch says. Maintenance is off after the update.

## Tests

Unit tests, no database needed. From the root of the Thelia project the module is installed in:

```
vendor/bin/phpunit --bootstrap vendor/autoload.php vendor/thelia/modules/Maintenance/Tests
```
