# Performance Optimization Documentation

## Performance Targets

- Dashboard load time: < 2 seconds
- Form pages: < 1.5 seconds
- Lighthouse score: > 90
- Database queries combined: < 500ms per page
- First Contentful Paint: < 1.8s
- Largest Contentful Paint: < 2.5s
- Cumulative Layout Shift: < 0.1

## Optimizations Implemented

### Database Layer
- Indexed `post_author/post_status`, `post_type/post_date`, and high-traffic plugin table columns.
- Centralized aggregate queries in `/includes/Classes/QueryOptimizer.php`.
- Prepared statements used for variable SQL inputs.
- Author dashboard stats and notification counts cached with 1-hour transients.

### Asset Layer
- Assets registered centrally in `/includes/Classes/AssetManager.php`.
- Frontend CSS/JS load only on auth, dashboard, profile, notifications, earnings, payout, and shortcode-backed screens.
- Admin assets load only on Newsblenda plugin screens.
- Deferred JavaScript enabled for registered frontend and admin bundles.
- Versioning uses `NB_ACCOUNTS_VERSION` for cache busting.

### Caching Layer
- Author statistics cached for 1 hour.
- User profile objects cached for 1 hour.
- Unread notification counts cached for 1 hour.
- Automatic invalidation on post saves/deletes and user meta changes.

### Cron Jobs
- Daily earnings reconciliation captures newly accrued article views into `wp_nb_earnings`.
- Scheduled payout processing creates or completes payout records based on earnings settings.
- User earnings and payout metadata refresh after scheduled processing.

## Monitoring

### Using Query Monitor
1. Set `WP_DEBUG = true` and `WP_DEBUG_LOG = true`.
2. Install the Query Monitor plugin.
3. Review dashboard, editor dashboard, profile, notifications, and earnings pages.
4. Watch for duplicate queries, slow meta lookups, or unbounded author/post queries.

### Lighthouse Audits
1. Open Chrome DevTools.
2. Run Lighthouse against login, dashboard, editor dashboard, and profile screens.
3. Target scores above 90 for Performance and Accessibility.

### Load Testing
Use curl to measure response headers on key routes:

```bash
time curl -I https://site.example/login
time curl -I https://site.example/dashboard
time curl -I https://site.example/editor-dashboard
time curl -I https://site.example/profile
```

Target: < 2s for dashboards, < 1.5s for forms.
