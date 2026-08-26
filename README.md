# HDWebmobile Spin & Win

A gamified discount-wheel popup for WooCommerce with real page targeting and server-enforced one spin per email.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-spin-and-win/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

HDWebmobile Spin & Win shows visitors a spinning-wheel popup that awards a real, single-use WooCommerce coupon. Unlike some similar plugins, spins are limited per email address on the server (not just a cookie), the popup only ever appears on the pages you choose, and the winning code is revealed right in the popup — no need to go check email.

## Features

* Server-enforced one spin per email address, not just a browser cookie
* Real page targeting: all pages, homepage only, shop & product pages, or specific page IDs — the popup has zero footprint on pages you didn't target
* Winning code shown instantly in the popup; a backup copy is also emailed via a real WooCommerce transactional email (customizable under WooCommerce > Settings > Emails)
* 8 configurable weighted prize segments — percent off, fixed amount off, free shipping, or "no prize" — each with its own probability weight
* Delay-based or exit-intent popup trigger
* Configurable coupon expiry
* Cookie-based frequency capping so returning visitors aren't nagged repeatedly

## Development

Standard WordPress plugin structure:

```
hdwebmobile-spin-and-win.php    Bootstrap
includes/class-hdspin-activator.php
includes/class-hdspin-admin.php
includes/class-hdspin-ajax.php
includes/class-hdspin-core.php
includes/class-hdspin-coupon-manager.php
includes/class-hdspin-email.php
includes/class-hdspin-frontend.php
includes/class-hdspin-hub.php
includes/class-hdspin-prize-engine.php
```

Part of the [HDWebmobile](https://hdwebmobile.com/plugins/) suite of focused, single-purpose WooCommerce plugins.

## License

GPLv2 or later. See [LICENSE](LICENSE).

