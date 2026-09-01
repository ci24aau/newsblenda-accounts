# QA Checklist

This checklist covers 209 test cases across 17 validation phases for the Newsblenda Accounts release candidate.

## Phase 1: Authentication

- [ ] 1. Validate required registration fields reject empty submissions.
- [ ] 2. Validate invalid email addresses are rejected during registration.
- [ ] 3. Validate mismatched passwords block registration.
- [ ] 4. Validate weak passwords show strength guidance before submit.
- [ ] 5. Validate terms acceptance is required when the form is configured to require it.
- [ ] 6. Validate duplicate usernames are rejected gracefully.
- [ ] 7. Validate duplicate email addresses are rejected gracefully.
- [ ] 8. Validate a successful registration stores the correct default role and status.
- [ ] 9. Validate verification emails are sent after successful registration.
- [ ] 10. Validate the verification link activates the account on first use.
- [ ] 11. Validate an invalid verification token shows an error state.
- [ ] 12. Validate an expired verification token is rejected.
- [ ] 13. Validate the resend verification flow sends a fresh token.
- [ ] 14. Validate rate limiting blocks repeated verification email requests.
- [ ] 15. Validate verified users cannot reuse the same verification token.

## Phase 2: Password Reset

- [ ] 1. Validate the forgot password form rejects an empty email address.
- [ ] 2. Validate invalid email formats are rejected before submission.
- [ ] 3. Validate unknown email addresses do not expose account enumeration details.
- [ ] 4. Validate a valid reset request sends a reset link.
- [ ] 5. Validate the reset link opens the correct reset form.
- [ ] 6. Validate expired reset tokens are rejected.
- [ ] 7. Validate consumed reset tokens cannot be reused.
- [ ] 8. Validate a successful password reset updates the user password and allows login.

## Phase 3: Login & Session

- [ ] 1. Validate the login form rejects missing username or password values.
- [ ] 2. Validate incorrect credentials show an error without logging the user in.
- [ ] 3. Validate unverified users are blocked with the expected guidance.
- [ ] 4. Validate pending approval users are blocked with the expected guidance.
- [ ] 5. Validate remember me persists the session across browser restarts.
- [ ] 6. Validate logout clears the authenticated session immediately.
- [ ] 7. Validate inactive or expired sessions redirect users back to login safely.

## Phase 4: Author Dashboard

- [ ] 1. Validate the author dashboard loads in under 2 seconds with representative data.
- [ ] 2. Validate published article count matches the database.
- [ ] 3. Validate pending review count matches the database.
- [ ] 4. Validate draft count matches the database.
- [ ] 5. Validate rejected count matches the database.
- [ ] 6. Validate revision requested count matches the database.
- [ ] 7. Validate total submissions is the correct aggregate of visible states.
- [ ] 8. Validate total view counts match tracked article views.
- [ ] 9. Validate total earnings match the configured earnings rate.
- [ ] 10. Validate unpaid balance matches total earnings minus paid amount.
- [ ] 11. Validate the profile completion card matches saved profile fields.
- [ ] 12. Validate recent articles list shows the newest author posts first.
- [ ] 13. Validate pending review table shows only pending author submissions.
- [ ] 14. Validate revision requested table shows only revision-requested drafts.
- [ ] 15. Validate rejected drafts table shows only rejected drafts.
- [ ] 16. Validate notification preview shows the newest notifications first.
- [ ] 17. Validate unread notification badge count is accurate.
- [ ] 18. Validate dashboard actions do not trigger PHP warnings in logs.
- [ ] 19. Validate cached dashboard statistics refresh after article updates.
- [ ] 20. Validate cached dashboard statistics refresh after profile changes.

## Phase 5: Editor Dashboard

- [ ] 1. Validate the editor dashboard loads in under 2 seconds with representative data.
- [ ] 2. Validate the pending review queue shows only pending articles.
- [ ] 3. Validate queue ordering prioritizes the oldest pending items first.
- [ ] 4. Validate search filters queue results by article title.
- [ ] 5. Validate author filtering limits queue results to the selected author.
- [ ] 6. Validate revision-requested articles render in the dedicated panel.
- [ ] 7. Validate approved unpublished articles render in the ready-to-publish panel.
- [ ] 8. Validate recently published articles render in descending date order.
- [ ] 9. Validate the author profile summary table lists recent authors correctly.
- [ ] 10. Validate unread notification badge count is accurate for editors.
- [ ] 11. Validate approve actions remove the article from the pending queue.
- [ ] 12. Validate reject actions require or preserve the rejection reason.
- [ ] 13. Validate request revision actions persist editor feedback.
- [ ] 14. Validate publish actions transition approved articles to published.
- [ ] 15. Validate queue actions complete without page reload errors.
- [ ] 16. Validate modal dialogs open and close correctly for review actions.
- [ ] 17. Validate cache invalidation occurs after workflow actions.
- [ ] 18. Validate editor asset loading is limited to dashboard contexts.
- [ ] 19. Validate review buttons are hidden from unauthorized roles.
- [ ] 20. Validate no console errors appear during queue operations.

## Phase 6: Editorial Workflow

- [ ] 1. Validate an author can submit a draft for editorial review.
- [ ] 2. Validate submitted drafts move to the pending review state.
- [ ] 3. Validate workflow history records the author, editor, and status change.
- [ ] 4. Validate editors can approve pending articles.
- [ ] 5. Validate editors can reject pending articles.
- [ ] 6. Validate editors can request revisions on pending articles.
- [ ] 7. Validate authors see revision feedback after a revision request.
- [ ] 8. Validate authors can resubmit a revision-requested article.
- [ ] 9. Validate rejected articles can be resubmitted when allowed by workflow rules.
- [ ] 10. Validate approved articles can be published immediately.
- [ ] 11. Validate approved articles can be scheduled when scheduling is used.
- [ ] 12. Validate published articles map to the published workflow state.
- [ ] 13. Validate workflow notifications are created for authors after editor actions.
- [ ] 14. Validate workflow-related caches are invalidated after each status change.
- [ ] 15. Validate invalid workflow transitions are blocked safely.

## Phase 7: Forms & Validation

- [ ] 1. Validate all frontend forms display required field errors consistently.
- [ ] 2. Validate inline error messages are visible and understandable.
- [ ] 3. Validate server-side validation mirrors client-side validation rules.
- [ ] 4. Validate invalid nonces are rejected on state-changing forms.
- [ ] 5. Validate invalid email inputs are sanitized before persistence.
- [ ] 6. Validate long textarea content is saved without truncation issues.
- [ ] 7. Validate autosave preserves draft content when supported.
- [ ] 8. Validate draft retrieval restores the latest saved draft content.
- [ ] 9. Validate password strength messaging updates while typing.
- [ ] 10. Validate password confirmation messaging updates while typing.
- [ ] 11. Validate duplicate form submissions are prevented by disabled submit buttons.
- [ ] 12. Validate file or avatar inputs reject unsupported file types.
- [ ] 13. Validate file or avatar inputs reject oversized files.

## Phase 8: Admin Settings

- [ ] 1. Validate the settings page loads for administrators only.
- [ ] 2. Validate tab switching shows the correct settings panel.
- [ ] 3. Validate general settings save successfully.
- [ ] 4. Validate registration settings save successfully.
- [ ] 5. Validate security settings save successfully.
- [ ] 6. Validate workflow settings save successfully.
- [ ] 7. Validate email settings save successfully.
- [ ] 8. Validate numeric field validation enforces min and max constraints.
- [ ] 9. Validate SMTP connection tests return actionable success or failure feedback.
- [ ] 10. Validate test email sending reports the expected result.

## Phase 9: Notifications

- [ ] 1. Validate unread badge count matches unread notification records.
- [ ] 2. Validate the notifications list orders items newest first.
- [ ] 3. Validate unread notifications are visually distinct from read notifications.
- [ ] 4. Validate mark-as-read updates the notification status.
- [ ] 5. Validate mark-all-read updates all unread notifications.
- [ ] 6. Validate deleting a notification removes it from the list.
- [ ] 7. Validate notification timestamps render using site date and time settings.
- [ ] 8. Validate notification action URLs navigate to the intended destination.
- [ ] 9. Validate notification cache invalidation occurs after create, update, and delete actions.
- [ ] 10. Validate old notifications are eligible for scheduled cleanup or purge policies.

## Phase 10: Mobile Responsiveness

- [ ] 1. Validate login and registration layouts at widths below 640px.
- [ ] 2. Validate dashboard stat cards stack cleanly below 640px.
- [ ] 3. Validate tables remain usable on small screens below 640px.
- [ ] 4. Validate navigation and action buttons remain tappable below 640px.
- [ ] 5. Validate profile forms remain usable below 640px.
- [ ] 6. Validate layouts between 640px and 1024px on tablets.
- [ ] 7. Validate dashboard columns adapt correctly between 640px and 1024px.
- [ ] 8. Validate editor review actions remain accessible between 640px and 1024px.
- [ ] 9. Validate notification layouts remain readable between 640px and 1024px.
- [ ] 10. Validate layouts above 1024px use the intended desktop spacing.
- [ ] 11. Validate no horizontal scrolling appears unintentionally at supported breakpoints.
- [ ] 12. Validate responsive states do not hide critical workflow actions.

## Phase 11: Accessibility

- [ ] 1. Validate every interactive control is reachable by keyboard alone.
- [ ] 2. Validate focus indicators remain visible on buttons, links, inputs, and tabs.
- [ ] 3. Validate modal dialogs trap focus while open and return focus on close.
- [ ] 4. Validate labels are associated with form inputs.
- [ ] 5. Validate screen readers announce validation errors meaningfully.
- [ ] 6. Validate headings follow a logical hierarchy on major screens.
- [ ] 7. Validate status badges and alerts are understandable without color alone.
- [ ] 8. Validate color contrast meets WCAG AA expectations.
- [ ] 9. Validate notification and dashboard tables expose understandable headers.
- [ ] 10. Validate dynamic UI changes do not create inaccessible focus loss.

## Phase 12: Security

- [ ] 1. Validate nonces protect all state-changing frontend actions.
- [ ] 2. Validate nonces protect all state-changing admin actions.
- [ ] 3. Validate capability checks protect editor-only workflow actions.
- [ ] 4. Validate capability checks protect admin-only settings and reports.
- [ ] 5. Validate direct access to plugin PHP files is blocked where expected.
- [ ] 6. Validate all custom SQL queries use prepared statements for dynamic values.
- [ ] 7. Validate article search and filters resist SQL injection attempts.
- [ ] 8. Validate notification actions resist CSRF attempts.
- [ ] 9. Validate profile updates resist CSRF attempts.
- [ ] 10. Validate output escaping prevents stored XSS in notifications.
- [ ] 11. Validate output escaping prevents stored XSS in profile fields.
- [ ] 12. Validate output escaping prevents reflected XSS in query-string driven views.
- [ ] 13. Validate password reset tokens expire and cannot be replayed.
- [ ] 14. Validate verification tokens expire and cannot be replayed.
- [ ] 15. Validate unauthorized users cannot access dashboard data through REST routes.

## Phase 13: Email Delivery

- [ ] 1. Validate the verification email renders and sends successfully.
- [ ] 2. Validate the password reset email renders and sends successfully.
- [ ] 3. Validate the account approval email renders and sends successfully.
- [ ] 4. Validate the account restriction email renders and sends successfully.
- [ ] 5. Validate the article approval email renders and sends successfully.
- [ ] 6. Validate the article rejection email renders and sends successfully.
- [ ] 7. Validate the revision requested email renders and sends successfully.
- [ ] 8. Validate the welcome email renders and sends successfully.
- [ ] 9. Validate the pending approval email renders and sends successfully.
- [ ] 10. Validate the payout email flow is defined and does not regress existing mail delivery.

## Phase 14: Earnings & Payouts

- [ ] 1. Validate valid article views are counted correctly per author.
- [ ] 2. Validate the configured earnings rate is applied correctly per 1000 views.
- [ ] 3. Validate the daily earnings job updates total earnings per author.
- [ ] 4. Validate unpaid balance recalculates after earnings synchronization.
- [ ] 5. Validate minimum payout thresholds are enforced before payout processing.
- [ ] 6. Validate the daily payout job creates payment records for eligible authors.
- [ ] 7. Validate processed payouts update paid amount and last payment date.
- [ ] 8. Validate payout history records display the expected values.
- [ ] 9. Validate payout statistics update after a processed payout.
- [ ] 10. Validate payout-related caches invalidate after payout processing.

## Phase 15: Database & Performance

- [ ] 1. Validate dashboard aggregate queries complete within the target budget.
- [ ] 2. Validate editor queue queries complete within the target budget.
- [ ] 3. Validate unread notification count queries complete within the target budget.
- [ ] 4. Validate author statistics are served from cache on repeated requests.
- [ ] 5. Validate unread notification counts are served from cache on repeated requests.
- [ ] 6. Validate user profile payloads are served from cache on repeated requests.
- [ ] 7. Validate N+1 query patterns are eliminated from dashboard summary sections.
- [ ] 8. Validate strategic indexes exist after migrations run.
- [ ] 9. Validate pagination offsets return stable result ordering.
- [ ] 10. Validate cache invalidation refreshes stale results after writes.

## Phase 16: Browser Compatibility

- [ ] 1. Validate authentication screens in Chrome 90+.
- [ ] 2. Validate dashboard screens in Chrome 90+.
- [ ] 3. Validate admin screens in Chrome 90+.
- [ ] 4. Validate authentication screens in Firefox 88+.
- [ ] 5. Validate dashboard screens in Firefox 88+.
- [ ] 6. Validate admin screens in Firefox 88+.
- [ ] 7. Validate authentication screens in Safari 14+.
- [ ] 8. Validate dashboard screens in Safari 14+.
- [ ] 9. Validate admin screens in Safari 14+.
- [ ] 10. Validate authentication screens in Edge 90+.
- [ ] 11. Validate dashboard screens in Edge 90+.
- [ ] 12. Validate admin screens in Edge 90+.
- [ ] 13. Validate mobile authentication screens in Chrome for Android.
- [ ] 14. Validate mobile dashboard screens in Chrome for Android.
- [ ] 15. Validate mobile authentication screens in Safari on iOS.
- [ ] 16. Validate mobile dashboard screens in Safari on iOS.

## Phase 17: Regression Testing

- [ ] 1. Validate all previously completed authentication flows still work end to end.
- [ ] 2. Validate all previously completed editorial workflow actions still work end to end.
- [ ] 3. Validate all previously completed profile management flows still work end to end.
- [ ] 4. Validate all previously completed notifications flows still work end to end.
- [ ] 5. Validate all previously completed earnings and payout screens still work end to end.
- [ ] 6. Validate there are no broken internal links on plugin-controlled screens.
- [ ] 7. Validate there are no browser console errors on critical screens.
- [ ] 8. Validate there are no PHP warnings or notices during smoke tests.
