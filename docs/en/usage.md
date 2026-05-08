---
title: Usage
locale: en
status: stable
---

# Usage

```php
namespace App\Health;

use Dskripchenko\LaravelAdminHealth\Contracts\HealthCheck;
use Dskripchenko\LaravelAdminHealth\Result\HealthResult;

class StripeApiChecker implements HealthCheck
{
    public function key(): string { return 'stripe'; }
    public function label(): string { return 'Stripe API'; }
    public function check(): HealthResult
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.key'));
            \Stripe\Balance::retrieve();
            return HealthResult::ok();
        } catch (\Throwable $e) {
            return HealthResult::failed($e->getMessage());
        }
    }
}
```

```php
// config/health.php
'checkers' => [
    \App\Health\StripeApiChecker::class,
],
```

