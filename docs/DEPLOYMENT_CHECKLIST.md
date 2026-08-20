# Phase 6 Deployment Checklist

- [ ] Database upgrade completed (`nb_accounts_db_version` current).
- [ ] Required plugin tables exist and indexes are present.
- [ ] Scheduled events exist (`nb_accounts_daily_event`, `nb_accounts_hourly_event`).
- [ ] Dashboard, earnings, profile and payout caches warm correctly.
- [ ] Cache invalidation verified on post/user updates.
- [ ] Dashboard and form load-time targets validated.
- [ ] Lighthouse Performance score > 90 recorded.
- [ ] Authentication and editorial workflow smoke tests passed.
- [ ] Security checks passed (nonce/capability/prepared SQL/escaping).
- [ ] No PHP warnings/errors and no JS console errors.
- [ ] Cross-browser smoke tests passed.
- [ ] Rollback plan confirmed (plugin rollback + DB backup restore).
