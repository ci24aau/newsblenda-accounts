# Deployment Checklist

## Pre-Deployment
- [ ] All QA tests passed
- [ ] Lighthouse scores > 90
- [ ] No PHP errors in logs
- [ ] No JavaScript console errors
- [ ] Database migrations tested on fresh install
- [ ] Cron jobs verified to schedule
- [ ] SMTP configured and tested
- [ ] Email templates tested
- [ ] All security validations complete

## Staging Deployment
- [ ] Deploy code to staging
- [ ] Run database migrations
- [ ] Test full registration flow
- [ ] Test login flow
- [ ] Test author dashboard (<2s load)
- [ ] Test editor dashboard (<2s load)
- [ ] Test settings page
- [ ] Test workflow (submit → approve → publish)
- [ ] Test notifications
- [ ] Test email delivery
- [ ] Run Lighthouse audit (>90)
- [ ] Load test with concurrent users

## Production Deployment
- [ ] Backup production database
- [ ] Deploy code
- [ ] Run database migrations
- [ ] Clear caches
- [ ] Verify plugin activated
- [ ] Verify cron jobs scheduled
- [ ] Test critical workflows
- [ ] Monitor error logs
- [ ] Verify email delivery

## Post-Deployment
- [ ] Monitor performance metrics
- [ ] Check error logs for issues
- [ ] Verify payouts processing
- [ ] Verify earnings tracking
- [ ] Monitor cache hit rates
- [ ] Gather user feedback
