# ACW Maintenance Mode by Page

A WordPress plugin that puts your site into maintenance mode by showing logged-out
visitors a single page of your choice, picked from your existing pages. You choose
which user roles keep browsing the whole site.

No dedicated templates and no page builder required: you design the page with your
own theme, then simply select it.

## Features

- Redirects logged-out visitors to a single page chosen from your existing Pages.
- Choose which user roles keep browsing the site and using the dashboard
  (administrators always can); other users see the maintenance page.
- Front-end login/registration/lost password pages (e.g. Paid Memberships Pro) stay
  reachable, plus any page you add to "Pages always reachable".
- If the selected page is deleted, unpublished or password-protected, visitors get
  a generic 503 message.
- Saving purges the most common page caches (SiteGround, WP Rocket, LiteSpeed,
  W3 Total Cache, WP Super Cache, WP Fastest Cache).
- The maintenance page is served with an HTTP `503 Service Unavailable` status and a
  `Retry-After` header — the correct signal for search engines during a temporary
  maintenance.
- Minimal template: shows **only the page content**, without the theme header,
  footer and menu.
- Content width option: **default** (inherited from the theme via `theme.json` or
  `$content_width`) or **full width**.
- XML sitemaps answer 503 too, while `robots.txt` is served normally, so search
  engines see that the pause is temporary.
- Optional: keep tracking codes (Analytics, Tag Manager, pixels, cookie banner) off
  the maintenance page, keeping the styles and the scripts of WordPress, of the theme
  and of the blocks used in the page. Useful on staging sites.
- Optional: hide pages, posts and other content from the REST API to visitors who are
  not signed in (signed-in users and application passwords keep working).
- The login page and AJAX stay reachable, and administrators always keep the
  dashboard, so you can always sign in and turn maintenance off.

## Installation

1. Copy the `acw-maintenance-mode-by-page` folder to `wp-content/plugins/`.
2. Activate the plugin from the **Plugins** screen.
3. Go to **Settings → Maintenance**.
4. Enable maintenance, choose the page to display and the roles with access, then save.

## Notes

This is a maintenance mode, not a security barrier: it blocks browser navigation of
the theme-rendered pages. It does not protect direct media file URLs, and the REST
API only when the "REST API" option is checked. To protect truly private content, use server-level
protection.

## License

[GPLv2 or later](LICENSE).
