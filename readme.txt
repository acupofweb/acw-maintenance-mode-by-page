=== ACW Maintenance Mode by Page ===
Contributors: lorenzof
Tags: maintenance, maintenance mode, coming soon, under construction, 503
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Put your site in maintenance mode using one of your existing pages. No templates, no page builder: pick a page and you are done.

== Description ==

ACW Maintenance Mode by Page lets you put your site into maintenance by showing visitors **one single page**, chosen from your existing Pages. No dedicated templates and no page builder are required: you design the page with your own theme, then simply select it.

When the mode is active:

* Logged-out visitors who open any URL are redirected to the selected page.
* Logged-in users keep browsing the whole site normally.
* The maintenance page is served with an HTTP **503 Service Unavailable** status and a `Retry-After` header, which is the correct signal for search engines during a temporary maintenance.
* The page is displayed with a minimal template (**content only**, without the theme header, footer and menu). Choose the content width: **default** (inherited from the theme via `theme.json` or `$content_width`, with a fallback) or **full width**.
* The login page, the admin area, AJAX and the REST API remain reachable, so you can always sign in and turn the mode off.

No complex configuration required: one checkbox to enable it and one dropdown to pick the page.

== Installation ==

1. Upload the `acw-maintenance-mode-by-page` folder to `/wp-content/plugins/`, or install the plugin from the WordPress dashboard.
2. Activate the plugin through the "Plugins" screen.
3. Go to **Settings → Maintenance**.
4. Check "Enable maintenance", choose the page to display and save.

== Frequently Asked Questions ==

= Do logged-in users see the maintenance page? =

No. Logged-in users browse the full site. Maintenance mode only affects logged-out visitors.

= Does the maintenance page show the theme header and footer? =

No, only the page content is shown through a minimal template. The theme styles (CSS/fonts) are still loaded.

= Is this a security feature for private content? =

No. It is a maintenance mode: it blocks browser navigation of the theme-rendered pages. It does not protect content served through the REST API, feeds or direct media file URLs. To protect truly private content you need server-level protection.

= How do I turn it off if I get locked out? =

The login page (`wp-login.php`) and the admin area always remain accessible. Sign in and disable the mode from the settings.

== Screenshots ==

1. The settings screen: enable the mode, choose the page and pick the content width.

== Changelog ==

= 1.0.0 =
* First release.
