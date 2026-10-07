# Security policy

## Supported versions

Security fixes are provided for the latest minor release.

| Version | Supported |
| --- | --- |
| 0.1.x | Yes |

## Reporting a vulnerability

Do not open a public issue for a suspected vulnerability. Use the repository's private security advisory form:

https://github.com/farvisun/mindio-magic-ai/security/advisories/new

Include the affected version, prerequisites, impact, steps to reproduce, and a minimal proof of concept. Do not include live API keys, customer data, or production database content.

You should receive an acknowledgement within five business days. Publication is coordinated once a fix and an upgrade path are available.

## Design

- **Keys.** Provider keys and extra headers are encrypted at rest (libsodium secretbox, or AES-256-GCM where sodium is missing) with a key derived from `MINDIO_MAGIC_AI_SECRET_KEY` or the site's auth salt. They are never returned by the REST API, sent to the browser, or logged. A key is bound to the host and port it was saved for; changing a connection's address deletes its key.
- **Outbound requests.** Every provider request goes through the WordPress HTTP API with a URL guard that rejects private, loopback, link-local, CGNAT, benchmark, NAT64, and other reserved ranges, URLs with credentials, and redirects. The validated IP address is pinned for the connection, which closes the DNS-rebinding window between the check and the request. Administrators can allow exact `host:port` pairs for self-hosted models. A proxy configured with `WP_PROXY_*` resolves names itself, so the pin does not apply through a proxy.
- **Capabilities.** Access is granted per role through a `user_has_cap` filter; nothing is written to the roles option. Connections, network allowlists, access, and the pause switch always require `manage_options` (super admin on multisite). Every REST route and every tool checks capabilities on the server.
- **Writes.** The model can only propose a change. A proposal is bound to the user, the conversation, a hash of its arguments, and a fingerprint of the target. Apply is a single atomic state transition, re-checks the capability and the fingerprint, and records a changeset that Undo can restore. Auto-apply is opt-in and limited to creating drafts.
- **Untrusted text.** Tool results that contain visitor-supplied text are wrapped as untrusted data, and a run that read such text never applies anything automatically.
- **Output.** Model text is rendered as React elements without HTML. Images in model output are never loaded, and links are only clickable for this site and allowlisted hosts. Content written to posts is filtered with `wp_kses_post`.
- **Cost.** Budgets reserve the worst-case cost of a call with an atomic counter before it starts and settle the real cost afterwards.

## Out of scope

Mindio Magic AI cannot protect a site whose administrator account, server, database, or another plugin is compromised. Prompt injection cannot be ruled out with language models; the review-and-undo design limits what an injected instruction can do, but site owners should read proposals before applying them.
