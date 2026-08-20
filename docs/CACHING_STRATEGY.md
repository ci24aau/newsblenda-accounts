# Phase 6 Caching Strategy

## Cache Keys and TTLs

### Dashboard
- `nb_dashboard_author_{user_id}_{version_hash}`: author dashboard fragment data, **1 hour**.
- `nb_dashboard_editor_{hash}`: editor dashboard fragment data, **15 minutes**.
- `nb_dashboard_cache_version`: global version seed for dashboard invalidation.
- `nb_dashboard_cache_metrics`: hit/miss counters for cache monitoring.

### Earnings
- `nb_earnings_summary_{user_id}_{version_hash}`: earnings summary snapshot, **1 day**.

### Profile
- `nb_profile_data_{user_id}`: cached profile data array, **1 hour**.
- `nb_profile_completion_{user_id}`: profile completion percentage, **1 hour**.

### Payouts
- `nb_payouts_statistics`: admin payout summary stats, **5 minutes**.

## Invalidation Rules

### Post updates
- Triggered on `save_post`, `delete_post`, and `transition_post_status`.
- Dashboard and earnings caches are invalidated for affected authors.
- `nb_dashboard_cache_version` is rotated to force versioned-key invalidation.

### User/Profile updates
- Triggered on `profile_update`, `added_user_meta`, `updated_user_meta`, `deleted_user_meta`.
- Profile transients are deleted for the affected user.
- Dashboard caches are invalidated for the affected user.
- Payout statistics cache is invalidated for fresh admin summaries.

### Payout events
- Triggered on `nb_accounts_payout_recorded`.
- Payout stats cache is invalidated.

## Operational Notes
- Keep TTLs short for admin operational summaries and longer for dashboard aggregates.
- Use versioned keys where broad invalidation is needed without wildcard delete support.
