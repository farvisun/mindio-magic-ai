=== Mindio Magic AI ===
Contributors: farvisun
Tags: ai, assistant, woocommerce, content, persian
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An AI assistant for WordPress and WooCommerce admins. Use your own API key. Every change is shown for review first and can be undone.

== Description ==

Mindio Magic AI adds an assistant to wp-admin that knows your site. Ask it to find content, summarize sales, draft a post, rewrite a product description, fix missing excerpts, or answer comments. It works through a fixed set of WordPress tools, and every change it wants to make appears as a card with a before-and-after view. Nothing is saved until you press Apply, and every applied change can be undone.

You bring your own API key from the provider you choose. Requests go straight from your server to that provider; there is no Mindio service in between and no telemetry.

= Providers =

* Anthropic Claude (Claude Opus 5.5, Sonnet 5.5, Haiku 4.5), using the official Anthropic PHP SDK
* OpenAI
* Google Gemini (through its OpenAI-compatible endpoint)
* OpenRouter, DeepSeek, AvalAI, and GapGPT
* Any OpenAI-compatible or Anthropic-compatible gateway at an address you enter, including a model server on your own network once an administrator allows its address

= What the assistant can do =

* **Content:** site overview, search and read posts, pages and custom post types, list terms, create drafts, update content, schedule and publish, set categories and tags.
* **Comments:** list comments, draft replies, and approve, unapprove, mark as spam, or move to the trash.
* **SEO:** read and update the SEO title and meta description in Yoast SEO or Rank Math.
* **WooCommerce:** search and read products, list low-stock products, summarize sales and top products from WooCommerce Analytics, update products, and create product drafts. Product changes go through WooCommerce, so lookup tables stay correct.

= Built for review and undo =

* Every write is a proposal bound to you, the conversation, and the exact version of the content it was made for. If someone edits that content first, the proposal is marked out of date instead of overwriting their work.
* Undo restores the version from before the change. If the content was edited after the change, Undo warns you before discarding those edits.
* Administrators can let users skip review for creating drafts only. Nothing is ever published, deleted, or sent without review.
* Text that came from visitors (comments, for example) is treated as untrusted. A run that read untrusted text never applies anything automatically.

= Where to find it =

* A full Assistant page with searchable, pinnable conversations and image attachments.
* An "Ask AI" button in the admin toolbar on every admin screen. It opens the assistant in a side panel that knows which screen and post you are on.
* "Ask Mindio AI" in the WordPress Command Palette (Ctrl+K or Cmd+K).

= Cost control =

* Usage page with tokens and estimated cost per day and per model, using a price table you can edit.
* Daily and monthly budgets. Each call reserves its worst-case cost before it starts, so parallel requests cannot overspend. A warning email is sent at 80 percent.
* A pause switch that stops every AI request on the site at once.

= Security =

* API keys are encrypted at rest, never sent to the browser, and never written to logs. After saving, only the last four characters are shown.
* A key is tied to the address it was saved for. Changing the address of a connection deletes its key.
* Requests to private and reserved network addresses are blocked unless an administrator allows an exact host and port.
* Access is managed per role. Capabilities are checked on the server for every request and every tool.
* An activity log records connection, setting, and access changes, every assistant run, and every change applied or undone, without message content or keys. It can be exported as CSV.

= Persian and right-to-left =

The interface is fully translated into Persian, mirrors correctly in right-to-left languages, shows dates in the Solar Hijri calendar with Persian digits for Persian users, and ships the Vazirmatn font.

= Coming next =

Editor sidebar actions and image generation, scheduled automations with templates, a knowledge base and a customer chat widget for your storefront, and an optional bridge to Mindio Magic MCP.

== External services ==

This plugin connects to an AI provider only after an administrator adds a connection with an API key, and only when a user sends a message, tests a connection, or refreshes its model list. It does not contact any service on its own, and it does not send telemetry.

**What is sent:** the conversation (your messages, attached images, and earlier replies), the assistant's instructions, the list of tools it can use, the results of tools it ran (for example the title and text of the posts or products it looked up, or order totals from a sales summary), the name of the current admin screen, and an anonymous user identifier (a hash, not your user ID or email). Testing a connection or refreshing models requests the provider's model list. The API key is sent in a request header.

**When:** each time a user sends a message to the assistant, and when an administrator tests a connection or refreshes its models.

The plugin talks to the service of the connection you choose:

* **Anthropic** (api.anthropic.com), the Claude API. [Commercial Terms](https://www.anthropic.com/legal/commercial-terms), [Privacy Policy](https://www.anthropic.com/legal/privacy).
* **OpenAI** (api.openai.com). [Business Terms](https://openai.com/policies/business-terms/), [Privacy Policy](https://openai.com/policies/privacy-policy/).
* **Google Gemini API** (generativelanguage.googleapis.com). [Gemini API Additional Terms](https://ai.google.dev/gemini-api/terms), [Google Privacy Policy](https://policies.google.com/privacy).
* **OpenRouter** (openrouter.ai), which forwards requests to the model provider you pick there. [Terms](https://openrouter.ai/terms), [Privacy Policy](https://openrouter.ai/privacy).
* **DeepSeek** (api.deepseek.com). [Open Platform Terms of Service](https://platform.deepseek.com/downloads/DeepSeek%20Open%20Platform%20Terms%20of%20Service.html), [Privacy Policy](https://cdn.deepseek.com/policies/en-US/deepseek-privacy-policy.html).
* **AvalAI** (api.avalai.ir), a gateway to several model providers. Terms and privacy policy: [avalai.ir](https://avalai.ir/).
* **GapGPT** (api.gapgpt.app), a gateway to several model providers. [Terms](https://gapgpt.app/terms), [Privacy Policy](https://gapgpt.app/privacy).
* **A custom address** that you enter. Data goes to that address under the terms of whoever runs it.

== Installation ==

1. Install and activate the plugin. PHP 8.1 or newer is required.
2. Go to **Mindio AI → Connections** and add a connection with your API key.
3. Open **Mindio AI → Assistant**, or press **Ask AI** in the admin toolbar, and ask a question.
4. Optional: in **Mindio AI → Settings**, choose which roles may use the assistant, and set budgets under **Usage & budgets**.

To keep a key out of the database, define it in `wp-config.php`, for example `define( 'MINDIO_MAGIC_AI_ANTHROPIC_KEY', 'sk-ant-…' );`. The constants are `MINDIO_MAGIC_AI_ANTHROPIC_KEY`, `MINDIO_MAGIC_AI_OPENAI_KEY`, `MINDIO_MAGIC_AI_GEMINI_KEY`, `MINDIO_MAGIC_AI_OPENROUTER_KEY`, `MINDIO_MAGIC_AI_DEEPSEEK_KEY`, `MINDIO_MAGIC_AI_AVALAI_KEY`, and `MINDIO_MAGIC_AI_GAPGPT_KEY`. A constant key is only ever sent to that provider's own address.

== Frequently Asked Questions ==

= Does the assistant change my site without asking? =

No. Every change is a proposal you apply yourself. The one exception is optional and limited: an administrator can allow users to create drafts without review, and each user then decides whether to turn it on for themselves.

= What does it cost? =

The plugin is free. You pay your AI provider for what you use, at their prices. The Usage page shows an estimate based on a price table you can edit, and budgets stop requests before they exceed your limit.

= Which model should I use? =

Claude Opus 5.5 gives the best results for multi-step work. Claude Haiku 4.5 or a small OpenAI-compatible model is cheaper for simple questions. Each conversation keeps the model it started with.

= Can I use a model running on my own server? =

Yes. Add an OpenAI-compatible connection with its address, for example `http://192.168.1.20:11434/v1`. Private addresses are blocked by default, so an administrator first adds the exact host and port under **Settings → Network and links**.

= Answers appear all at once instead of word by word =

Some hosts and proxies buffer streamed responses. The assistant notices this after a few seconds and switches to checking for new output instead, so it keeps working. You can also choose this mode under **Settings → Network and links**.

= Is my API key safe? =

Keys are encrypted with a key derived from your site's salts, or from `MINDIO_MAGIC_AI_SECRET_KEY` if you define it in `wp-config.php`. They are never sent to the browser or written to logs. If your salts change, the plugin tells you to re-enter your keys instead of failing silently.

= How do I stop all AI requests immediately? =

Use **Pause AI** on the Usage page, or define `MINDIO_MAGIC_AI_DISABLED` as `true` in `wp-config.php`.

= What happens to my data when I delete the plugin? =

By default your conversations, connections, and logs are kept, in case you reinstall. Turn on **Delete all Mindio AI data when the plugin is deleted** in Settings to remove everything on uninstall. Conversations and logs are also removed automatically after the retention periods you set.

= Is the source code available? =

Yes. The JavaScript and style sources ship in `assets/src`, and the build setup is in `package.json` and `webpack.config.js` (run `npm install` and `npm run build`). Development happens at https://github.com/farvisun/mindio-magic-ai.

== Screenshots ==

1. The assistant proposes an edit, with a before-and-after view, Apply, and Reject.
2. The assistant in a side panel, opened from the admin toolbar.
3. Connections to AI providers, with encrypted keys.
4. Usage, estimated cost, and budgets.
5. The interface in Persian, right to left.

== Changelog ==

= 0.1.0 =
* First release: the Assistant page, the toolbar side panel, and the Command Palette command.
* Connections for Anthropic, OpenAI, Google Gemini, OpenRouter, DeepSeek, AvalAI, GapGPT, and custom compatible gateways.
* Content, comment, SEO, and WooCommerce tools, with proposals, Apply, Reject, and Undo.
* Usage, budgets, the pause switch, access by role, and the activity log.
* Persian translation and right-to-left support.

== Upgrade Notice ==

= 0.1.0 =
First release.

== Credits ==

* Anthropic PHP SDK (MIT), Nyholm PSR-7 (MIT), PHP-HTTP Discovery (MIT), the PSR HTTP interfaces (MIT), and Standard Webhooks (Apache 2.0), bundled under a prefixed namespace in `vendor-prefixed` with their licenses.
* Icons from Lucide (ISC License).
* Vazirmatn font by Saber Rastikerdar and contributors (SIL Open Font License 1.1).
