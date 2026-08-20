# Phase 6 QA Test Plan

## 1. Authentication
- Registration, verification, login, logout, forgot/reset password.
- Validate rate limits and nonce/capability checks.

## 2. RBAC
- Author/editor/admin route and action permissions.
- Confirm author users cannot access restricted editor/admin actions.

## 3. Editorial Workflow
- Submit -> pending -> revision request/reject/approve -> publish.
- Verify status badges, queue visibility, and notification generation.

## 4. Notifications
- Create/read/unread/delete notification flows.
- Verify unread counts on dashboards.

## 5. Forms & Validation
- Required field handling, sanitization, and user-visible error states.
- Profile updates and settings save/validation paths.

## 6. Performance
- Dashboard page under 2.0s and forms under 1.5s in staging baseline.
- Lighthouse Performance > 90 on key pages.
- Confirm no N+1 query regressions with Query Monitor.

## 7. Accessibility
- Keyboard-only navigation across auth/profile/dashboard pages.
- Screen-reader labels on form controls and status notices.
- Color contrast checks for status badges and notices.

## 8. Security
- Nonce verification across POST actions.
- Capability checks enforced for admin/editor actions.
- Validate prepared SQL usage and escaped output in templates.
- Spot-check XSS/CSRF/SQL injection hardening paths.

## 9. Responsive & Browser Compatibility
- Mobile / tablet / desktop layout verification.
- Chrome, Firefox, Safari, Edge smoke checks.

## 10. Regression
- Re-run core flows from previous phases after performance changes.
- Verify no PHP errors/warnings and no browser console errors.
