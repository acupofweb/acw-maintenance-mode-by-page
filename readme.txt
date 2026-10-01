=== ACW Maintenance Mode by Page ===
Contributors: lorenzof
Tags: maintenance, maintenance mode, coming soon, under construction, 503
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Put your site in maintenance mode using one of your existing pages. No templates, no page builder: pick a page and you are done.

== Description ==

ACW Maintenance Mode by Page lets you put your site into maintenance by showing visitors **one single page**, chosen from your existing Pages. No dedicated templates and no page builder are required: you design the page with your own theme, then simply select it.

When the mode is active:

* Logged-out visitors who open any URL are redirected to the selected page.
* You choose which user roles keep browsing the whole site and using the dashboard (administrators always can). Users with other roles, such as subscribers or customers, see the maintenance page like visitors.
* Front-end login, registration and lost password pages (e.g. Paid Memberships Pro) are detected and stay reachable. You can add other pages that must stay reachable, such as an account page.
* The maintenance page is served with an HTTP **503 Service Unavailable** status and a `Retry-After` header, which is the correct signal for search engines during a temporary maintenance.
* The page is displayed with a minimal template (**content only**, without the theme header, footer and menu). Choose the content width: **default** (inherited from the theme via `theme.json` or `$content_width`, with a fallback) or **full width**.
* The login page, AJAX and the REST API remain reachable, so you can always sign in and turn the mode off.
* If the selected page is deleted, unpublished or password-protected, visitors get a generic 503 maintenance message (never a redirect loop) and administrators see a warning.
* Saving the settings purges the cache of SiteGround Speed Optimizer, WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache and WP Fastest Cache.

No complex configuration required: one checkbox to enable it and one dropdown to pick the page.

== Installation ==

1. Upload the `acw-maintenance-mode-by-page` folder to `/wp-content/plugins/`, or install the plugin from the WordPress dashboard.
2. Activate the plugin through the "Plugins" screen.
3. Go to **Settings → Maintenance**.
4. Check "Enable maintenance", choose the page to display and save.

== Frequently Asked Questions ==

= Do logged-in users see the maintenance page? =

It depends on their role. In **Settings → Maintenance** check the roles that keep browsing the site: by default these are the roles that can write content (Administrator, Editor, Author, Contributor). Users with unchecked roles see the maintenance page and cannot open the dashboard. Administrators always have access.

= My site uses a front-end login page. Can users still sign in? =

Yes. Login, registration and lost password pages moved to the front end by plugins such as Paid Memberships Pro are detected automatically. Other pages can be kept reachable with the "Pages always reachable" setting.

= Visitors still see the normal site after enabling maintenance =

A page cache is serving pages saved before maintenance was enabled. The most common caching plugins are purged automatically when you save; with other caches, a CDN or a server cache, purge it manually.

= Does the maintenance page show the theme header and footer? =

No, only the page content is shown through a minimal template. The theme styles (CSS/fonts) are still loaded.

= Is this a security feature for private content? =

No. It is a maintenance mode: it blocks browser navigation of the theme-rendered pages. It does not protect content served through the REST API or direct media file URLs. To protect truly private content you need server-level protection.

= How do I turn it off if I get locked out? =

The login page (`wp-login.php`) always remains accessible and administrators always have access to the dashboard. Sign in and disable the mode from the settings.

== Screenshots ==

1. The settings screen: enable the mode, choose the page, the roles with access, the pages always reachable and the content width.

== Changelog ==

= 1.1.0 =
* New: choose which user roles keep browsing the site during maintenance. Users with other roles see the maintenance page and cannot open the dashboard. Administrators always have access.
* New: front-end login, registration and lost password pages (e.g. Paid Memberships Pro) stay reachable.
* New: "Pages always reachable" setting.
* New: saving the settings purges the most common page caches.
* Fix: redirect loop when the selected page is the posts page, or is deleted, trashed or unpublished. Visitors now get a generic 503 message and administrators a warning.
* Fix: a password-protected page can no longer be used as maintenance page (visitors would see the password form).
* Fix: theme content widths using clamp() or var() are no longer broken.
* Fix: the redirect is no longer cacheable.
* Tweak: the maintenance template calls wp_body_open().
* Tweak: uninstall removes the options on every site of a multisite network.

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.1.0 =
Logged-in users without a content role (e.g. subscribers, customers) now see the maintenance page. Review "Who can browse the site" in Settings → Maintenance.
