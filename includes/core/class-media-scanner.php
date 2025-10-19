<?php

/**
 * Media Scanner Class
 *
 * Handles media detection and scanning functionality
 *
 * @package OrphanedACFMedia
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OrphanedACFMedia_MediaScanner
{

    /**
     * Get orphaned media files with pagination
     *
     * @param int $page Current page number
     * @param int $per_page Items per page
     * @param bool $scan_all Whether to scan all or use cache
     * @param string $file_type_filter File type filter
     * @param string $safety_status_filter Safety status filter
     * @return array
     */
    public function get_orphaned_media($page = 1, $per_page = 50, $scan_all = false, $file_type_filter = 'all', $safety_status_filter = 'all')
    {
        // Cache key for this specific scan configuration
        $cache_key = 'orphaned_acf_media_scan_' . md5($file_type_filter . '_' . $safety_status_filter . '_' . $scan_all);

        // Try to get from cache first (unless forcing a fresh scan)
        if (!$scan_all) {
            $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');
            if ($cached_result !== false) {
                return $this->paginate_results($cached_result, $page, $per_page);
            }
        }

        global $wpdb;

        // Get all attachments
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for comprehensive media analysis
        $all_attachments = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title, post_date, guid
            FROM {$wpdb->posts}
            WHERE post_type = %s
            AND post_status = %s
            ORDER BY post_date DESC
        ", 'attachment', 'inherit'));

        $orphaned_media = array();
        $total_processed = 0;
        $total_attachments = count($all_attachments);

        foreach ($all_attachments as $attachment) {
            $total_processed++;

            // Basic attachment info
            $file_url = wp_get_attachment_url($attachment->ID);
            $file_path = get_attached_file($attachment->ID);
            $filename = basename($file_path);
            $file_size = file_exists($file_path) ? filesize($file_path) : 0;
            $file_type = wp_check_filetype($filename);

            // Check if used in ACF
            $used_in_acf = $this->is_attachment_used_in_acf($attachment->ID);

            // Only process media that's NOT used in ACF (that's what we want to show)
            if (!$used_in_acf) {
                // Check if used elsewhere for safety classification
                $used_elsewhere = $this->is_attachment_used_elsewhere($attachment->ID);

                // Determine safety status
                $is_truly_orphaned = !$used_elsewhere;

                // Get detailed usage information
                $usage_details = $this->get_attachment_usage_details($attachment->ID);

                $orphaned_media[] = array(
                    'id' => $attachment->ID,
                    'title' => $attachment->post_title,
                    'filename' => $filename,
                    'url' => $file_url,
                    'thumbnail' => wp_get_attachment_image($attachment->ID, array(80, 80)),
                    'upload_date' => $attachment->post_date,
                    'file_size' => $file_size,
                    'file_type' => $file_type['type'],
                    'mime_type' => get_post_mime_type($attachment->ID),
                    'is_truly_orphaned' => $is_truly_orphaned,
                    'safety_status' => $is_truly_orphaned ? 'safe' : 'warning',
                    'usage_details' => $usage_details
                );
            }
        }

        // Apply filters
        $filtered_media = $this->apply_filters($orphaned_media, $file_type_filter, $safety_status_filter);

        // Cache the results for 5 minutes
        wp_cache_set($cache_key, $filtered_media, 'orphaned_acf_media', 300);

        return $this->paginate_results($filtered_media, $page, $per_page);
    }

    /**
     * Check if attachment is used in ACF fields
     *
     * @param int $attachment_id
     * @return bool
     */
    public function is_attachment_used_in_acf($attachment_id)
    {
        $cache_key = 'acf_usage_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        $used = $this->is_attachment_used_in_acf_fields($attachment_id);

        // Cache for 5 minutes
        wp_cache_set($cache_key, $used, 'orphaned_acf_media', 300);

        return $used;
    }

    /**
     * Check if attachment is used elsewhere
     *
     * @param int $attachment_id
     * @return bool
     */
    public function is_attachment_used_elsewhere($attachment_id)
    {
        // Ensure plugin functions are available
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $cache_key = 'elsewhere_usage_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        $file_url = wp_get_attachment_url($attachment_id);
        $file_path = get_attached_file($attachment_id);
        $filename = basename($file_path);

        // Check various usage areas
        $used_as_featured = !empty($this->check_usage_in_featured_images($attachment_id, $file_url, $filename));
        $used_in_content = !empty($this->check_usage_in_post_content($attachment_id, $file_url, $filename));
        $used_in_widgets = $this->check_usage_in_widgets($attachment_id, $file_url, $filename);
        $used_in_menus = $this->check_usage_in_menus($attachment_id, $file_url, $filename);
        $used_in_customizer = $this->check_usage_in_customizer($attachment_id, $file_url, $filename);

        // Check if Oxygen Builder is active and scan its data
        $used_in_oxygen = false;
        if (is_plugin_active('oxygen/plugin.php') || class_exists('CT_Component')) {
            $used_in_oxygen = $this->check_usage_in_oxygen_builder($attachment_id, $file_url, $filename);
        }

        // Check if WooCommerce is active and scan its data
        $used_in_woocommerce = false;
        if (is_plugin_active('woocommerce/woocommerce.php') || class_exists('WooCommerce')) {
            $used_in_woocommerce = $this->check_usage_in_woocommerce($attachment_id, $file_url, $filename);
        }

        // Add catch-all check for any other post meta (like original)
        // This is what catches Oxygen Builder and other page builders
        $used_in_other_meta = $this->check_usage_in_all_post_meta($attachment_id, $file_url, $filename);

        // Also check user meta like the original
        $used_in_user_meta = $this->check_usage_in_user_meta($attachment_id, $file_url, $filename);

        $used = $used_as_featured || $used_in_content || $used_in_widgets ||
            $used_in_menus || $used_in_customizer || $used_in_oxygen || $used_in_woocommerce || $used_in_other_meta || $used_in_user_meta;

        // Cache for 5 minutes
        wp_cache_set($cache_key, $used, 'orphaned_acf_media', 300);
        return $used;
    }

    /**
     * Clear orphaned media cache
     */
    public function clear_orphaned_cache()
    {
        // Clear WordPress cache
        wp_cache_flush_group('orphaned_acf_media');

        // Clear transients
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct query for transient cleanup
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
        ", '_transient_orphaned_acf_media_%'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct query for transient cleanup
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
        ", '_transient_timeout_orphaned_acf_media_%'));
    }

    /**
     * Get detailed attachment usage information
     *
     * @param int $attachment_id
     * @return array
     */
    public function get_attachment_usage_details($attachment_id)
    {
        // Ensure plugin functions are available
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $file_url = wp_get_attachment_url($attachment_id);
        $file_path = get_attached_file($attachment_id);
        $filename = basename($file_path);

        $usage_details = array();

        // Check various usage locations - return labels like original
        $checks = array(
            'acf_fields' => 'ACF Fields',
            'featured_images' => 'Featured Images',
            'post_content' => 'Post/Page Content',
            'widgets' => 'Widgets',
            'menus' => 'Navigation Menus',
            'customizer' => 'Theme Customizer',
            'site_settings' => 'Site Settings'
        );

        // Add Oxygen Builder check if plugin is active
        if (is_plugin_active('oxygen/plugin.php') || class_exists('CT_Component')) {
            $checks['oxygen_builder'] = 'Oxygen Builder';
        }

        // Add WooCommerce check if plugin is active
        if (is_plugin_active('woocommerce/woocommerce.php') || class_exists('WooCommerce')) {
            $checks['woocommerce'] = 'WooCommerce (Products/Categories)';
        }

        // Add catch-all check for any other post meta (like original)
        $checks['all_post_meta'] = 'Page Builder/Custom Fields';

        // Add user meta check like original
        $checks['user_meta'] = 'User Profiles';

        foreach ($checks as $check => $label) {
            $method = "check_usage_in_{$check}";
            if (method_exists($this, $method)) {
                $result = $this->$method($attachment_id, $file_url, $filename);
                if ($result) {
                    $usage_details[] = $label;
                }
            }
        }

        return $usage_details;
    }

    /**
     * Check if attachment is used in any ACF field
     *
     * @param int $attachment_id
     * @return bool
     */
    private function is_attachment_used_in_acf_fields($attachment_id)
    {
        global $wpdb;

        // Search in post meta for ACF fields
        // Check cache first
        $cache_key = 'orphaned_acf_meta_' . $attachment_id;
        $count = wp_cache_get($cache_key, 'orphaned_acf_media');

        if (false === $count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Complex meta query for performance, cached result
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->postmeta}
                WHERE meta_value = %s
                AND meta_key NOT LIKE %s
            ", $attachment_id, '\\_%'));
            wp_cache_set($cache_key, $count, 'orphaned_acf_media', 300); // Cache for 5 minutes
        }

        if ($count > 0) {
            return true;
        }

        // Also check in serialized data (for ACF repeater fields, etc.)
        // Check cache first for serialized data
        $serialized_cache_key = 'orphaned_acf_serialized_' . $attachment_id;
        $serialized_count = wp_cache_get($serialized_cache_key, 'orphaned_acf_media');

        if (false === $serialized_count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Complex serialized data search for performance, cached result
            $serialized_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->postmeta}
                WHERE meta_value LIKE %s
                AND meta_key NOT LIKE %s
            ", '%"' . $attachment_id . '"%', '\\_%'));
            wp_cache_set($serialized_cache_key, $serialized_count, 'orphaned_acf_media', 300); // Cache for 5 minutes
        }

        if ($serialized_count > 0) {
            return true;
        }

        // Check in ACF options (for options pages)
        // Check cache first for ACF options
        $options_cache_key = 'orphaned_acf_options_' . $attachment_id;
        $options_count = wp_cache_get($options_cache_key, 'orphaned_acf_media');

        if (false === $options_count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- ACF options table search for performance, cached result
            $options_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->options}
                WHERE option_name LIKE %s
                AND (option_value = %s OR option_value LIKE %s)
            ", 'options\\_%', $attachment_id, '%"' . $attachment_id . '"%'));
            wp_cache_set($options_cache_key, $options_count, 'orphaned_acf_media', 300); // Cache for 5 minutes
        }

        if ($options_count > 0) {
            return true;
        }

        // Check ACF Extended Performance Mode consolidated 'acf' meta field
        $acfe_cache_key = 'orphaned_acf_extended_' . $attachment_id;
        $acfe_count = wp_cache_get($acfe_cache_key, 'orphaned_acf_media');

        if (false === $acfe_count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- ACF Extended performance mode consolidated field search, cached result
            $acfe_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->postmeta}
                WHERE meta_key = 'acf'
                AND (meta_value LIKE %s OR meta_value LIKE %s)
            ", '%"' . $attachment_id . '"%', '%:' . $attachment_id . ';%'));
            wp_cache_set($acfe_cache_key, $acfe_count, 'orphaned_acf_media', 300); // Cache for 5 minutes
        }

        if ($acfe_count > 0) {
            return true;
        }

        // Also check ACF Extended Performance Mode in options table
        $acfe_options_cache_key = 'orphaned_acf_extended_options_' . $attachment_id;
        $acfe_options_count = wp_cache_get($acfe_options_cache_key, 'orphaned_acf_media');

        if (false === $acfe_options_count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- ACF Extended options performance mode search, cached result
            $acfe_options_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->options}
                WHERE option_name = 'options_acf'
                AND (option_value LIKE %s OR option_value LIKE %s)
            ", '%"' . $attachment_id . '"%', '%:' . $attachment_id . ';%'));
            wp_cache_set($acfe_options_cache_key, $acfe_options_count, 'orphaned_acf_media', 300); // Cache for 5 minutes
        }

        return $acfe_options_count > 0;
    }

    /**
     * Check usage in ACF fields for details
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return array
     */
    private function check_usage_in_acf_fields($attachment_id, $file_url, $filename)
    {
        // Implementation would return detailed usage info
        return array();
    }

    /**
     * Check usage in featured images
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return array
     */
    private function check_usage_in_featured_images($attachment_id, $file_url, $filename)
    {
        global $wpdb;

        // Check cache first
        $cache_key = 'orphaned_featured_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Featured image meta query for performance, cached result
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_type
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_thumbnail_id'
            AND pm.meta_value = %s
            AND p.post_status IN ('publish', 'private', 'draft')
        ", $attachment_id));

        $usage = array();
        foreach ($posts as $post) {
            $usage[] = sprintf('Featured image for %s: %s', $post->post_type, $post->post_title);
        }

        // Cache for 5 minutes
        wp_cache_set($cache_key, $usage, 'orphaned_acf_media', 300);

        return $usage;
    }

    /**
     * Check usage in post content
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return array
     */
    private function check_usage_in_post_content($attachment_id, $file_url, $filename)
    {
        global $wpdb;

        // Check cache first
        $cache_key = 'orphaned_content_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        $usage = array();

        // Search by attachment ID in content
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Content search for performance, cached result
        $id_posts = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title, post_type
            FROM {$wpdb->posts}
            WHERE post_content LIKE %s
            AND post_status IN ('publish', 'private', 'draft')
        ", '%wp-image-' . $attachment_id . '%'));

        foreach ($id_posts as $post) {
            $usage[] = sprintf('Content in %s: %s', $post->post_type, $post->post_title);
        }

        // Search by filename in content
        if (!empty($filename)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Filename search for performance, cached result
            $filename_posts = $wpdb->get_results($wpdb->prepare("
                SELECT ID, post_title, post_type
                FROM {$wpdb->posts}
                WHERE post_content LIKE %s
                AND post_status IN ('publish', 'private', 'draft')
            ", '%' . $wpdb->esc_like($filename) . '%'));

            foreach ($filename_posts as $post) {
                $usage[] = sprintf('Filename reference in %s: %s', $post->post_type, $post->post_title);
            }
        }

        // Cache for 5 minutes
        wp_cache_set($cache_key, $usage, 'orphaned_acf_media', 300);

        return $usage;
    }

    /**
     * Check usage in widgets
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return bool
     */
    private function check_usage_in_widgets($attachment_id, $file_url, $filename)
    {
        // Check cache first
        $cache_key = 'orphaned_widgets_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        $widget_options = get_option('widget_media_image', array());
        foreach ($widget_options as $widget) {
            if (is_array($widget) && isset($widget['attachment_id']) && $widget['attachment_id'] == $attachment_id) {
                wp_cache_set($cache_key, true, 'orphaned_acf_media', 300);
                return true;
            }
        }

        // Check in other widget types that might contain media
        $all_widget_options = wp_load_alloptions();
        foreach ($all_widget_options as $option_name => $option_value) {
            if (strpos($option_name, 'widget_') === 0) {
                if (
                    strpos($option_value, (string)$attachment_id) !== false ||
                    (!empty($filename) && strpos($option_value, $filename) !== false)
                ) {
                    wp_cache_set($cache_key, true, 'orphaned_acf_media', 300);
                    return true;
                }
            }
        }

        wp_cache_set($cache_key, false, 'orphaned_acf_media', 300);
        return false;
    }

    /**
     * Check usage in menus
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return bool
     */
    private function check_usage_in_menus($attachment_id, $file_url, $filename)
    {
        // Check cache first
        $cache_key = 'orphaned_menus_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        global $wpdb;

        // Check in nav menu items
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Menu item meta search for performance, cached result
        $menu_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'nav_menu_item'
            AND (pm.meta_value = %s OR pm.meta_value LIKE %s)
        ", $attachment_id, '%' . $wpdb->esc_like($filename) . '%'));

        $result = $menu_count > 0;
        wp_cache_set($cache_key, $result, 'orphaned_acf_media', 300);

        return $result;
    }

    /**
     * Check usage in customizer
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return bool
     */
    private function check_usage_in_customizer($attachment_id, $file_url, $filename)
    {
        // Check cache first
        $cache_key = 'orphaned_customizer_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        // Check theme modifications
        $theme_mods = get_theme_mods();
        if ($theme_mods) {
            $serialized_mods = serialize($theme_mods);
            if (
                strpos($serialized_mods, (string)$attachment_id) !== false ||
                (!empty($filename) && strpos($serialized_mods, $filename) !== false)
            ) {
                wp_cache_set($cache_key, true, 'orphaned_acf_media', 300);
                return true;
            }
        }

        // Check other customizer-related options
        $customizer_options = array(
            'site_icon',
            'custom_logo',
            'header_image',
            'background_image'
        );

        foreach ($customizer_options as $option) {
            $value = get_option($option);
            if (
                $value == $attachment_id ||
                (!empty($filename) && is_string($value) && strpos($value, $filename) !== false)
            ) {
                wp_cache_set($cache_key, true, 'orphaned_acf_media', 300);
                return true;
            }
        }

        wp_cache_set($cache_key, false, 'orphaned_acf_media', 300);
        return false;
    }

    /**
     * Check usage in Oxygen Builder
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return bool
     */
    private function check_usage_in_oxygen_builder($attachment_id, $file_url, $filename)
    {
        // Check cache first
        $cache_key = 'orphaned_oxygen_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        global $wpdb;

        // Check Oxygen Builder content in post meta
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Oxygen Builder meta search for performance, cached result
        $oxygen_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key IN ('ct_builder_shortcodes', 'ct_builder_json', '_oxygen_data', 'ct_other_template', 'ct_template_type')
            AND (meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $filename . '%', '%' . $file_url . '%'));

        // Also check Oxygen options (templates, global settings)
        if ($oxygen_count === 0) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Oxygen Builder options search for performance, cached result
            $oxygen_options_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->options}
                WHERE option_name LIKE %s
                AND (option_value LIKE %s OR option_value LIKE %s OR option_value LIKE %s)
            ", 'ct_%', '%' . $attachment_id . '%', '%' . $filename . '%', '%' . $file_url . '%'));

            $oxygen_count = $oxygen_options_count;
        }

        $result = $oxygen_count > 0;
        wp_cache_set($cache_key, $result, 'orphaned_acf_media', 300);

        return $result;
    }

    /**
     * Check if attachment is used in WooCommerce
     * Includes product galleries, featured images, category images, shop customizer settings
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return bool
     */
    private function check_usage_in_woocommerce($attachment_id, $file_url, $filename)
    {
        // Check cache first
        $cache_key = 'orphaned_woocommerce_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        global $wpdb;
        $usage_found = false;

        // Early optimization: Check if WooCommerce has any content to scan
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Quick count for optimization, cached result
        $has_woo_content = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type IN ('product', 'product_variation')
            AND post_status IN ('publish', 'private', 'draft')
            LIMIT 1
        ");

        // If no products exist, check only WooCommerce-specific settings (not general theme settings)
        if ($has_woo_content == 0) {
            // Only check WooCommerce options that can actually contain media references
            // Exclude configuration options that might contain coincidental numeric matches
            $media_related_options = array(
                'woocommerce_catalog_image',
                'woocommerce_single_image', 
                'woocommerce_thumbnail_image',
                'woocommerce_shop_page_id',
                'woocommerce_cart_page_id',
                'woocommerce_checkout_page_id',
                'woocommerce_myaccount_page_id',
                'woocommerce_terms_page_id',
                'woocommerce_placeholder_image',
                'woocommerce_shop_header_image',
                'woocommerce_email_header_image'
            );
            
            $usage_found = false;
            
            // Only check specific media-related WooCommerce options
            foreach ($media_related_options as $option_key) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce specific options search for performance, cached result
                $option_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*)
                    FROM {$wpdb->options}
                    WHERE option_name = %s
                    AND (option_value = %s OR option_value LIKE %s OR option_value LIKE %s)
                ", $option_key, $attachment_id, '%' . $filename . '%', '%' . $file_url . '%'));

                if ($option_count > 0) {
                    $usage_found = true;
                    break;
                }
            }

            // Cache and return early
            wp_cache_set($cache_key, $usage_found, 'orphaned_acf_media', 300);
            return $usage_found;
        }        // 1. Check WooCommerce product galleries (_product_image_gallery)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce gallery search for performance, cached result
        $gallery_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'product'
            AND pm.meta_key = '_product_image_gallery'
            AND pm.meta_value LIKE %s
        ", '%' . $attachment_id . '%'));

        if ($gallery_count > 0) {
            $usage_found = true;
        }

        // 2. Check WooCommerce product featured images (_thumbnail_id)
        if (!$usage_found) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce thumbnail search for performance, cached result
            $thumbnail_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'product'
                AND pm.meta_key = '_thumbnail_id'
                AND pm.meta_value = %s
            ", $attachment_id));

            if ($thumbnail_count > 0) {
                $usage_found = true;
            }
        }

        // 3. Check WooCommerce category/tag thumbnails
        if (!$usage_found) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce term meta search for performance, cached result
            $term_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->termmeta} tm
                INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
                WHERE tt.taxonomy IN ('product_cat', 'product_tag')
                AND tm.meta_key = 'thumbnail_id'
                AND tm.meta_value = %s
            ", $attachment_id));

            if ($term_count > 0) {
                $usage_found = true;
            }
        }

        // 4. Check WooCommerce-specific customizer settings (shop headers, backgrounds, etc.)
        if (!$usage_found) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce customizer search for performance, cached result
            $customizer_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->options}
                WHERE option_name LIKE %s
                AND option_name LIKE %s
                AND (option_value LIKE %s OR option_value LIKE %s OR option_value LIKE %s)
            ", 'theme_mods_%', '%woocommerce%', '%' . $attachment_id . '%', '%' . $filename . '%', '%' . $file_url . '%'));

            if ($customizer_count > 0) {
                $usage_found = true;
            }
        }

        // 5. Check WooCommerce-specific options and settings
        if (!$usage_found) {
            // Only check WooCommerce options that can actually contain media references
            $media_related_options = array(
                'woocommerce_catalog_image',
                'woocommerce_single_image', 
                'woocommerce_thumbnail_image',
                'woocommerce_shop_page_id',
                'woocommerce_cart_page_id',
                'woocommerce_checkout_page_id',
                'woocommerce_myaccount_page_id',
                'woocommerce_terms_page_id',
                'woocommerce_placeholder_image',
                'woocommerce_shop_header_image',
                'woocommerce_email_header_image'
            );
            
            // Only check specific media-related WooCommerce options
            foreach ($media_related_options as $option_key) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce specific options search for performance, cached result
                $option_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*)
                    FROM {$wpdb->options}
                    WHERE option_name = %s
                    AND (option_value = %s OR option_value LIKE %s OR option_value LIKE %s)
                ", $option_key, $attachment_id, '%' . $filename . '%', '%' . $file_url . '%'));

                if ($option_count > 0) {
                    $usage_found = true;
                    break;
                }
            }
        }

        // 6. Check WooCommerce product content and short descriptions
        if (!$usage_found) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce product content search for performance, cached result
            $content_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->posts}
                WHERE post_type = 'product'
                AND (post_content LIKE %s OR post_content LIKE %s OR post_excerpt LIKE %s OR post_excerpt LIKE %s)
            ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $attachment_id . '%', '%' . $file_url . '%'));

            if ($content_count > 0) {
                $usage_found = true;
            }
        }

        // 7. Check WooCommerce variations and variation images
        if (!$usage_found) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce variation search for performance, cached result
            $variation_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'product_variation'
                AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
            ", '%' . $attachment_id . '%', '%' . $file_url . '%'));

            if ($variation_count > 0) {
                $usage_found = true;
            }
        }

        // Cache the result for 5 minutes
        wp_cache_set($cache_key, $usage_found, 'orphaned_acf_media', 300);

        return $usage_found;
    }

    /**
     * Check if attachment is used in any post meta (catch-all like original)
     * This is what catches Oxygen Builder and other page builders
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return bool
     */
    private function check_usage_in_all_post_meta($attachment_id, $file_url, $filename)
    {
        global $wpdb;

        // Check cache first
        $cache_key = 'orphaned_allmeta_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        // This is the original catch-all query that made Oxygen Builder work
        // Now with proper post validation to exclude orphaned meta entries
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Comprehensive meta search for performance, cached result
        $all_meta_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE (pm.meta_value = %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
            AND pm.meta_key NOT LIKE %s
            AND pm.meta_key NOT IN ('_thumbnail_id', '_product_image_gallery')
            AND p.post_status IN ('publish', 'private', 'draft', 'pending', 'future')
        ", $attachment_id, '%' . $file_url . '%', '%' . $filename . '%', '_%'));

        $result = $all_meta_count > 0;
        wp_cache_set($cache_key, $result, 'orphaned_acf_media', 300);

        return $result;
    }

    /**
     * Check if attachment is used in any user meta (like original)
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return bool
     */
    private function check_usage_in_user_meta($attachment_id, $file_url, $filename)
    {
        global $wpdb;

        // Check cache first
        $cache_key = 'orphaned_usermeta_' . $attachment_id;
        $cached_result = wp_cache_get($cache_key, 'orphaned_acf_media');

        if ($cached_result !== false) {
            return $cached_result;
        }

        // Check user meta like the original
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- User meta search for performance, cached result
        $user_meta_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->usermeta}
            WHERE meta_value = %s
            OR meta_value LIKE %s
            OR meta_value LIKE %s
        ", $attachment_id, '%' . $file_url . '%', '%' . $filename . '%'));

        $result = $user_meta_count > 0;
        wp_cache_set($cache_key, $result, 'orphaned_acf_media', 300);

        return $result;
    }

    /**
     * Apply filters to media array
     *
     * @param array $media_array
     * @param string $file_type_filter
     * @param string $safety_status_filter
     * @return array
     */
    private function apply_filters($media_array, $file_type_filter, $safety_status_filter)
    {
        if ($file_type_filter === 'all' && $safety_status_filter === 'all') {
            return $media_array;
        }

        return array_filter($media_array, function ($media) use ($file_type_filter, $safety_status_filter) {
            // File type filtering
            $file_type_match = true;
            if ($file_type_filter !== 'all') {
                $mime_type = $media['mime_type'];
                switch ($file_type_filter) {
                    case 'images':
                        $file_type_match = strpos($mime_type, 'image/') === 0;
                        break;
                    case 'videos':
                        $file_type_match = strpos($mime_type, 'video/') === 0;
                        break;
                    case 'audio':
                        $file_type_match = strpos($mime_type, 'audio/') === 0;
                        break;
                    case 'pdfs':
                        $file_type_match = $mime_type === 'application/pdf';
                        break;
                    case 'documents':
                        $document_types = [
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                        ];
                        $file_type_match = in_array($mime_type, $document_types);
                        break;
                }
            }

            // Safety status filtering
            $safety_match = true;
            if ($safety_status_filter !== 'all') {
                switch ($safety_status_filter) {
                    case 'safe':
                        $safety_match = $media['is_truly_orphaned'] === true;
                        break;
                    case 'warning':
                        $safety_match = $media['is_truly_orphaned'] === false;
                        break;
                }
            }

            return $file_type_match && $safety_match;
        });
    }

    /**
     * Check if attachment is used in site settings (logo, favicon, etc.)
     *
     * @param int $attachment_id
     * @param string $file_url
     * @param string $filename
     * @return bool
     */
    private function check_usage_in_site_settings($attachment_id, $file_url, $filename)
    {
        // Check site icon/favicon
        $site_icon = get_option('site_icon');
        if ($site_icon && $site_icon == $attachment_id) {
            return true;
        }

        // Check custom header
        $custom_header = get_option('header_image');
        if ($custom_header && (strpos($custom_header, $filename) !== false || strpos($custom_header, $file_url) !== false)) {
            return true;
        }

        // Check custom background
        $custom_background = get_option('background_image');
        if ($custom_background && (strpos($custom_background, $filename) !== false || strpos($custom_background, $file_url) !== false)) {
            return true;
        }

        // Check other common logo/branding options
        $logo_options = array('custom_logo', 'site_logo', 'logo', 'brand_logo');
        foreach ($logo_options as $option) {
            $value = get_option($option);
            if ($value && (
                $value == $attachment_id ||
                (is_string($value) && (strpos($value, $filename) !== false || strpos($value, $file_url) !== false))
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Paginate results
     *
     * @param array $media_array
     * @param int $page
     * @param int $per_page
     * @return array
     */
    private function paginate_results($media_array, $page, $per_page)
    {
        $total_items = count($media_array);
        $total_pages = ceil($total_items / $per_page);
        $offset = ($page - 1) * $per_page;
        $paged_media = array_slice($media_array, $offset, $per_page);

        // Count safe to delete items
        $safe_to_delete_count = 0;
        foreach ($media_array as $media) {
            if (isset($media['is_truly_orphaned']) && $media['is_truly_orphaned']) {
                $safe_to_delete_count++;
            }
        }

        return array(
            'media' => $paged_media,
            'pagination' => array(
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_items' => $total_items,
                'per_page' => $per_page,
                'has_prev' => $page > 1,
                'has_next' => $page < $total_pages,
                'total_safe_to_delete' => $safe_to_delete_count
            )
        );
    }

    /**
     * Get all safe to delete media
     *
     * @return array
     */
    public function get_all_safe_to_delete_media()
    {
        // Get all orphaned media with a large page size
        $result = $this->get_orphaned_media(1, 9999, true, 'all', 'safe');

        if (!isset($result['media']) || !is_array($result['media'])) {
            return array();
        }

        return array_filter($result['media'], function ($media) {
            return isset($media['is_truly_orphaned']) && $media['is_truly_orphaned'] === true;
        });
    }
}
