<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

class Routes
{
    /**
     * REST namespace.
     */
    private string $namespace = 'newsblenda/v1';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action(
            'rest_api_init',
            [$this, 'register_routes']
        );
    }

    /**
     * Register REST routes.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/dashboard',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'dashboard'],
                'permission_callback' => [$this, 'logged_in'],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/profile',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'profile'],
                'permission_callback' => [$this, 'logged_in'],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/profile',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'update_profile'],
                'permission_callback' => [$this, 'logged_in'],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/notifications',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'notifications'],
                'permission_callback' => [$this, 'logged_in'],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/account',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'account'],
                'permission_callback' => [$this, 'logged_in'],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/logout',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'logout'],
                'permission_callback' => [$this, 'logged_in'],
            ]
        );
    }

    /**
     * Logged in permission.
     */
    public function logged_in(): bool
    {
        return is_user_logged_in();
    }
    
        /**
     * Dashboard endpoint.
     */
    public function dashboard(
        WP_REST_Request $request
    ): WP_REST_Response {

        $user = wp_get_current_user();

        return new WP_REST_Response(
            [
                'success' => true,

                'data' => [

                    'user_id' => $user->ID,

                    'display_name' => $user->display_name,

                    'email' => $user->user_email,

                    'roles' => $user->roles,

                    'published' => (int) count_user_posts(
                        $user->ID
                    ),

                    'pending' => count_user_posts(
                        $user->ID,
                        'post',
                        false
                    ),

                    'status' => get_user_meta(
                        $user->ID,
                        'nb_account_status',
                        true
                    ),

                ],

            ],
            200
        );

    }

    /**
     * Profile endpoint.
     */
    public function profile(
        WP_REST_Request $request
    ): WP_REST_Response {

        $user = wp_get_current_user();

        return new WP_REST_Response(
            [
                'success' => true,

                'data' => [

                    'id' => $user->ID,

                    'username' => $user->user_login,

                    'display_name' => $user->display_name,

                    'email' => $user->user_email,

                    'website' => $user->user_url,

                    'bio' => $user->description,

                    'phone' => get_user_meta(
                        $user->ID,
                        'nb_phone',
                        true
                    ),

                    'country' => get_user_meta(
                        $user->ID,
                        'nb_country',
                        true
                    ),

                    'state' => get_user_meta(
                        $user->ID,
                        'nb_state',
                        true
                    ),

                    'city' => get_user_meta(
                        $user->ID,
                        'nb_city',
                        true
                    ),

                ],

            ],
            200
        );

    }

    /**
     * Notifications endpoint.
     */
    public function notifications(
        WP_REST_Request $request
    ): WP_REST_Response {

        global $wpdb;

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$wpdb->prefix}nb_notifications
                WHERE user_id=%d
                ORDER BY created_at DESC
                ",
                get_current_user_id()
            ),
            ARRAY_A
        );

        return new WP_REST_Response(
            [
                'success' => true,
                'data' => $items,
            ],
            200
        );

    }

    /**
     * Account endpoint.
     */
    public function account(
        WP_REST_Request $request
    ): WP_REST_Response {

        return new WP_REST_Response(
            [
                'success' => true,

                'status' => get_user_meta(
                    get_current_user_id(),
                    'nb_account_status',
                    true
                ),

                'type' => get_user_meta(
                    get_current_user_id(),
                    'nb_account_type',
                    true
                ),

                'email_verified' => (bool) get_user_meta(
                    get_current_user_id(),
                    'nb_email_verified',
                    true
                ),

            ],
            200
        );

    }
    
        /**
     * Update profile endpoint.
     */
    public function update_profile(
        WP_REST_Request $request
    ) {

        $user_id = get_current_user_id();

        $result = wp_update_user(
            [
                'ID'           => $user_id,
                'display_name' => sanitize_text_field(
                    (string) $request->get_param('display_name')
                ),
                'user_email'   => sanitize_email(
                    (string) $request->get_param('email')
                ),
                'user_url'     => esc_url_raw(
                    (string) $request->get_param('website')
                ),
                'description'  => sanitize_textarea_field(
                    (string) $request->get_param('bio')
                ),
            ]
        );

        if (is_wp_error($result)) {
            return new WP_Error(
                'profile_update_failed',
                $result->get_error_message(),
                [
                    'status' => 400,
                ]
            );
        }

        $meta_fields = [

            'nb_phone',
            'nb_whatsapp',
            'nb_country',
            'nb_state',
            'nb_city',
            'nb_address',
            'nb_gender',
            'nb_dob',
            'nb_niche',
            'nb_payment_method',
            'nb_bank_name',
            'nb_account_name',
            'nb_account_number',

        ];

        foreach ($meta_fields as $field) {

            if ($request->has_param($field)) {

                update_user_meta(
                    $user_id,
                    $field,
                    sanitize_text_field(
                        (string) $request->get_param($field)
                    )
                );

            }

        }

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => __(
                    'Profile updated successfully.',
                    'newsblenda-accounts'
                ),
            ],
            200
        );
    }

    /**
     * Logout endpoint.
     */
    public function logout(
        WP_REST_Request $request
    ): WP_REST_Response {

        wp_logout();

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => __(
                    'Logged out successfully.',
                    'newsblenda-accounts'
                ),
                'redirect' => home_url('/login/'),
            ],
            200
        );
    }

    /**
     * Success response helper.
     */
    protected function success(
        array $data = [],
        int $status = 200
    ): WP_REST_Response {

        return new WP_REST_Response(
            [
                'success' => true,
                'data'    => $data,
            ],
            $status
        );

    }

    /**
     * Error response helper.
     */
    protected function error(
        string $message,
        int $status = 400
    ): WP_Error {

        return new WP_Error(
            'nb_accounts_error',
            $message,
            [
                'status' => $status,
            ]
        );

    }

    /**
     * REST namespace.
     */
    public function namespace(): string
    {
        return $this->namespace;
    }
}