<?php

namespace Newsblenda\Accounts\Auth;

if (! defined('ABSPATH')) {
    exit;
}

class Auth
{
    /**
     * Protected frontend routes.
     *
     * @var array
     */
    private array $protected_routes = [
        'dashboard',
        'editor-dashboard',
        'submit',
        'my-articles',
        'earnings',
        'notifications',
        'profile',
        'settings',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('template_redirect', [$this, 'protect_routes']);

        add_action('admin_init', [$this, 'block_admin_dashboard']);

        add_filter('show_admin_bar', [$this, 'hide_admin_bar']);

        add_filter('login_redirect', [$this, 'login_redirect'], 10, 3);

        add_action('wp_logout', [$this, 'logout_redirect']);
    }

    /**
     * Protect frontend routes.
     */
    public function protect_routes(): void
    {
        $route = trim(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/'
        );

        if (! in_array($route, $this->protected_routes, true)) {
            return;
        }

        if (! is_user_logged_in()) {
            wp_safe_redirect(home_url('/login/'));
            exit;
        }

        $user_id = get_current_user_id();

        // Administrators bypass email verification.
        if (! current_user_can('manage_options')) {

            $verified = (int) get_user_meta(
                $user_id,
                'nb_email_verified',
                true
            );

            if ($verified !== 1) {
                wp_safe_redirect(
                    add_query_arg(
                        'status',
                        'verify-email',
                        home_url('/verify-email/')
                    )
                );
                exit;
            }
        }

        if (! $this->is_account_approved($user_id)) {
            wp_safe_redirect(
                add_query_arg(
                    'status',
                    'pending',
                    home_url('/pending-approval/')
                )
            );

            exit;
        }

        if ($this->is_account_restricted($user_id)) {
            wp_safe_redirect(
                add_query_arg(
                    'status',
                    'restricted',
                    home_url('/account-restricted/')
                )
            );

            exit;
        }

        if ($route === 'editor-dashboard' && ! self::is_editor() && ! self::is_admin()) {
            wp_safe_redirect(home_url('/dashboard/'));
            exit;
        }

        if ($route === 'dashboard' && self::is_editor() && ! self::is_admin()) {
            wp_safe_redirect(home_url('/editor-dashboard/'));
            exit;
        }

        if (in_array($route, ['earnings', 'submit'], true) && self::is_editor() && ! self::is_admin()) {
            wp_safe_redirect(home_url('/editor-dashboard/'));
            exit;
        }
    }

    /**
     * Prevent authors/editors from entering wp-admin.
     */
    public function block_admin_dashboard(): void
    {
        if (! is_user_logged_in()) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (current_user_can('manage_options')) {
            return;
        }

        // Editors use the frontend editor dashboard, not wp-admin.
        if (current_user_can('nb_editor')) {
            wp_safe_redirect(home_url('/editor-dashboard/'));
            exit;
        }

        wp_safe_redirect(home_url('/dashboard/'));
        exit;
    }

    /**
     * Hide admin toolbar.
     */
    public function hide_admin_bar(bool $show): bool
    {
        if (! is_user_logged_in()) {
            return $show;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        return false;
    }

    /**
     * Login redirect.
     */
    public function login_redirect(
        string $redirect_to,
        string $requested_redirect_to,
        $user
    ): string {

        if (! $user instanceof \WP_User) {
            return $redirect_to;
        }

        if (in_array('administrator', $user->roles, true)) {
            return admin_url();
        }

        if (in_array('nb_editor', $user->roles, true)) {
            return home_url('/editor-dashboard/');
        }

        return home_url('/dashboard/');
    }


        /**
     * Logout redirect.
     */
    public function logout_redirect(): void
    {
        wp_safe_redirect(
            home_url('/login/?logged_out=1')
        );

        exit;
    }

    /**
     * Is logged in.
     */
    public static function is_logged_in(): bool
    {
        return is_user_logged_in();
    }

    /**
     * Current user roles.
     */
    public static function roles(): array
    {
        $user = self::user();

        if (! $user) {
            return [];
        }

        return (array) $user->roles;
    }

    /**
     * Has role.
     */
    public static function has_role(
        string $role
    ): bool {

        return in_array(
            $role,
            self::roles(),
            true
        );

    }

    /**
     * Account verified.
     */
    public static function verified(): bool
    {
        if (! is_user_logged_in()) {
            return false;
        }

        return (bool) get_user_meta(
            get_current_user_id(),
            'nb_email_verified',
            true
        );
    }

    /**
     * Account approved.
     */
    public static function approved(): bool
    {
        if (! is_user_logged_in()) {
            return false;
        }

        return get_user_meta(
            get_current_user_id(),
            'nb_account_status',
            true
        ) === 'approved';
    }

    /**
     * Account restricted.
     */
    public static function restricted(): bool
    {
        if (! is_user_logged_in()) {
            return false;
        }

        return (bool) get_user_meta(
            get_current_user_id(),
            'nb_submission_restricted',
            true
        );
    }

    /**
     * Pending approval.
     */
    public static function pending(): bool
    {
        if (! is_user_logged_in()) {
            return false;
        }

        return get_user_meta(
            get_current_user_id(),
            'nb_account_status',
            true
        ) === 'pending';
    }

    /**
     * User status.
     */
    public static function status(): string
    {
        if (! is_user_logged_in()) {
            return 'guest';
        }

        return (string) get_user_meta(
            get_current_user_id(),
            'nb_account_status',
            true
        );
    }

        /**
     * Redirect current user according to role.
     */
    public static function redirect_by_role(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(home_url('/login/'));
            exit;
        }

        if (self::is_admin()) {
            wp_safe_redirect(admin_url());
            exit;
        }

        if (self::is_editor()) {
            wp_safe_redirect(home_url('/editor-dashboard/'));
            exit;
        }

        wp_safe_redirect(home_url('/dashboard/'));
        exit;
    }

    /**
     * Current dashboard URL.
     */
    public static function dashboard_url(): string
    {
        if (self::is_admin()) {
            return admin_url();
        }

        if (self::is_editor()) {
            return home_url('/editor-dashboard/');
        }

        return home_url('/dashboard/');
    }

    /**
     * Login URL.
     */
    public static function login_url(): string
    {
        return home_url('/login/');
    }

    /**
     * Register URL.
     */
    public static function register_url(): string
    {
        return home_url('/register/');
    }

    /**
     * Logout URL.
     */
    public static function logout_url(): string
    {
        return wp_logout_url(
            home_url('/login/')
        );
    }

    /**
     * Profile URL.
     */
    public static function profile_url(): string
    {
        return home_url('/profile/');
    }

    /**
     * Notifications URL.
     */
    public static function notifications_url(): string
    {
        return home_url('/notifications/');
    }

    /**
     * Earnings URL.
     */
    public static function earnings_url(): string
    {
        return home_url('/earnings/');
    }

    /**
     * Submission URL.
     */
    public static function submission_url(): string
    {
        return home_url('/submit/');
    }

    /**
     * Is frontend dashboard.
     */
    public static function is_dashboard(): bool
    {
        return is_page('dashboard');
    }

    /**
     * Is editor dashboard.
     */
    public static function is_editor_dashboard(): bool
    {
        return is_page('editor-dashboard');
    }

    /**
     * Is profile page.
     */
    public static function is_profile(): bool
    {
        return is_page('profile');
    }

    /**
     * Is notifications page.
     */
    public static function is_notifications(): bool
    {
        return is_page('notifications');
    }

    /**
     * Is earnings page.
     */
    public static function is_earnings(): bool
    {
        return is_page('earnings');
    }

    /**
     * Is article submission page.
     */
    public static function is_submission(): bool
    {
        return is_page('submit');
    }

    /**
     * Can submit articles.
     */
    public static function can_submit(): bool
    {
        if (! self::is_logged_in()) {
            return false;
        }

        if (! self::verified()) {
            return false;
        }

        if (! self::approved()) {
            return false;
        }

        if (self::restricted()) {
            return false;
        }

        return self::is_author()
            || self::is_editor()
            || self::is_admin();
    }

    /**
     * Require verified account.
     */
    public static function require_verified(): void
    {
        if (self::is_admin()) {
            return;
        }

        $verified = (int) get_user_meta(
            self::user_id(),
            'nb_email_verified',
            true
        );

        if ($verified !== 1) {
            wp_safe_redirect(home_url('/verify-email/'));
            exit;
        }
    }

    /**
     * Require approved account.
     */
    public static function require_approved(): void
    {
        if (! self::approved()) {

            wp_safe_redirect(
                home_url('/pending-approval/')
            );

            exit;
        }
    }

    /**
     * Require unrestricted account.
     */
    public static function require_unrestricted(): void
    {
        if (self::restricted()) {

            wp_safe_redirect(
                home_url('/account-restricted/')
            );

            exit;
        }
    }
    
        /**
     * Current user display name.
     */
    public static function display_name(): string
    {
        $user = self::user();

        return $user
            ? (string) $user->display_name
            : '';
    }

    /**
     * Current username.
     */
    public static function username(): string
    {
        $user = self::user();

        return $user
            ? (string) $user->user_login
            : '';
    }

    /**
     * Current email.
     */
    public static function email(): string
    {
        $user = self::user();

        return $user
            ? (string) $user->user_email
            : '';
    }

    /**
     * User avatar.
     */
    public static function avatar(
        int $size = 96
    ): string {

        return get_avatar(
            self::user_id(),
            $size
        );

    }

    /**
     * User meta helper.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function meta(
        string $key,
        $default = null
    ) {

        if (! self::is_logged_in()) {
            return $default;
        }

        $value = get_user_meta(
            self::user_id(),
            $key,
            true
        );

        return $value === ''
            ? $default
            : $value;
    }

    /**
     * Update current user meta.
     */
    public static function update_meta(
        string $key,
        $value
    ): bool {

        if (! self::is_logged_in()) {
            return false;
        }

        return (bool) update_user_meta(
            self::user_id(),
            $key,
            $value
        );
    }

    /**
     * Delete current user meta.
     */
    public static function delete_meta(
        string $key
    ): bool {

        if (! self::is_logged_in()) {
            return false;
        }

        return (bool) delete_user_meta(
            self::user_id(),
            $key
        );
    }

    /**
     * Is account active.
     */
    public static function active(): bool
    {
        return self::verified()
            && self::approved()
            && ! self::restricted();
    }

    /**
     * Require active account.
     */
    public static function require_active(): void
    {
        self::require_login();

        self::require_verified();

        self::require_approved();

        self::require_unrestricted();
    }

    /**
     * Redirect guests.
     */
    public static function redirect_guest(
        string $url = ''
    ): void {

        if (self::is_logged_in()) {
            return;
        }

        if ($url === '') {
            $url = self::login_url();
        }

        wp_safe_redirect($url);

        exit;
    }

    /**
     * Redirect authenticated users.
     */
    public static function redirect_authenticated(): void
    {
        if (! self::is_logged_in()) {
            return;
        }

        self::redirect_by_role();
    }

    /**
     * Is current user allowed to access frontend account pages.
     */
    public static function can_access_frontend(): bool
    {
        if (! self::is_logged_in()) {
            return false;
        }

        return self::active();
    }

    /**
     * Get current user role.
     */
    public static function role(): string
    {
        $roles = self::roles();

        return empty($roles)
            ? ''
            : (string) reset($roles);
    }

    /**
     * Has any role.
     */
    public static function has_any_role(
        array $roles
    ): bool {

        foreach ($roles as $role) {

            if (self::has_role($role)) {
                return true;
            }

        }

        return false;
    }

    /**
     * Is staff member.
     */
    public static function is_staff(): bool
    {
        return self::has_any_role(
            [
                'administrator',
                'editor',
                'nb_editor',
            ]
        );
    }

    /**
     * User registration date.
     */
    public static function registered(): ?string
    {
        $user = self::user();

        if (! $user) {
            return null;
        }

        return $user->user_registered;
    }

    /**
     * Is email verified for supplied user.
     */
    public static function user_verified(
        int $user_id
    ): bool {

        return (bool) get_user_meta(
            $user_id,
            'nb_email_verified',
            true
        );
    }

    /**
     * Is approved for supplied user.
     */
    public static function user_approved(
        int $user_id
    ): bool {

        return get_user_meta(
            $user_id,
            'nb_account_status',
            true
        ) === 'approved';
    }

    /**
     * Is restricted for supplied user.
     */
    public static function user_restricted(
        int $user_id
    ): bool {

        return (bool) get_user_meta(
            $user_id,
            'nb_submission_restricted',
            true
        );
    }
    

    /**
     * Email verification.
     */
    private function is_account_verified(int $user_id): bool
    {
        return (bool) get_user_meta(
            $user_id,
            'nb_email_verified',
            true
        );
    }

    /**
     * Approval.
     */
    private function is_account_approved(int $user_id): bool
    {
        return get_user_meta(
            $user_id,
            'nb_account_status',
            true
        ) === 'approved';
    }

    /**
     * Restriction.
     */
    private function is_account_restricted(int $user_id): bool
    {
        return (bool) get_user_meta(
            $user_id,
            'nb_submission_restricted',
            true
        );
    }

    /**
     * Current user is author.
     */
    public static function is_author(): bool
    {
        return current_user_can('nb_author');
    }

    /**
     * Current user is editor.
     */
    public static function is_editor(): bool
    {
        return current_user_can('nb_editor');
    }

    /**
     * Current user is administrator.
     */
    public static function is_admin(): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * Current user ID.
     */
    public static function user_id(): int
    {
        return get_current_user_id();
    }

    /**
     * Current user object.
     */
    public static function user(): ?\WP_User
    {
        $user = wp_get_current_user();

        if (! $user || ! $user->exists()) {
            return null;
        }

        return $user;
    }

    /**
     * Require login.
     */
    public static function require_login(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(home_url('/login/'));
            exit;
        }
    }

    /**
     * Require administrator.
     */
    public static function require_admin(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'Access denied.',
                    'newsblenda-accounts'
                )
            );
        }
    }

    /**
     * Require editor.
     */
    public static function require_editor(): void
    {
        if (
            ! current_user_can('nb_editor')
            && ! current_user_can('manage_options')
        ) {
            wp_die(
                esc_html__(
                    'Access denied.',
                    'newsblenda-accounts'
                )
            );
        }
    }

    /**
     * Require author.
     */
    public static function require_author(): void
    {
        if (
            ! current_user_can('nb_author')
            && ! current_user_can('manage_options')
        ) {
            wp_die(
                esc_html__(
                    'Access denied.',
                    'newsblenda-accounts'
                )
            );
        }
    }
}