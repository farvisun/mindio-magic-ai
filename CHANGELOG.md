# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [0.1.0] - Unreleased

### Added

- Assistant page with searchable, pinnable conversations, image attachments, streaming answers, and Stop.
- "Ask AI" side panel on every admin screen, aware of the current screen and post, and an "Ask Mindio AI" Command Palette command.
- Connections for Anthropic (official PHP SDK), OpenAI, Google Gemini, DeepSeek, and custom OpenAI-compatible services. Keys are encrypted and bound to their host.
- 21 tools for content, comments, SEO (Yoast SEO, Rank Math), and WooCommerce.
- Proposals with a before-and-after view, Apply, Reject, and Undo with conflict detection.
- Usage and estimated cost per day and model, an editable price table, daily and monthly budgets, and a pause switch.
- Access by role, an activity log with CSV export, privacy exporter and eraser, retention settings, and Site Health checks.
- Persian translation (kept in `languages/` and delivered through translate.wordpress.org), right-to-left layout, Solar Hijri dates, and the bundled Vazirmatn font.
- Streaming over server-sent events, with automatic fallback to polling on hosts that buffer responses.

[0.1.0]: https://github.com/farvisun/mindio-magic-ai/releases/tag/v0.1.0
