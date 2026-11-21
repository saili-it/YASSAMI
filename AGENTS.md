# AGENTS.md

The full project specification and development guide lives in [CLAUDE.md](CLAUDE.md). Read it before making any change.

Key reminders:
- Laravel 13 + MySQL, run **only through Docker** (no PHP/Composer on the host).
- Never inject `APP_*` / `DB_*` env vars into the `app` container. Never remove the database guard in `tests/TestCase.php`.
- Never invent business information (WhatsApp number, fees, policies, socials).
- No commit, push, deploy, or destructive database operation without explicit approval.
