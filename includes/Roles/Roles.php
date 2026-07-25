<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Roles;

defined('ABSPATH') || exit;

class Roles
{
    /**
     * Plugin roles.
     */
    private const ROLES = [

        'nb_author_pending',

        'nb_author',

        'nb_author_restricted',

        'nb_editor',

    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action(
            'init',
            [$this, 'register']
        );
    }

    /**
     * Register roles.
     */
    public function register(): void
    {
        self::create_roles();
    }

    /**
     * Create all plugin roles.
     */
    public static function create_roles(): void
    {
        add_role(
            'nb_author_pending',
            __('Pending Author', 'newsblenda-accounts'),
            self::pending_author_capabilities()
        );

        add_role(
            'nb_author',
            __('Newsblenda Author', 'newsblenda-accounts'),
            self::author_capabilities()
        );

        add_role(
            'nb_author_restricted',
            __('Restricted Author', 'newsblenda-accounts'),
            self::restricted_author_capabilities()
        );

        add_role(
            'nb_editor',
            __('Newsblenda Editor', 'newsblenda-accounts'),
            self::editor_capabilities()
        );

        self::administrator_capabilities();
    }
    
        /**
     * Pending Author capabilities.
     */
    private static function pending_author_capabilities(): array
    {
        return [

            'read'                     => true,

            'nb_access_dashboard'      => true,

            'nb_edit_profile'          => true,

            'nb_receive_notifications' => true,

            'nb_submit_articles'       => false,

            'nb_manage_earnings'       => false,

            'upload_files'             => false,

        ];
    }

    /**
     * Author capabilities.
     */
    private static function author_capabilities(): array
    {
        return [

            'read'                     => true,

            'upload_files'             => true,

            'edit_posts'               => true,

            'delete_posts'             => true,

            'edit_published_posts'     => true,

            'publish_posts'            => false,

            'nb_access_dashboard'      => true,

            'nb_submit_articles'       => true,

            'nb_edit_profile'          => true,

            'nb_view_earnings'         => true,

            'nb_receive_notifications' => true,

            'nb_upload_media'          => true,

        ];
    }

    /**
     * Restricted Author capabilities.
     */
    private static function restricted_author_capabilities(): array
    {
        return [

            'read'                     => true,

            'nb_access_dashboard'      => true,

            'nb_edit_profile'          => true,

            'nb_receive_notifications' => true,

            'nb_submit_articles'       => false,

            'upload_files'             => false,

            'edit_posts'               => false,

        ];
    }

    /**
     * Editor capabilities.
     */
    private static function editor_capabilities(): array
    {
        return [

            'read'                       => true,

            'upload_files'               => true,

            'edit_posts'                 => true,

            'edit_others_posts'          => true,

            'delete_posts'               => true,

            'delete_others_posts'        => true,

            'edit_published_posts'       => true,

            'publish_posts'              => true,

            'moderate_comments'          => true,

            'nb_access_dashboard'        => true,

            'nb_review_articles'         => true,

            'nb_request_revision'        => true,

            'nb_approve_articles'        => true,

            'nb_reject_articles'         => true,

            'nb_manage_notifications'    => true,

            'nb_manage_authors'          => true,

            'nb_manage_accounts'         => true,

            'nb_manage_payouts'          => true,

            'nb_view_earnings'           => true,

        ];
    }

    /**
     * Administrator capabilities.
     */
    private static function administrator_capabilities(): void
    {
        $admin = get_role('administrator');

        if (! $admin) {
            return;
        }

        $caps = [

            'nb_access_dashboard',

            'nb_submit_articles',

            'nb_edit_profile',

            'nb_view_earnings',

            'nb_receive_notifications',

            'nb_upload_media',

            'nb_review_articles',

            'nb_request_revision',

            'nb_approve_articles',

            'nb_reject_articles',

            'nb_manage_notifications',

            'nb_manage_authors',

            'nb_manage_accounts',

            'nb_manage_settings',

            'nb_manage_payouts',

        ];

        foreach ($caps as $cap) {

            $admin->add_cap($cap);

        }
    }
    
        /**
     * Assign a role to a user.
     */
    public static function assign(
        int $user_id,
        string $role
    ): bool {

        $user = get_user_by('id', $user_id);

        if (! $user || ! self::is_newsblenda_role($role)) {
            return false;
        }

        $user->set_role($role);

        do_action(
            'nb_accounts_role_assigned',
            $user_id,
            $role
        );

        return true;
    }

    /**
     * Promote pending author to author.
     */
    public static function approve(
        int $user_id
    ): bool {

        update_user_meta(
            $user_id,
            'nb_account_status',
            'approved'
        );

        return self::assign(
            $user_id,
            'nb_author'
        );
    }

    /**
     * Restrict an author.
     */
    public static function restrict(
        int $user_id
    ): bool {

        update_user_meta(
            $user_id,
            'nb_account_status',
            'restricted'
        );

        return self::assign(
            $user_id,
            'nb_author_restricted'
        );
    }

    /**
     * Restore restricted author.
     */
    public static function restore(
        int $user_id
    ): bool {

        update_user_meta(
            $user_id,
            'nb_account_status',
            'approved'
        );

        return self::assign(
            $user_id,
            'nb_author'
        );
    }

    /**
     * User has capability?
     */
    public static function has_capability(
        int $user_id,
        string $capability
    ): bool {

        $user = get_userdata($user_id);

        if (! $user) {
            return false;
        }

        return user_can(
            $user,
            $capability
        );
    }

    /**
     * Get user's Newsblenda role.
     */
    public static function role(
        int $user_id
    ): string {

        $user = get_userdata($user_id);

        if (! $user) {
            return '';
        }

        foreach (self::ROLES as $role) {

            if (in_array($role, $user->roles, true)) {
                return $role;
            }

        }

        return '';
    }

    /**
     * Is this a Newsblenda role?
     */
    public static function is_newsblenda_role(
        string $role
    ): bool {

        return in_array(
            $role,
            self::ROLES,
            true
        );
    }

    /**
     * Get all Newsblenda roles.
     */
    public static function all(): array
    {
        return self::ROLES;
    }

    /**
     * Remove plugin roles.
     */
    public static function remove_roles(): void
    {
        foreach (self::ROLES as $role) {

            remove_role($role);

        }
    }

    /**
     * Recreate all roles.
     */
    public static function reset(): void
    {
        self::remove_roles();

        self::create_roles();
    }

    /**
     * Check whether roles exist.
     */
    public static function installed(): bool
    {
        foreach (self::ROLES as $role) {

            if (! get_role($role)) {
                return false;
            }

        }

        return true;
    }
}