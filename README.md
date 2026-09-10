# Maintenance Mode by Page

A WordPress plugin that puts your site into maintenance mode by showing logged-out
visitors a single page of your choice, picked from your existing pages. Logged-in
users keep browsing the whole site normally.

No dedicated templates and no page builder required: you design the page with your
own theme, then simply select it.

## Features

- Redirects logged-out visitors to a single page chosen from your existing Pages.
- Logged-in users keep full access to the site.
- The maintenance page is served with an HTTP `503 Service Unavailable` status and a
  `Retry-After` header — the correct signal for search engines during a temporary
  maintenance.
- Minimal template: shows **only the page content**, without the theme header,
  footer and menu.
- Content width option: **default** (inherited from the theme via `theme.json` or
  `$content_width`) or **full width**.
- The login page, admin area, AJAX and the REST API stay reachable, so you can
  always sign in and turn maintenance off.

## Installation

1. Copy the `maintenance-mode-by-page` folder to `wp-content/plugins/`.
2. Activate the plugin from the **Plugins** screen.
3. Go to **Settings → Maintenance**.
4. Enable maintenance, choose the page to display, pick the content width and save.

## Notes

This is a maintenance mode, not a security barrier: it blocks browser navigation of
the theme-rendered pages. It does not protect content served through the REST API,
feeds or direct media file URLs. To protect truly private content, use server-level
protection.

## License

[GPLv2 or later](LICENSE).
