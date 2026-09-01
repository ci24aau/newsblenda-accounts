
# Performance Optimization

## Targets
- Dashboard load: <2 seconds
- Form pages: <1.5 seconds
- Lighthouse: >90
- Database queries: <500ms
- First Contentful Paint: <1.8s
- Largest Contentful Paint: <2.5s
- Cumulative Layout Shift: <0.1

## Optimizations
- Database indexes on frequently queried columns
- Efficient JOINs instead of multiple queries
- Transient caching for statistics (1 hour TTL)
- Conditional CSS/JS loading
- Minified assets
- Lazy loaded images

## Monitoring
1. Enable WP_DEBUG and WP_DEBUG_LOG
2. Install Query Monitor plugin
3. Check slow queries in Query Monitor dashboard
4. Monitor cache hit rates

## Lighthouse Testing
1. Open Chrome DevTools (F12)
2. Click Lighthouse tab
3. Generate report
4. Target: All scores >90
