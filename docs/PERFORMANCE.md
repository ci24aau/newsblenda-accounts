# Performance Optimization Documentation

## Targets

| Metric | Target |
|--------|--------|
| Dashboard load time | < 2 seconds |
| Form page load time | < 1.5 seconds |
| Lighthouse score | > 90 |
| Database queries (combined per page) | < 500 ms |
| First Contentful Paint | < 1.8 s |
| Largest Contentful Paint | < 2.5 s |
| Cumulative Layout Shift | < 0.1 |

---

## Optimizations Implemented

### Database

- Indexes added on author, status, date, and meta-key columns via `Migration001AddIndexes`.
- Efficient JOINs used inside `QueryOptimizer` instead of multiple sequential queries.
- Transient caching in `CacheManager` for author stats and notification counts (TTL: 1 hour).
- Automatic cache invalidation on `save_post`, `delete_post`, and `update_user_meta` hooks.
- All listings support pagination (50 results per page by default).

### Assets

- Assets registered via `AssetManager` and enqueued only on the pages that need them.
- `design-system.css` and `components.css` are shared base sheets; page-specific sheets
  (`dashboard.css`, `auth.css`, `admin.css`) are loaded only where required.
- JavaScript is deferred (`wp_script_add_data( …, 'defer', true )`).
- Version constant used for all asset handles to enable cache busting on deploy.

### Caching

- Author statistics cached 1 hour (`nb_author_stats_{user_id}`).
- Unread notification counts cached 1 hour (`nb_unread_notifs_{user_id}`).
- User profile data cached 1 hour (`nb_user_profile_{user_id}`).
- Invalidation runs automatically when posts or user meta change.

### Cron Jobs

- `nb_accounts_daily_earnings` – single aggregated query calculates view-based earnings
  for all authors each day.
- `nb_accounts_process_payouts` – batch query selects users above the payout threshold
  and fires the `nb_accounts_process_payout` action for downstream processing.

---

## Monitoring

### WordPress Query Monitor (Development)

1. Set `WP_DEBUG = true` and `WP_DEBUG_LOG = true` in `wp-config.php`.
2. Install the [Query Monitor](https://wordpress.org/plugins/query-monitor/) plugin.
3. **Dashboard** tab → review total query count and slow-query warnings.
4. **Queries** tab → identify duplicate or N+1 queries.
5. **Transients** tab → verify cache hit/miss rates.

### Lighthouse Audits

```bash
# Chrome DevTools
1. Open DevTools (F12 / Cmd+Opt+I)
2. Select the "Lighthouse" tab
3. Click "Analyze page load"
4. Review Performance, Accessibility, Best Practices, SEO scores
```

Target: all scores ≥ 90.

### Load Testing

```bash
# Quick response-time check with curl
time curl -s -o /dev/null -w "%{http_code} %{time_total}s\n" https://site.com/dashboard
time curl -s -o /dev/null -w "%{http_code} %{time_total}s\n" https://site.com/register
```

Targets: dashboards < 2 s, form pages < 1.5 s.
