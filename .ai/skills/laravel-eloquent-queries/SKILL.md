---
name: laravel-eloquent-queries
description: "Laravel Eloquent query helpers for this project. Activates when writing Eloquent queries, filtering by relationships, or using whereHas."
---

# Laravel Eloquent Query Helpers

## Use `whereRelation()` Instead of `whereHas()` for Simple Conditions

When filtering by a single condition on a relationship, always use `whereRelation()` instead of `whereHas()` with a closure. It's shorter and more readable.

```php
// ✅ Correct
TimesheetEntry::whereRelation('user', 'id', $user->id)->get();
Post::whereRelation('author', 'verified', true)->get();
Post::whereRelation('comments', 'published_at', '>=', now()->subMonth())->get();

// ❌ Wrong
TimesheetEntry::whereHas('user', fn ($q) => $q->where('id', $user->id))->get();
Post::whereHas('author', fn ($q) => $q->where('verified', true))->get();
```

## Use `whereBetween()` for Range Conditions

When filtering a column between two values, use `whereBetween()` instead of two chained `where()` calls.

```php
// ✅ Correct
Entry::whereBetween('date', [$from, $to])->get();
Invoice::whereBetween('amount', [100, 1000])->get();

// ❌ Wrong
Entry::where('date', '>=', $from)->where('date', '<=', $to)->get();
```

Also works inside closures:

```php
// ✅ Correct
->whereHas('timesheetEntries', fn ($q) => $q->whereBetween('date', [$from, $to]));

// ❌ Wrong
->whereHas('timesheetEntries', fn ($q) => $q->where('date', '>=', $from)->where('date', '<=', $to));
```

Use `whereNotBetween()` for the inverse.

## Use `whereRelation()` Instead of `whereHas()` for Simple Conditions

When filtering by a single condition on a relationship, always use `whereRelation()` instead of `whereHas()` with a closure. It's shorter and more readable.

```php
// ✅ Correct
TimesheetEntry::whereRelation('user', 'id', $user->id)->get();
Post::whereRelation('author', 'verified', true)->get();
Post::whereRelation('comments', 'published_at', '>=', now()->subMonth())->get();

// ❌ Wrong
TimesheetEntry::whereHas('user', fn ($q) => $q->where('id', $user->id))->get();
Post::whereHas('author', fn ($q) => $q->where('verified', true))->get();
```

Use `whereHas()` with a closure when `whereRelation()` is not enough: multiple conditions, scopes, or helpers like `whereBetween()` (which `whereRelation()` cannot call internally).

**Exception — `BelongsToThrough` relations:** `whereRelation()` and `whereHas()` do not correctly traverse multi-level `BelongsToThrough` relationships (from the `znck` package). Use `whereIn` through the intermediate IDs instead:

```php
// ❌ Fails — BelongsToThrough doesn't implement getRelationExistenceQuery() correctly
TimesheetEntry::whereRelation('user', 'id', $user->id)->get();
TimesheetEntry::whereHas('user', fn ($q) => $q->where('id', $user->id))->get();

// ✅ Correct — traverse the chain explicitly
$clientIds = $user->clients()->withTrashed()->pluck('id');
TimesheetEntry::whereIn('project_id', Project::withTrashed()->whereIn('client_id', $clientIds)->pluck('id'))->get();
```

```php
// ✅ whereHas is appropriate here (multiple conditions)
Post::whereHas('comments', function ($q) {
    $q->where('published_at', '>=', now()->subMonth())
      ->where('approved', true);
})->get();

// ✅ whereHas required — whereRelation() cannot use whereBetween()
->whereHas('timesheetEntries', fn ($q) => $q->whereBetween('date', [$from, $to]));
```
