# QA Checklist

This checklist covers 209 test cases across 17 validation phases for the Newsblenda Accounts release candidate.

## Authentication (15 tests)
- [ ] Auth 01: Register with valid author details and receive success state
- [ ] Auth 02: Register with required email verification enabled
- [ ] Auth 03: Register with admin approval enabled and confirm pending state
- [ ] Auth 04: Reject duplicate email registration attempts
- [ ] Auth 05: Reject duplicate username registration attempts
- [ ] Auth 06: Enforce required-field validation on registration
- [ ] Auth 07: Enforce password complexity requirements
- [ ] Auth 08: Confirm registration nonce validation
- [ ] Auth 09: Confirm registration rate limiting by IP/session
- [ ] Auth 10: Validate author role assignment for approved accounts
- [ ] Auth 11: Validate pending-author role assignment for approval-required accounts
- [ ] Auth 12: Confirm restricted accounts cannot access protected screens
- [ ] Auth 13: Confirm access-denied template renders for blocked users
- [ ] Auth 14: Confirm verification template renders correctly from emailed link
- [ ] Auth 15: Confirm registration-related emails use configured branding

## Password Reset (8 tests)
- [ ] Reset 01: Submit forgot-password form with valid account email
- [ ] Reset 02: Show neutral response for unknown email address
- [ ] Reset 03: Generate reset token with expiration
- [ ] Reset 04: Accept valid token and allow password change
- [ ] Reset 05: Reject expired token
- [ ] Reset 06: Reject reused/consumed token
- [ ] Reset 07: Enforce password reset rate limits
- [ ] Reset 08: Confirm password-reset success email or confirmation state

## Login & Session (7 tests)
- [ ] Login 01: Log in with valid credentials
- [ ] Login 02: Reject invalid credentials
- [ ] Login 03: Enforce lockout after repeated failures
- [ ] Login 04: Prevent pending users from entering the dashboard
- [ ] Login 05: Prevent restricted users from entering the dashboard
- [ ] Login 06: Confirm logout destroys the active session
- [ ] Login 07: Confirm session-protected pages redirect guests

## Author Dashboard (20 tests)
- [ ] Author 01: Dashboard route loads for approved authors
- [ ] Author 02: Published count matches authored published posts
- [ ] Author 03: Pending count matches authored pending posts
- [ ] Author 04: Draft count excludes rejected/revision-requested drafts
- [ ] Author 05: Rejected count reflects workflow-marked rejected drafts
- [ ] Author 06: Revision count reflects workflow-marked revision requests
- [ ] Author 07: Total submissions equals visible status totals
- [ ] Author 08: Total views render from cached aggregate query
- [ ] Author 09: Total earnings render from stored author earnings meta
- [ ] Author 10: Unpaid balance reflects unpaid earnings entries
- [ ] Author 11: Recent posts list loads most recently modified submissions
- [ ] Author 12: Pending posts section shows oldest pending items first
- [ ] Author 13: Revision posts section shows latest revision-requested items
- [ ] Author 14: Rejected posts section shows latest rejected items
- [ ] Author 15: Draft posts section shows author drafts without workflow regressions
- [ ] Author 16: Notifications preview renders latest four notifications
- [ ] Author 17: Notification badge count matches unread notifications
- [ ] Author 18: Profile completion percentage updates after profile edits
- [ ] Author 19: Quick links navigate to submit/profile/earnings/notifications
- [ ] Author 20: Dashboard cache invalidates after post or profile updates

## Editor Dashboard (20 tests)
- [ ] Editor 01: Editor dashboard route loads for editors
- [ ] Editor 02: Non-editors are redirected away from editor dashboard
- [ ] Editor 03: Pending review count matches queue rows
- [ ] Editor 04: Revision-request count matches revision rows
- [ ] Editor 05: Approved-unpublished count matches approved rows
- [ ] Editor 06: Total published count matches WordPress post counts
- [ ] Editor 07: Search filter narrows pending queue by title
- [ ] Editor 08: Author filter narrows pending queue by author
- [ ] Editor 09: Clear filter resets search and author constraints
- [ ] Editor 10: Pending queue displays article title, author, date, and word count
- [ ] Editor 11: Approve action transitions workflow correctly
- [ ] Editor 12: Reject action records rejection reason and updates workflow
- [ ] Editor 13: Revision action records feedback and updates workflow
- [ ] Editor 14: Publish action publishes approved drafts
- [ ] Editor 15: Recently published list loads expected posts
- [ ] Editor 16: Recent author list loads newest registered authors
- [ ] Editor 17: Editor notification preview renders latest five notifications
- [ ] Editor 18: Editor unread badge count matches unread notifications
- [ ] Editor 19: Editor dashboard cache invalidates after workflow actions
- [ ] Editor 20: No duplicate queries appear in editor dashboard baseline

## Editorial Workflow (15 tests)
- [ ] Workflow 01: Submit article creates draft or pending post as configured
- [ ] Workflow 02: Submit action records workflow metadata
- [ ] Workflow 03: Pending article appears in editor review queue
- [ ] Workflow 04: Revision request stores editor feedback
- [ ] Workflow 05: Revision request notifies the author
- [ ] Workflow 06: Revised article can be resubmitted
- [ ] Workflow 07: Reject action stores rejection reason
- [ ] Workflow 08: Rejected article remains visible in author rejected state
- [ ] Workflow 09: Approval action stores approved workflow status
- [ ] Workflow 10: Approved article is eligible for publish action
- [ ] Workflow 11: Publish action transitions post to `publish`
- [ ] Workflow 12: Workflow history table records each transition
- [ ] Workflow 13: Status badges render correct labels and classes
- [ ] Workflow 14: Editorial actions require valid nonces
- [ ] Workflow 15: Editorial actions require editor/admin capabilities

## Forms (13 tests)
- [ ] Forms 01: Login form validates required fields
- [ ] Forms 02: Registration form validates required fields
- [ ] Forms 03: Forgot-password form validates required email
- [ ] Forms 04: Reset-password form validates matching passwords
- [ ] Forms 05: Submit-article form validates required fields
- [ ] Forms 06: Profile form sanitizes text inputs
- [ ] Forms 07: Profile form sanitizes textarea inputs
- [ ] Forms 08: Settings forms persist valid values
- [ ] Forms 09: Settings forms reject invalid emails/URLs/numbers
- [ ] Forms 10: Form error notices are visible and understandable
- [ ] Forms 11: Form success notices are visible and understandable
- [ ] Forms 12: Deferred frontend scripts do not break form submission
- [ ] Forms 13: Shortcode-rendered forms load required assets only

## Admin Settings (10 tests)
- [ ] Settings 01: Main settings page loads for authorized administrators
- [ ] Settings 02: Unauthorized users cannot access settings
- [ ] Settings 03: General settings save successfully
- [ ] Settings 04: Registration settings save successfully
- [ ] Settings 05: Security settings save successfully
- [ ] Settings 06: Email settings save successfully
- [ ] Settings 07: Earnings settings save successfully
- [ ] Settings 08: Notification settings save successfully
- [ ] Settings 09: Validation errors render when invalid values are submitted
- [ ] Settings 10: SMTP test connection and test email actions succeed

## Notifications (10 tests)
- [ ] Notify 01: Notification shortcode page loads for logged-in users
- [ ] Notify 02: Latest notifications sort newest first
- [ ] Notify 03: Unread count matches unread rows in table
- [ ] Notify 04: Mark-read action updates the notification state
- [ ] Notify 05: Delete action removes only the selected user notification
- [ ] Notify 06: Mark-read action requires valid nonce
- [ ] Notify 07: Delete action requires valid nonce
- [ ] Notify 08: Notification cache invalidates after create/update/delete
- [ ] Notify 09: Notification action URLs preserve safe redirects
- [ ] Notify 10: Empty-state message renders when user has no notifications

## Mobile Responsiveness (12 tests)
- [ ] Mobile 01: Login page renders correctly at 320px width
- [ ] Mobile 02: Registration page renders correctly at 320px width
- [ ] Mobile 03: Author dashboard cards stack correctly on mobile
- [ ] Mobile 04: Editor dashboard tables remain usable on mobile
- [ ] Mobile 05: Profile form remains usable on mobile
- [ ] Mobile 06: Notifications table remains usable on mobile
- [ ] Mobile 07: Earnings page remains usable on mobile
- [ ] Mobile 08: Tablet layout renders correctly at 768px width
- [ ] Mobile 09: Desktop layout renders correctly at 1280px width
- [ ] Mobile 10: Touch targets remain accessible on primary actions
- [ ] Mobile 11: Sticky headers or notices do not overlap form fields
- [ ] Mobile 12: No horizontal overflow appears on key frontend screens

## Accessibility (10 tests)
- [ ] A11y 01: All auth forms are keyboard navigable
- [ ] A11y 02: Dashboard interactive controls are keyboard navigable
- [ ] A11y 03: Editor workflow modals are keyboard accessible
- [ ] A11y 04: Form fields have visible labels or accessible names
- [ ] A11y 05: Error and success notices are announced appropriately
- [ ] A11y 06: Focus indicators are visible across primary controls
- [ ] A11y 07: Color contrast meets WCAG AA for badges/notices/buttons
- [ ] A11y 08: Tables use meaningful header structure
- [ ] A11y 09: Links and buttons have descriptive text
- [ ] A11y 10: Lighthouse Accessibility score remains above 90

## Security (15 tests)
- [ ] Security 01: Registration requests require valid nonce
- [ ] Security 02: Login requests require valid nonce where applicable
- [ ] Security 03: Password-reset requests enforce token expiration
- [ ] Security 04: Password-reset submission rejects consumed tokens
- [ ] Security 05: Editorial actions require editor/admin capabilities
- [ ] Security 06: Admin settings require administrator capabilities
- [ ] Security 07: Notification actions require logged-in ownership checks
- [ ] Security 08: POST/GET inputs are sanitized before persistence
- [ ] Security 09: Template output is escaped appropriately
- [ ] Security 10: SQL queries with variables are prepared
- [ ] Security 11: Login throttling/lockout resists brute force attempts
- [ ] Security 12: Registration throttling resists spam submissions
- [ ] Security 13: Password-reset throttling resists abuse
- [ ] Security 14: No secrets are embedded in plugin source or docs
- [ ] Security 15: Scheduled payout processing does not bypass configuration

## Email Delivery (10 tests)
- [ ] Email 01: Welcome or approval email sends successfully
- [ ] Email 02: Verification email sends successfully
- [ ] Email 03: Password-reset email sends successfully
- [ ] Email 04: Revision-request email sends successfully
- [ ] Email 05: Article-approved email sends successfully
- [ ] Email 06: Article-rejected email sends successfully
- [ ] Email 07: Payout-processed email sends successfully when enabled
- [ ] Email 08: Email templates include configured branding
- [ ] Email 09: Email links use the correct frontend routes
- [ ] Email 10: SMTP test email succeeds with configured provider

## Earnings & Payouts (10 tests)
- [ ] Earnings 01: Daily earnings cron hook is scheduled
- [ ] Earnings 02: Payout-processing cron hook is scheduled
- [ ] Earnings 03: Daily earnings sync creates unpaid earnings rows for new views
- [ ] Earnings 04: `nb_last_earnings_views` prevents duplicate daily accrual
- [ ] Earnings 05: `nb_total_earnings` meta refreshes after sync
- [ ] Earnings 06: `nb_unpaid_balance` meta refreshes after sync
- [ ] Earnings 07: Manual payout processing updates paid totals and balances
- [ ] Earnings 08: Auto-payout mode creates paid payout records when enabled
- [ ] Earnings 09: Manual-review mode creates pending payout records when auto payout is disabled
- [ ] Earnings 10: Admin payout/reporting summaries match stored earnings and payout rows

## Performance (10 tests)
- [ ] Perf 01: Dashboard load time is below 2 seconds
- [ ] Perf 02: Editor dashboard load time is below 2 seconds
- [ ] Perf 03: Login/register/reset/profile form pages load below 1.5 seconds
- [ ] Perf 04: Lighthouse Performance score is above 90 on dashboard
- [ ] Perf 05: Lighthouse Performance score is above 90 on profile
- [ ] Perf 06: Combined dashboard query time remains below 500ms
- [ ] Perf 07: Cached author stats avoid repeated aggregate queries
- [ ] Perf 08: Cached unread notification counts avoid repeated count queries
- [ ] Perf 09: Assets load only on relevant frontend/admin screens
- [ ] Perf 10: Deferred scripts execute without console errors or UX regressions

## Browser Compatibility (16 tests)
- [ ] Browser 01: Chrome latest login flow
- [ ] Browser 02: Chrome latest author dashboard flow
- [ ] Browser 03: Chrome latest editor dashboard flow
- [ ] Browser 04: Firefox latest login flow
- [ ] Browser 05: Firefox latest author dashboard flow
- [ ] Browser 06: Firefox latest editor dashboard flow
- [ ] Browser 07: Safari latest login flow
- [ ] Browser 08: Safari latest author dashboard flow
- [ ] Browser 09: Safari latest editor dashboard flow
- [ ] Browser 10: Edge latest login flow
- [ ] Browser 11: Edge latest author dashboard flow
- [ ] Browser 12: Edge latest editor dashboard flow
- [ ] Browser 13: Mobile Safari author flow
- [ ] Browser 14: Chrome Mobile author flow
- [ ] Browser 15: Firefox Mobile smoke flow
- [ ] Browser 16: No browser-specific JS errors on supported platforms

## Regression Testing (8 tests)
- [ ] Regression 01: Phase 1 authentication flows still pass
- [ ] Regression 02: Phase 2 password reset flows still pass
- [ ] Regression 03: Phase 3 author dashboard flows still pass
- [ ] Regression 04: Phase 3 editor workflow flows still pass
- [ ] Regression 05: Phase 4 settings flows still pass
- [ ] Regression 06: Phase 5 responsive UI still passes smoke testing
- [ ] Regression 07: No PHP warnings/notices appear during key journeys
- [ ] Regression 08: No JavaScript console errors appear during key journeys
