# Phase 6 Performance Optimization

## Targets
- Dashboard page load: **< 2.0s**
- Form pages (login/register/profile): **< 1.5s**
- Lighthouse Performance: **> 90**
- No N+1 query regressions in dashboard flows

## Implemented Optimizations

### Database & Query Path
- Dashboard and editor dashboards use cached aggregate/statistical query paths.
- Post and author/meta caches are primed before table rendering to reduce repeated DB calls.
- Existing custom table indexes are defined in `/includes/Database/Database.php` for frequent filters and joins.

### Asset Loading
- Frontend assets are loaded conditionally through `Plugin::should_load_frontend_assets()`.
- Admin assets are now loaded only on Newsblenda plugin screens.
- Frontend and admin plugin scripts are marked deferred for non-blocking loading.
- Asset versioning uses `NB_ACCOUNTS_VERSION` for cache busting consistency.

### Cron
- Daily earnings synchronization is connected to `nb_accounts_daily_event`.
- Scheduled events are created on activation and cleaned up on deactivation.

## Verification Procedure
1. Enable Query Monitor and browse:
   - `/dashboard/`
   - `/editor-dashboard/`
   - `/profile/`
2. Confirm query counts do not spike with larger article sets.
3. Run Lighthouse on dashboard and profile pages and record scores.
4. Confirm no PHP warnings/notices and no JS console errors.

## Remaining Monitoring Guidance
- Track transient hit/miss metrics via option `nb_dashboard_cache_metrics`.
- Investigate any sustained cache miss ratio above 40% for dashboard fragments.
