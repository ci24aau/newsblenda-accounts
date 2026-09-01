<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Profile;

use Newsblenda\Accounts\Classes\CacheManager;

defined('ABSPATH') || exit;

class Profile
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_shortcode(
            'newsblenda_profile',
            [$this, 'render']
        );

        add_shortcode(
            'nbe_profile',
            [$this, 'render']
        );

        add_shortcode(
            'nb_profile',
            [$this, 'render']
        );

        add_action(
            'init',
            [$this, 'save_profile']
        );

        add_action(
            'profile_update',
            [$this, 'invalidate_profile_cache']
        );

        add_action(
            'added_user_meta',
            [$this, 'invalidate_profile_cache_from_meta'],
            10,
            4
        );

        add_action(
            'updated_user_meta',
            [$this, 'invalidate_profile_cache_from_meta'],
            10,
            4
        );

        add_action(
            'deleted_user_meta',
            [$this, 'invalidate_profile_cache_from_meta'],
            10,
            4
        );
    }

    /**
     * Render profile page.
     */
    public function render(): string
    {
        if (! is_user_logged_in()) {

            return '<div class="nba-message nba-message-error">' .
                esc_html__(
                    'You must be logged in to view your profile.',
                    'newsblenda-accounts'
                ) .
                '</div>';

        }

        ob_start();

        $template = NB_ACCOUNTS_PATH .
            'templates/profile/profile.php';

        if (file_exists($template)) {

            include $template;

        } else {

            echo '<p>' .
                esc_html__(
                    'Profile template not found.',
                    'newsblenda-accounts'
                ) .
                '</p>';

        }

        return (string) ob_get_clean();
    }

    /**
     * Save profile.
     */
    public function save_profile(): void
    {
        if (
            ! isset($_POST['nbe_profile_submit']) && ! isset($_POST['nba_profile_submit']) ||
            ! is_user_logged_in()
        ) {
            return;
        }

        check_admin_referer(
            'nbe_nonce'
        );

        $user_id = get_current_user_id();

        $result = wp_update_user(
            [
                'ID'           => $user_id,
                'display_name' => sanitize_text_field(
                    wp_unslash(
                        $_POST['nbe_full_name'] ?? $_POST['display_name'] ?? ''
                    )
                ),
                'user_email' => sanitize_email(
                    wp_unslash(
                        $_POST['nbe_email'] ?? $_POST['user_email'] ?? ''
                    )
                ),
                'user_url' => esc_url_raw(
                    wp_unslash(
                        $_POST['nbe_website'] ?? $_POST['user_url'] ?? ''
                    )
                ),
                'description' => sanitize_textarea_field(
                    wp_unslash(
                        $_POST['nbe_biography'] ?? $_POST['description'] ?? ''
                    )
                ),
            ]
        );

        if (is_wp_error($result)) {
            return;
        }

        do_action(
            'nb_accounts_before_profile_save',
            $user_id
        );

        /*
        |--------------------------------------------------------------------------
        | Save extended profile fields
        |--------------------------------------------------------------------------
        */

        $this->save_profile_meta(
            $user_id
        );
    }

    /**
     * Save extended profile fields.
     */
    private function save_profile_meta(
        int $user_id
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Personal Information
        |--------------------------------------------------------------------------
        */

        $this->save_meta($user_id, 'first_name');
        $this->save_meta($user_id, 'last_name');
        $this->save_meta($user_id, 'nickname');
        $this->save_meta($user_id, 'nb_phone', false, 'nbe_phone');
        $this->save_meta($user_id, 'nb_whatsapp', false, 'nbe_whatsapp');
        $this->save_meta($user_id, 'nb_gender', false, 'nbe_gender');
        $this->save_meta($user_id, 'nb_dob', false, 'nbe_dob');
        $this->save_meta($user_id, 'nb_country', false, 'nbe_country');
        $this->save_meta($user_id, 'nb_state', false, 'nbe_state');
        $this->save_meta($user_id, 'nb_city', false, 'nbe_city');
        $this->save_meta($user_id, 'nb_address', true, 'nbe_address');

        /*
        |--------------------------------------------------------------------------
        | Author Information
        |--------------------------------------------------------------------------
        */

        $this->save_meta($user_id, 'nb_occupation', false, 'nbe_occupation');
        $this->save_meta($user_id, 'nb_niche', false, 'nbe_niche');
        $this->save_meta($user_id, 'nb_experience', false, 'nbe_experience');
        $this->save_meta($user_id, 'nb_categories', false, 'nbe_categories');
        $this->save_meta($user_id, 'nb_facebook', false, 'nbe_facebook');
        $this->save_meta($user_id, 'nb_twitter', false, 'nbe_twitter');
        $this->save_meta($user_id, 'nb_instagram', false, 'nbe_instagram');
        $this->save_meta($user_id, 'nb_linkedin', false, 'nbe_linkedin');
        $this->save_meta($user_id, 'nb_website', false, 'nbe_website');

        /*
        |--------------------------------------------------------------------------
        | Payment Information
        |--------------------------------------------------------------------------
        */

        $this->save_meta($user_id, 'nb_payment_method', false, 'nbe_payment_method');
        $this->save_meta($user_id, 'nb_bank_name', false, 'nbe_bank_name');
        $this->save_meta($user_id, 'nb_account_name', false, 'nbe_account_name');
        $this->save_meta($user_id, 'nb_account_number', false, 'nbe_account_number');
        $this->save_meta($user_id, 'nb_paypal', false, 'nbe_paypal');
        $this->save_meta($user_id, 'nb_opay', false, 'nbe_opay');
        $this->save_meta($user_id, 'nb_palmpay', false, 'nbe_palmpay');
        $this->save_meta($user_id, 'nb_moniepoint', false, 'nbe_moniepoint');

        update_user_meta(
            $user_id,
            'nb_profile_updated',
            current_time('mysql')
        );

        do_action(
            'nb_accounts_after_profile_save',
            $user_id
        );

        $this->invalidate_profile_cache($user_id);

        wp_safe_redirect(

            add_query_arg(
                'updated',
                '1',
                wp_get_referer()
            )

        );

        exit;
    }
    
        /**
     * Save user meta.
     */
    private function save_meta(
        int $user_id,
        string $key,
        bool $textarea = false,
        ?string $input_key = null
    ): void {

        $input_key = $input_key ?: $key;

        if (! isset($_POST[$input_key]) && ! isset($_POST[$key])) {
            return;
        }

        $value = wp_unslash($_POST[$input_key] ?? $_POST[$key] ?? '');

        if ($textarea) {

            $value = sanitize_textarea_field($value);

        } else {

            $value = sanitize_text_field($value);

        }

        update_user_meta(
            $user_id,
            $key,
            $value
        );
    }

    /**
     * Get profile completion percentage.
     */
    public function completion(
        int $user_id
    ): int {
        $cache_key = 'nb_profile_completion_' . $user_id;
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return (int) $cached;
        }

        $fields = [

            'first_name',
            'last_name',
            'description',
            'nb_phone',
            'nb_country',
            'nb_state',
            'nb_city',
            'nb_niche',
            'nb_payment_method',

        ];

        $completed = 0;

        foreach ($fields as $field) {

            if (! empty(get_user_meta($user_id, $field, true))) {

                $completed++;

            }

        }

        $completion = (int) round(
            ($completed / count($fields)) * 100
        );

        set_transient(
            $cache_key,
            $completion,
            HOUR_IN_SECONDS
        );

        return $completion;

    }

    /**
     * Profile updated?
     */
    public function updated(
        int $user_id
    ): string {

        return (string) get_user_meta(
            $user_id,
            'nb_profile_updated',
            true
        );

    }

    /**
     * Get payment details.
     */
    public function payment(
        int $user_id
    ): array {

        return [

            'method' => get_user_meta(
                $user_id,
                'nb_payment_method',
                true
            ),

            'bank_name' => get_user_meta(
                $user_id,
                'nb_bank_name',
                true
            ),

            'account_name' => get_user_meta(
                $user_id,
                'nb_account_name',
                true
            ),

            'account_number' => get_user_meta(
                $user_id,
                'nb_account_number',
                true
            ),

            'paypal' => get_user_meta(
                $user_id,
                'nb_paypal',
                true
            ),

            'opay' => get_user_meta(
                $user_id,
                'nb_opay',
                true
            ),

            'palmpay' => get_user_meta(
                $user_id,
                'nb_palmpay',
                true
            ),

            'moniepoint' => get_user_meta(
                $user_id,
                'nb_moniepoint',
                true
            ),

        ];

    }

    /**
     * Get social links.
     */
    public function socials(
        int $user_id
    ): array {

        return [

            'facebook' => get_user_meta($user_id, 'nb_facebook', true),

            'twitter' => get_user_meta($user_id, 'nb_twitter', true),

            'instagram' => get_user_meta($user_id, 'nb_instagram', true),

            'linkedin' => get_user_meta($user_id, 'nb_linkedin', true),

            'website' => get_user_meta($user_id, 'nb_website', true),

        ];

    }

    /**
     * Get profile data.
     */
    public function profile(
        int $user_id
    ): array {
        return CacheManager::get_user_profile($user_id);
    }

    /**
     * Invalidate profile transients for a user.
     */
    public function invalidate_profile_cache(
        int $user_id
    ): void {
        if ($user_id <= 0) {
            return;
        }

        delete_transient('nb_profile_data_' . $user_id);
        delete_transient('nb_profile_completion_' . $user_id);
    }

    /**
     * Invalidate profile transients when user meta changes.
     *
     * @param int $meta_id
     * @param int $user_id
     * @param string $meta_key
     * @param mixed $meta_value
     */
    public function invalidate_profile_cache_from_meta(
        $meta_id,
        int $user_id,
        string $meta_key,
        $meta_value
    ): void {
        unset($meta_id, $meta_key, $meta_value);
        $this->invalidate_profile_cache($user_id);
    }

    /**
     * Profile URL.
     */
    public static function url(): string
    {
        return home_url('/profile/');
    }

    /**
     * Is profile page.
     */
    public static function is_profile(): bool
    {
        return is_page('profile');
    }

    /**
     * Current profile user.
     */
    public static function current_user(): ?\WP_User
    {
        if (! is_user_logged_in()) {
            return null;
        }

        return wp_get_current_user();
    }
}