<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Core;

defined('ABSPATH') || exit;

class Loader
{

    /**
     * Plugin base path.
     */
    private string $base_path;

    /**
     * Constructor.
     */
    public function __construct(?string $base_path = null)
    {
        $this->base_path = rtrim(
            $base_path ?: dirname(__DIR__),
            DIRECTORY_SEPARATOR
        );

        spl_autoload_register(
            [$this, 'autoload']
        );
    }

    /**
     * PSR-4 autoloader.
     */
    public function autoload(string $class): void
    {
        $prefix = 'Newsblenda\\Accounts\\';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative = substr(
            $class,
            strlen($prefix)
        );

        $relative = str_replace(
            '\\',
            DIRECTORY_SEPARATOR,
            $relative
        );

        $file = $this->base_path .
            DIRECTORY_SEPARATOR .
            $relative .
            '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }

    /**
     * Registered actions.
     */
    private array $actions = [];

    /**
     * Registered filters.
     */
    private array $filters = [];

    /**
     * Registered shortcodes.
     */
    private array $shortcodes = [];

    /**
     * Registered AJAX hooks.
     */
    private array $ajax = [];

    /**
     * Registered REST callbacks.
     */
    private array $rest = [];

    /**
     * Register action.
     */
    public function add_action(
        string $hook,
        $component,
        string $callback,
        int $priority = 10,
        int $accepted_args = 1
    ): void {

        $this->actions[] = [

            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,

        ];

    }

    /**
     * Register filter.
     */
    public function add_filter(
        string $hook,
        $component,
        string $callback,
        int $priority = 10,
        int $accepted_args = 1
    ): void {

        $this->filters[] = [

            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,

        ];

    }

    /**
     * Register shortcode.
     */
    public function add_shortcode(
        string $tag,
        $component,
        string $callback
    ): void {

        $this->shortcodes[] = [

            'tag'       => $tag,
            'component' => $component,
            'callback'  => $callback,

        ];

    }

    /**
     * Register AJAX.
     */
    public function add_ajax(
        string $action,
        $component,
        string $callback,
        bool $public = false
    ): void {

        $this->ajax[] = [

            'action'    => $action,
            'component' => $component,
            'callback'  => $callback,
            'public'    => $public,

        ];

    }

    /**
     * Register REST callback.
     */
    public function add_rest(
        string $hook,
        $component,
        string $callback
    ): void {

        $this->rest[] = [

            'hook'      => $hook,
            'component' => $component,
            'callback'  => $callback,

        ];

    }
    
        /**
     * Register every hook with WordPress.
     */
    public function run(): void
    {
        foreach ($this->actions as $action) {

            add_action(

                $action['hook'],

                [$action['component'], $action['callback']],

                $action['priority'],

                $action['accepted_args']

            );

        }

        foreach ($this->filters as $filter) {

            add_filter(

                $filter['hook'],

                [$filter['component'], $filter['callback']],

                $filter['priority'],

                $filter['accepted_args']

            );

        }

        foreach ($this->shortcodes as $shortcode) {

            add_shortcode(

                $shortcode['tag'],

                [$shortcode['component'], $shortcode['callback']]

            );

        }

        foreach ($this->ajax as $ajax) {

            add_action(

                'wp_ajax_' . $ajax['action'],

                [$ajax['component'], $ajax['callback']]

            );

            if ($ajax['public']) {

                add_action(

                    'wp_ajax_nopriv_' . $ajax['action'],

                    [$ajax['component'], $ajax['callback']]

                );

            }

        }

        foreach ($this->rest as $rest) {

            add_action(

                $rest['hook'],

                [$rest['component'], $rest['callback']]

            );

        }
    }

    /**
     * Registered actions.
     */
    public function get_actions(): array
    {
        return $this->actions;
    }

    /**
     * Registered filters.
     */
    public function get_filters(): array
    {
        return $this->filters;
    }

    /**
     * Registered shortcodes.
     */
    public function get_shortcodes(): array
    {
        return $this->shortcodes;
    }

    /**
     * Registered AJAX callbacks.
     */
    public function get_ajax(): array
    {
        return $this->ajax;
    }

    /**
     * Registered REST callbacks.
     */
    public function get_rest(): array
    {
        return $this->rest;
    }

    /**
     * Clear loader.
     */
    public function clear(): void
    {
        $this->actions = [];

        $this->filters = [];

        $this->shortcodes = [];

        $this->ajax = [];

        $this->rest = [];
    }

    /**
     * Number of actions.
     */
    public function action_count(): int
    {
        return count($this->actions);
    }

    /**
     * Number of filters.
     */
    public function filter_count(): int
    {
        return count($this->filters);
    }

    /**
     * Number of shortcodes.
     */
    public function shortcode_count(): int
    {
        return count($this->shortcodes);
    }

    /**
     * Number of AJAX callbacks.
     */
    public function ajax_count(): int
    {
        return count($this->ajax);
    }

    /**
     * Number of REST callbacks.
     */
    public function rest_count(): int
    {
        return count($this->rest);
    }
    
        /**
     * Total registered hooks.
     */
    public function total_hooks(): int
    {
        return
            $this->action_count() +
            $this->filter_count() +
            $this->shortcode_count() +
            $this->ajax_count() +
            $this->rest_count();
    }

    /**
     * Determine whether an action has been registered.
     */
    public function has_action(
        string $hook
    ): bool {

        foreach ($this->actions as $action) {

            if ($action['hook'] === $hook) {
                return true;
            }

        }

        return false;
    }

    /**
     * Determine whether a filter has been registered.
     */
    public function has_filter(
        string $hook
    ): bool {

        foreach ($this->filters as $filter) {

            if ($filter['hook'] === $hook) {
                return true;
            }

        }

        return false;
    }

    /**
     * Determine whether a shortcode exists.
     */
    public function has_shortcode(
        string $tag
    ): bool {

        foreach ($this->shortcodes as $shortcode) {

            if ($shortcode['tag'] === $tag) {
                return true;
            }

        }

        return false;
    }

    /**
     * Determine whether an AJAX action exists.
     */
    public function has_ajax(
        string $action
    ): bool {

        foreach ($this->ajax as $ajax) {

            if ($ajax['action'] === $action) {
                return true;
            }

        }

        return false;
    }

    /**
     * Determine whether a REST callback exists.
     */
    public function has_rest(
        string $hook
    ): bool {

        foreach ($this->rest as $rest) {

            if ($rest['hook'] === $hook) {
                return true;
            }

        }

        return false;
    }

    /**
     * Remove all hooks for a specific action.
     */
    public function remove_action(
        string $hook
    ): void {

        $this->actions = array_values(
            array_filter(
                $this->actions,
                static function ($action) use ($hook) {
                    return $action['hook'] !== $hook;
                }
            )
        );

    }

    /**
     * Remove all filters for a specific hook.
     */
    public function remove_filter(
        string $hook
    ): void {

        $this->filters = array_values(
            array_filter(
                $this->filters,
                static function ($filter) use ($hook) {
                    return $filter['hook'] !== $hook;
                }
            )
        );

    }

    /**
     * Reset the loader.
     */
    public function reset(): void
    {
        $this->clear();
    }

    /**
     * Export loader statistics.
     */
    public function stats(): array
    {
        return [

            'actions'   => $this->action_count(),

            'filters'   => $this->filter_count(),

            'shortcodes'=> $this->shortcode_count(),

            'ajax'      => $this->ajax_count(),

            'rest'      => $this->rest_count(),

            'total'     => $this->total_hooks(),

        ];
    }

    /**
     * Is the loader empty?
     */
    public function is_empty(): bool
    {
        return $this->total_hooks() === 0;
    }
    
        /**
     * Register the autoloader.
     */
    public static function register(?string $base_path = null): self
    {
        return new self($base_path);
    }
}