# Mindio Magic AI

An AI assistant for WordPress and WooCommerce admins. It works with your own API key from Anthropic, OpenAI, Google Gemini, DeepSeek, or any OpenAI-compatible service, and every change it makes is shown for review first and can be undone.

- **Assistant** page, an **Ask AI** side panel on every admin screen, and an **Ask Mindio AI** Command Palette command.
- **21 tools** for content, comments, SEO (Yoast SEO, Rank Math), and WooCommerce. Writes become proposals with a before-and-after view; Apply, Reject, and Undo with conflict detection.
- **Cost control:** usage and estimated cost per day and model, budgets that reserve before each call, and a site-wide pause switch.
- **Security:** encrypted keys bound to their host, an SSRF guard with DNS pinning, per-role access, untrusted-content handling, and an activity log.
- **Persian and RTL:** full Persian translation, mirrored layout, Solar Hijri dates, bundled Vazirmatn.

See [readme.txt](readme.txt) for the user-facing description, the external services the plugin talks to, and the FAQ, and [SECURITY.md](SECURITY.md) for the security design and how to report a vulnerability.

## Requirements

WordPress 6.6 or newer, PHP 8.1 or newer. WooCommerce is optional.

## Development

```sh
npm install
npm run build          # build/admin.js, build/drawer.js and their styles (LTR and RTL)
npm run start          # watch mode
npm run lint:js
npm run test:unit      # Jest tests for the browser code
```

PHP dependencies are prefixed with [Strauss](https://github.com/BrianHenryIE/strauss) into `vendor-prefixed/` (namespace `MindioMagicAI\Vendor\`), so they cannot clash with other plugins:

```sh
composer install
composer prefix        # slow (several minutes); only needed when dependencies change
```

`vendor-prefixed/` is committed and shipped. `vendor/` is not.

### Tests

The integration suites run against a real WordPress install with the plugin active and a scripted mock of the Anthropic and OpenAI APIs (`tests/mock/provider.php`), so no real keys are needed:

```sh
WP_PATH=/path/to/wordpress composer test
WP_PATH=/path/to/wordpress bin/test.sh security     # one suite
```

### Translations

```sh
npm run build && composer i18n
```

`bin/i18n.sh` regenerates the POT, merges it into each PO, and compiles the MO files and the JSON files for the scripts. It needs WP-CLI and gettext.

Only the POT ships in the release zip. WordPress.org delivers translations from translate.wordpress.org, so import `languages/mindio-magic-ai-fa_IR.po` there after each release. To see the Persian interface on a local site, copy `languages/*-fa_IR*.{mo,json}` into `wp-content/languages/plugins/`.

### Release

```sh
npm run build && composer i18n && composer build
```

`bin/build-release.sh` checks that the version and the readme's Stable tag match, then writes `dist/mindio-magic-ai-<version>.zip` using `.distignore`. It also leaves out the SDK's unused cloud-platform clients. Run Plugin Check on the result before uploading.

After WordPress.org approves the plugin, publish each release through SVN:

```sh
svn checkout https://plugins.svn.wordpress.org/mindio-magic-ai/ ../wordpress-org-mindio-magic-ai
bin/prepare-wordpress-org.sh ../wordpress-org-mindio-magic-ai
svn commit ../wordpress-org-mindio-magic-ai -m "Release X.Y.Z" --username <wordpress.org user>
```

The script copies the zip's contents to `trunk/`, the directory artwork in `.wordpress-org/` to `assets/`, and creates `tags/<version>`. SVN uses its own password, set in your WordPress.org profile.

## Architecture

| Path | Contents |
| --- | --- |
| `src/Core` | Boot, installer and schema, settings, access, encryption, URL guard, counters, rate limits, budgets, usage ledger, audit log, pause switch |
| `src/AI` | Provider presets and connections, the Anthropic (official SDK) and OpenAI-compatible providers, the WordPress HTTP transport with Fiber-based streaming |
| `src/Agent` | Tool registry and tools, schema validation, proposals, changesets and undo, run events |
| `src/Assistant` | Conversations, the system prompt, and the run loop |
| `src/Rest` | REST routes under `mindio-magic-ai/v1` and the server-sent events stream |
| `src/Admin` | Admin pages, the toolbar side panel, Site Health, and privacy hooks |
| `assets/src` | React admin app and the side panel loader |

Hooks for extensions: `mindio_magic_ai_register_tools` (action), `mindio_magic_ai_presets`, `mindio_magic_ai_provider`, `mindio_magic_ai_system_prompt`, and `mindio_magic_ai_network_locks_connections` (filters).

## License

GPL-2.0-or-later.
