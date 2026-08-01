# Newsblenda Accounts QA Checklist

## Pre-Release Validation

### Authentication (Phase 1)
- [ ] Registration flow complete
- [ ] Email verification works
- [ ] Login restrictions enforced
- [ ] Password reset works
- [ ] Rate limiting active

### Dashboards (Phase 3)
- [ ] Author dashboard loads
- [ ] Editor dashboard loads
- [ ] Statistics accurate
- [ ] Notifications display
- [ ] Workflows function

### Admin Settings (Phase 4)
- [ ] Settings save correctly
- [ ] All tabs accessible
- [ ] SMTP test works
- [ ] Validation functions

### UI/UX (Phase 5)
- [ ] Professional appearance
- [ ] Responsive on mobile
- [ ] Responsive on tablet
- [ ] Responsive on desktop
- [ ] No oversized headings

### Performance (Phase 6)
- [ ] Lighthouse >90
- [ ] Dashboard <2s load
- [ ] No N+1 queries
- [ ] Caching active
- [ ] Assets optimized

### Security
- [ ] Nonces validated
- [ ] Capabilities checked
- [ ] Input sanitized
- [ ] Output escaped
- [ ] SQL prepared

### Accessibility
- [ ] Keyboard navigation
- [ ] Screen reader compatible
- [ ] Color contrast 4.5:1
- [ ] Focus indicators visible
- [ ] Labels properly associated

### Cross-Browser
- [ ] Chrome works
- [ ] Firefox works
- [ ] Safari works
- [ ] Edge works

### Final
- [ ] No PHP errors
- [ ] No console errors
- [ ] No broken links
- [ ] All features working
- [ ] Documentation complete
