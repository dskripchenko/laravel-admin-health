---
title: Usage
locale: en
status: stable
---

# Usage

## What you see

The pack has three surfaces, and only one of them asks to be visited:

- **The top bar.** A dot appears the moment a check starts failing, on every
  page of the panel. Nothing is drawn while everything passes — a permanent
  green dot is decoration, and the point of a health check is to find you rather
  than to wait to be found.
- **The dashboard.** A card with the counts: passing, warnings, failures, and —
  only when it happens — how many checks have never run at all, which almost
  always means the scheduler was never wired up.
- **The results section**, the full history, for when one of those numbers is
  not zero. A user with `admin.system.health.run` also gets **Run checks now**
  in its header menu: every check runs at once, and the toast says how it went.

Both the indicator and the widget are registered by the plugin. Nothing has to
be placed on a dashboard by hand; a host that wants the widget somewhere
specific can still declare it in its own `widgets()` and it will not be
duplicated.

The two surfaces need `dskripchenko/laravel-admin` ^1.30.

## Scheduling the runs

The results come from the runner; nothing runs the checks by itself. Add both
commands to the scheduler — without the first one the panel keeps showing the
last answer it was given, however old:

```php
// routes/console.php
Schedule::command('admin:health:run')->everyMinute();
Schedule::command('admin:health:cleanup')->daily();
```

`admin:health:run` runs every registered check regardless of its `frequency()` —
respecting the frequency is the scheduler's job, which is why it is called every
minute and each check declares how often it actually wants to run.

## A check of your own

A check implements `HealthCheck`. It is stateless: the id, the name and the
timings are all it declares, and the history lives in `admin_health_results`.

```php
namespace App\Health;

use Dskripchenko\LaravelAdminHealth\HealthCheck;
use Dskripchenko\LaravelAdminHealth\HealthResult;

final class StripeApiCheck implements HealthCheck
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config = []) {}

    public function id(): string
    {
        return 'stripe.api';
    }

    public function name(): string
    {
        return 'Stripe API';
    }

    public function category(): string
    {
        return 'custom';
    }

    public function frequency(): string
    {
        return '5m';
    }

    public function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? 5);
    }

    public function run(): HealthResult
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.key'));
            \Stripe\Balance::retrieve();

            return HealthResult::ok('Ответ получен');
        } catch (\Throwable $e) {
            return HealthResult::failing('Stripe не отвечает: :message', [], ['message' => $e->getMessage()]);
        }
    }
}
```

Register it in the config. The key is the class, the value is the array handed
to its constructor:

```php
// config/admin-health.php
'checks' => [
    \App\Health\StripeApiCheck::class => ['timeout' => 10],
],
```

A check that throws is not a crash: the runner records it as `failing` with the
exception's class in the result's meta.

### Messages in the reader's language

A check runs in the scheduler's locale, and its result is read later by people
who may use another one. So a result carries its message untranslated: a
source string (a key of your JSON translations) plus its placeholders, the
arguments `__()` takes:

```php
HealthResult::warning(
    'Очередь :queue растёт: :size задач',  // the key, translated when read
    ['size' => $size],                      // meta: kept as it is
    ['queue' => 'mail', 'size' => $size],   // the placeholders
);
```

The runner stores both, and everything that shows a message translates it in
its own locale: the results list, the top-bar indicator, `HealthSummary`, the
`admin:health:run` output. In code, `$result->text()` is the translated
message; `$result->message` is the source. A string with no translation is
shown as it is, so a message that was already translated, or is not meant to
be, still works — it just stays in one language. Rows written by earlier
versions hold finished strings and are shown as they were stored.

## Three states, not two

`HealthResult::ok()`, `::warning()` and `::failing()`. The middle one exists so
that "the queue is 300 deep" can be said without waking anyone up: a warning
colours the header amber, a failure colours it red.

## Reading the state in code

`HealthSummary` answers what the panel answers, from one query — the latest row
of every registered check:

```php
$summary = app(\Dskripchenko\LaravelAdminHealth\HealthSummary::class);

$summary->overall();  // 'ok' | 'warning' | 'failing' | 'unknown'
$summary->counts();   // ['ok' => 4, 'warning' => 0, 'failing' => 1, 'never' => 0]
$summary->latest();   // per check id: status, message, ran_at — null when never run
```

It is cached for a few seconds, since the top-bar indicator polls once a minute
per open tab; the messages it returns are translated on every call, in the
current locale. The runner drops that cache after every run, so a manual "run the
checks" never leaves the header showing yesterday's answer.

`unknown` means nobody has looked — either nothing is registered, or nothing has
run yet.
