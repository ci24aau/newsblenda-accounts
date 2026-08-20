# Newsblenda Accounts QA Checklist

## Pre-Release Validation

### Authentication (Phase 1)
- [ ] Registration flow completes successfully
- [ ] Email verification token flow works
- [ ] Login restrictions and lockout rules enforced
- [ ] Password reset request and submit flows work
- [ ] Verification/login/password rate limits active

### Dashboards & Workflow (Phase 3)
- [ ] Author dashboard loads and stats display correctly
- [ ] Editor dashboard loads and queue actions work
- [ ] Submission -> review -> approve/reject/revision workflow validated
- [ ] Notification counters and latest notifications are accurate

### Admin Settings (Phase 4)
- [ ] All settings tabs render and save successfully
- [ ] Field-level validation and error notices work
- [ ] SMTP connection test and test email actions work

### UI/UX (Phase 5)
- [ ] Mobile layout verified (<640px)
- [ ] Tablet layout verified (640px-1024px)
- [ ] Desktop layout verified (>1024px)
- [ ] Touch targets and focus states are usable

### Performance (Phase 6)
- [ ] Lighthouse Performance score > 90 on dashboard and profile
- [ ] Dashboard page load < 2.0s baseline
- [ ] Form pages load < 1.5s baseline
- [ ] No N+1 query regressions (Query Monitor)
- [ ] Cache hit/miss metrics observed in `nb_dashboard_cache_metrics`
- [ ] Assets load only on required frontend/admin screens
- [ ] Deferred scripts execute without console errors

### Database Integrity
- [ ] All required tables exist
- [ ] Indexes exist on key filtered columns
- [ ] Upgrade path and migrations execute without data loss

### Security
- [ ] Nonce validation on all state-changing requests
- [ ] Capability checks protect admin/editor operations
- [ ] Inputs sanitized and outputs escaped
- [ ] SQL statements prepared where variables are included
- [ ] CSRF/XSS/SQL injection spot checks passed

### Accessibility
- [ ] Keyboard navigation complete
- [ ] Screen-reader labels and structure verified
- [ ] Color contrast meets WCAG AA baseline
- [ ] Focus indicators visible for interactive controls

### Cross-Browser
- [ ] Chrome smoke test passed
- [ ] Firefox smoke test passed
- [ ] Safari smoke test passed
- [ ] Edge smoke test passed

### Final Regression
- [ ] No PHP errors/warnings in logs
- [ ] No console JavaScript errors
- [ ] Prior phase critical flows still working
- [ ] Performance and caching documentation reviewed
- [ ] Deployment checklist completed
