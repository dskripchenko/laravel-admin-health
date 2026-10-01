# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Entries for releases published before this file existed were reconstructed from
the tagged commit history.

## [Unreleased]

### Changed
- `AdminHealthPlugin::version()` reports the installed package version from
  Composer instead of a hardcoded `0.1.0` that had not matched any release
  since the first one. Falls back to `dev` when the version cannot be resolved.
- User-facing strings — the menu group, permission labels, the resource's
  labels and filters, check names and result messages, the top-bar indicator
  and the dashboard card — go through the translator. The Russian text stays the
  translation key; messages that were built by concatenation now use
  placeholders.
- The status filter in the results list shows the same words as the dashboard
  card instead of the raw `OK` / `Warning` / `Failing`.

### Added
- English translations (`resources/lang/en.json`), loaded by the service
  provider as JSON translations.
- A weekly scheduled CI run, so a breaking upstream release surfaces without
  waiting for the next commit.

## [v1.4.0] - 2026-08-17

### Added
- **The status indicator in the top bar** — the surface the pack was specified
  with and shipped without. Until now the checks answered only when someone
  remembered to open their section, which is the opposite of what a health check
  is for. A dot appears the moment something fails, on every page of the panel,
  and nothing at all is drawn while every check passes. The `topbar_indicator`
  config flag, which had described a component that did not exist, now means
  something.
- **A dashboard card** with the counts per state. "Never run" gets a card of its
  own only when it happens: a check registered and never executed almost always
  means the scheduler was never wired up.
- **`HealthSummary`** — the latest result of every check, from one query, cached
  for a few seconds and dropped after every run. Both surfaces read it, so the
  header and the dashboard cannot disagree.

### Changed
- Requires `dskripchenko/laravel-admin` ^1.30 — the release that added the
  status-indicator contract and a reader for the widget registry.

### Fixed
- **The documentation described an API that never existed**: a
  `Contracts\HealthCheck` with `key()`/`check()`, `HealthResult::failed()`, a
  `config/health.php` with `checkers`, a `--tag=health-config`, and seven
  built-in checkers of which three were never written. Rewritten against the
  code: `HealthCheck` with `id()`/`run()`, `config/admin-health.php`,
  `--tag=admin-health-config`, and the four checks that actually ship. The
  getting-started page also never mentioned that nothing runs the checks without
  a scheduler entry.
- `HealthCheck::timeout()` claimed the runner aborts a check that hangs. It does
  not — PHP cannot be interrupted mid-call — and the docblock now says what the
  number is really for.

## [v1.3.0] - 2026-07-20

### Changed
- Supported versions moved to the canonical matrix: PHP 8.2-8.5 with Laravel 11, 12 and 13.

### Added
- GitHub Actions pipeline covering the whole support matrix.
- Documentation in German, Russian and Chinese alongside the English default.

## [v1.2.0] - 2026-05-01

### Changed
- Version aligned with the admin core release line. No functional changes.

## [v1.0.0] - 2026-05-01

### Added
- First standalone release, extracted from the laravel-admin monorepo.
- Packagist metadata: description, keywords, authors and support links.
