
# Deployment Checklist

## Pre-deployment Validation
- [ ] Confirm the production branch contains the approved Phase 6 changes only.
- [ ] Review the diff for unexpected asset, dependency, or generated file changes.
- [ ] Back up the production database before deployment.
- [ ] Back up the current plugin directory before deployment.
- [ ] Confirm WordPress and PHP versions meet plugin requirements.
- [ ] Verify all new PHP files pass syntax checks.
- [ ] Verify database migration classes are present and load correctly.
- [ ] Verify minified CSS and JavaScript assets are included in the deployment artifact.
- [ ] Confirm cron hooks are registered for daily earnings and payouts.
- [ ] Confirm cache invalidation hooks are present for post, workflow, and user updates.
- [ ] Review PERFORMANCE.md targets with the release owner.
- [ ] Confirm the QA checklist has been completed for the release candidate.

## Staging Deployment
- [ ] Deploy the plugin build to staging.
- [ ] Activate or refresh the plugin on staging.
- [ ] Confirm activation completes without PHP warnings or fatal errors.
- [ ] Confirm migrations run and required indexes exist.
- [ ] Confirm existing plugin tables remain intact after migration.
- [ ] Verify registration, login, verification, and password reset flows.
- [ ] Verify author dashboard loads under the performance target.
- [ ] Verify editor dashboard loads under the performance target.
- [ ] Verify notifications, profile, earnings, and submission pages load their scoped assets only.
- [ ] Verify admin settings screens load admin assets and settings scripts correctly.
- [ ] Confirm daily earnings and payout cron events are scheduled.
- [ ] Confirm cache invalidation occurs after post saves, deletes, and workflow changes.

## Production Deployment
- [ ] Put the site into the agreed maintenance or low-traffic deployment window.
- [ ] Deploy the updated plugin files to production.
- [ ] Reactivate the plugin if required by the deployment method.
- [ ] Confirm activation and migrations complete successfully.
- [ ] Flush rewrite rules or permalinks if routing issues are observed.
- [ ] Warm critical frontend caches by loading login, registration, dashboard, profile, and notifications pages.
- [ ] Confirm cron hooks are present in WP-Cron after deployment.
- [ ] Run a post-deploy smoke test for author and editor flows.
- [ ] Record the deployed plugin version, timestamp, and operator.

## Post-deployment Monitoring
- [ ] Monitor PHP error logs for warnings, notices, and fatals.
- [ ] Monitor Query Monitor or APM output for slow dashboard queries.
- [ ] Check cache hit behavior for author stats and unread notification counts.
- [ ] Confirm daily earnings processing completes on the next scheduled run.
- [ ] Confirm payout processing completes on the next scheduled run.
- [ ] Validate there are no frontend or admin console errors.
- [ ] Watch user support channels for authentication, dashboard, or payout regressions.
