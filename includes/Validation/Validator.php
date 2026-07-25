<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Validation;

defined('ABSPATH') || exit;

class Validator
{
    /**
     * Validation errors.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Validation warnings.
     *
     * @var array
     */
    protected array $warnings = [];

    /**
     * Validation report.
     *
     * @var array
     */
    protected array $report = [];

    /**
     * Validate an article.
     */
    public function validate(
        array $data
    ): bool {

        $this->errors = [];

        $this->warnings = [];

        $this->report = [];

        $this->validate_title($data);

        $this->validate_seo_title($data);

        $this->validate_meta_description($data);

        $this->validate_content($data);

        $this->validate_internal_links($data);

        $this->validate_sources($data);

        $this->validate_featured_image($data);

        $this->validate_forbidden_words($data);

        $this->validate_duplicate_title($data);

        return empty($this->errors);

    }

    /**
     * Validate article title.
     */
    protected function validate_title(
        array $data
    ): void {

        $title = trim(
            (string) ($data['post_title'] ?? '')
        );

        if ($title === '') {

            $this->errors[] = __(
                'Article title is required.',
                'newsblenda-accounts'
            );

            return;

        }

        if (mb_strlen($title) < 15) {

            $this->warnings[] = __(
                'The article title is quite short.',
                'newsblenda-accounts'
            );

        }

    }

    /**
     * Validate SEO title.
     */
    protected function validate_seo_title(
        array $data
    ): void {

        $title = trim(
            (string) ($data['seo_title'] ?? '')
        );

        if ($title === '') {

            $this->errors[] = __(
                'SEO title is required.',
                'newsblenda-accounts'
            );

            return;

        }

        if (mb_strlen($title) > 60) {

            $this->warnings[] = __(
                'SEO title exceeds 60 characters.',
                'newsblenda-accounts'
            );

        }

    }

    /**
     * Validate meta description.
     */
    protected function validate_meta_description(
        array $data
    ): void {

        $description = trim(
            (string) ($data['meta_description'] ?? '')
        );

        if ($description === '') {

            $this->errors[] = __(
                'Meta description is required.',
                'newsblenda-accounts'
            );

            return;

        }

        if (mb_strlen($description) > 160) {

            $this->warnings[] = __(
                'Meta description exceeds 160 characters.',
                'newsblenda-accounts'
            );

        }

    }
    
        /**
     * Validate article content.
     */
    protected function validate_content(
        array $data
    ): void {

        $content = wp_strip_all_tags(
            (string) ($data['article_content'] ?? '')
        );

        $words = str_word_count($content);

        $this->report['word_count'] = $words;

        if ($words < 900) {

            $this->errors[] = __(
                'Article must contain at least 900 words.',
                'newsblenda-accounts'
            );

        }

        if ($words > 1500) {

            $this->errors[] = __(
                'Article must not exceed 1500 words.',
                'newsblenda-accounts'
            );

        }

    }

    /**
     * Validate Newsblenda internal links.
     */
    protected function validate_internal_links(
        array $data
    ): void {

        $content = (string) ($data['article_content'] ?? '');

        preg_match_all(
            '/https?:\/\/[^"\s<]+/i',
            $content,
            $matches
        );

        $internal = 0;

        foreach ($matches[0] as $url) {

            if (stripos($url, 'newsblenda.com') !== false) {

                $internal++;

            }

        }

        $this->report['internal_links'] = $internal;

        if ($internal < 3) {

            $this->errors[] = __(
                'At least three internal Newsblenda links are required.',
                'newsblenda-accounts'
            );

        }

    }

    /**
     * Validate article sources.
     */
    protected function validate_sources(
        array $data
    ): void {

        $sources = trim(
            (string) ($data['sources'] ?? '')
        );

        if ($sources === '') {

            $this->errors[] = __(
                'Please provide at least one source.',
                'newsblenda-accounts'
            );

            return;

        }

        $lines = array_filter(
            array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', $sources)
            )
        );

        $this->report['sources'] = count($lines);

        foreach ($lines as $url) {

            if (! filter_var($url, FILTER_VALIDATE_URL)) {

                $this->warnings[] = sprintf(

                    __(
                        'Invalid source URL: %s',
                        'newsblenda-accounts'
                    ),

                    $url

                );

            }

        }

    }

    /**
     * Validate featured image.
     */
    protected function validate_featured_image(
        array $data
    ): void {

        $post_id = isset($data['post_id'])
            ? (int) $data['post_id']
            : 0;

        if (
            (empty($_FILES['featured_image']) ||
            empty($_FILES['featured_image']['name'])) &&
            ($post_id < 1 || ! has_post_thumbnail($post_id))
        ) {

            $this->errors[] = __(
                'A featured image is required.',
                'newsblenda-accounts'
            );

            return;

        }

        if (
            empty($_FILES['featured_image']) ||
            empty($_FILES['featured_image']['name'])
        ) {
            return;
        }

        $allowed = [

            'image/jpeg',

            'image/png',

            'image/webp',

        ];

        $type = $_FILES['featured_image']['type'] ?? '';

        if (! in_array($type, $allowed, true)) {

            $this->errors[] = __(
                'Featured image must be JPG, PNG or WebP.',
                'newsblenda-accounts'
            );

        }

    }

    /**
     * Detect forbidden words.
     */
    protected function validate_forbidden_words(
        array $data
    ): void {

        $list = get_option(
            'nb_forbidden_words',
            []
        );

        if (! is_array($list)) {
            return;
        }

        $text = strtolower(

            wp_strip_all_tags(

                (string) ($data['article_content'] ?? '')

            )

        );

        foreach ($list as $word) {

            $word = trim(
                strtolower((string) $word)
            );

            if ($word === '') {
                continue;
            }

            if (strpos($text, $word) !== false) {

                $this->errors[] = sprintf(

                    __(
                        'Forbidden word detected: %s',
                        'newsblenda-accounts'
                    ),

                    $word

                );

            }

        }

    }
    
        /**
     * Validate duplicate titles.
     */
    protected function validate_duplicate_title(
        array $data
    ): void {

        $title = trim(
            (string) ($data['post_title'] ?? '')
        );

        if ($title === '') {
            return;
        }

        $existing = get_page_by_title(
            $title,
            OBJECT,
            'post'
        );

        $current_post_id = isset($data['post_id']) ? (int) $data['post_id'] : 0;

        if ($existing instanceof \WP_Post && $existing->ID !== $current_post_id) {

            $this->warnings[] = __(
                'A similar article title already exists.',
                'newsblenda-accounts'
            );

            $this->report['duplicate_post_id'] = $existing->ID;

        }

    }

    /**
     * Get validation errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings.
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get validation report.
     */
    public function report(): array
    {
        return array_merge(

            $this->report,

            [

                'passed' => empty($this->errors),

                'errors' => $this->errors,

                'warnings' => $this->warnings,

                'validated_at' => current_time('mysql'),

            ]

        );

    }

    /**
     * Store validation report.
     */
    public function save_report(
        int $post_id
    ): void {

        update_post_meta(

            $post_id,

            'nb_validation_report',

            $this->report()

        );

    }

    /**
     * Calculate author's rejection rate.
     */
    public function rejection_rate(
        int $user_id
    ): float {

        $approved = (int) get_user_meta(
            $user_id,
            'nb_approved_articles',
            true
        );

        $rejected = (int) get_user_meta(
            $user_id,
            'nb_rejected_articles',
            true
        );

        $reviewed = $approved + $rejected;

        $minimum_reviews = (int) get_option(
            'nb_minimum_reviewed_articles',
            10
        );

        if ($reviewed < $minimum_reviews) {

            return 0;

        }

        $rate = ($rejected / $reviewed) * 100;

        update_user_meta(

            $user_id,

            'nb_rejection_rate',

            round($rate, 2)

        );

        return round($rate, 2);

    }

    /**
     * Restrict author if rejection rate is too high.
     */
    public function enforce_author_restriction(
        int $user_id
    ): bool {

        $limit = (float) get_option(
            'nb_rejection_limit',
            60
        );

        $rate = $this->rejection_rate(
            $user_id
        );

        if ($rate >= $limit) {

            update_user_meta(

                $user_id,

                'nb_submission_restricted',

                1

            );

            do_action(

                'nb_accounts_author_restricted',

                $user_id,

                $rate

            );

            return true;

        }

        delete_user_meta(

            $user_id,

            'nb_submission_restricted'

        );

        return false;

    }

    /**
     * Determine whether the author may submit articles.
     */
    public function can_submit(
        int $user_id
    ): bool {

        return ! (bool) get_user_meta(

            $user_id,

            'nb_submission_restricted',

            true

        );

    }

    /**
     * Reset validator state.
     */
    public function reset(): void
    {
        $this->errors = [];
        $this->warnings = [];
        $this->report = [];
    }
}