<?php

/**
 * Plugin Name: Orphaned ACF Media
 * Plugin URI: https://plugins.citcom.support/orphaned-acf-media
 * Description: Find and delete media files that are not used in any ACF fields. Helps clean up unused attachments in your WordPress site.
 * Version: 1.2.1
 * Author: Gareth Hale, CitCom.
 * Author URI: https://citcom.co.uk
 * License: GPL2
 * Text Domain: orphaned-acf-media
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ORPHANED_ACF_MEDIA_VERSION', '1.2.1');
define('ORPHANED_ACF_MEDIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ORPHANED_ACF_MEDIA_PLUGIN_URL', plugin_dir_url(__FILE__));

class OrphanedACFMedia
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Check if ACF is active
        if (!function_exists('get_field')) {
            add_action('admin_notices', array($this, 'acf_missing_notice'));
            return;
        }

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX handlers
        add_action('wp_ajax_delete_orphaned_media', array($this, 'ajax_delete_orphaned_media'));
        add_action('wp_ajax_bulk_delete_orphaned_media', array($this, 'ajax_bulk_delete_orphaned_media'));
        add_action('wp_ajax_delete_all_safe_media', array($this, 'ajax_delete_all_safe_media'));
        add_action('wp_ajax_clear_orphaned_cache', array($this, 'ajax_clear_orphaned_cache'));
    }

    /**
     * Notice when ACF is missing
     */
    public function acf_missing_notice()
    {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Orphaned ACF Media:</strong> This plugin requires Advanced Custom Fields (ACF) to be installed and activated.';
        echo '</p></div>';
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu()
    {
        add_media_page(
            'Orphaned ACF Media',
            'Orphaned ACF Media',
            'manage_options',
            'orphaned-acf-media',
            array($this, 'admin_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        if ($hook !== 'media_page_orphaned-acf-media') {
            return;
        }

        wp_enqueue_style(
            'orphaned-acf-media-admin',
            ORPHANED_ACF_MEDIA_PLUGIN_URL . 'assets/admin.css',
            array(),
            ORPHANED_ACF_MEDIA_VERSION
        );

        wp_enqueue_script(
            'orphaned-acf-media-admin',
            ORPHANED_ACF_MEDIA_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            ORPHANED_ACF_MEDIA_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('orphaned-acf-media-admin', 'orphanedACFMedia', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('orphaned_acf_media_nonce'),
            'confirmDelete' => __('Are you sure you want to delete this media file? This action cannot be undone.', 'orphaned-acf-media'),
            'confirmBulkDelete' => __('Are you sure you want to delete all selected media files? This action cannot be undone.', 'orphaned-acf-media'),
            'deleting' => __('Deleting...', 'orphaned-acf-media'),
            'deleted' => __('Deleted', 'orphaned-acf-media'),
            'error' => __('Error occurred while deleting', 'orphaned-acf-media')
        ));
    }

    /**
     * Admin page display
     */
    public function admin_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="orphaned-acf-media-header">
                <p><?php _e('This tool helps you find and delete media files that are not used in any ACF fields. Files may still be used elsewhere on your website - check the Safety Status column for details.', 'orphaned-acf-media'); ?></p>
                <p class="safety-note"><strong><?php _e('Note:', 'orphaned-acf-media'); ?></strong> <?php _e('A final safety check is performed before each deletion. Files found to be in use will not be deleted.', 'orphaned-acf-media'); ?></p>

                <div class="safety-warning">
                    <h3><span class="dashicons dashicons-warning"></span> <?php _e('Safety Features', 'orphaned-acf-media'); ?></h3>
                    <p><?php _e('This plugin includes comprehensive safety checks to prevent accidental deletion of media files. It scans for usage in:', 'orphaned-acf-media'); ?></p>
                    <ul>
                        <li><?php _e('ACF Fields (all types including repeaters)', 'orphaned-acf-media'); ?></li>
                        <li><?php _e('Featured Images', 'orphaned-acf-media'); ?></li>
                        <li><?php _e('Post/Page Content', 'orphaned-acf-media'); ?></li>
                        <li><?php _e('Widgets & Sidebars', 'orphaned-acf-media'); ?></li>
                        <li><?php _e('Navigation Menus', 'orphaned-acf-media'); ?></li>
                        <li><?php _e('Theme Customizer Settings', 'orphaned-acf-media'); ?></li>
                        <li><?php _e('Site Icon & Custom Headers/Backgrounds', 'orphaned-acf-media'); ?></li>
                        <li><?php _e('Oxygen Builder 6 & Classic Content (Breakdance-based)', 'orphaned-acf-media'); ?></li>
                    </ul>
                </div>

                <div class="backup-warning">
                    <h3><span class="dashicons dashicons-warning"></span> <?php _e('Important Backup Warning', 'orphaned-acf-media'); ?></h3>
                    <p><?php _e('Although this plugin includes comprehensive safety checks and multiple layers of protection, we strongly recommend creating a complete backup of your website (including files and database) before performing any media deletion operations.', 'orphaned-acf-media'); ?></p>
                    <p><?php _e('While every precaution has been taken to safely identify and remove only truly orphaned media files, we cannot be held responsible for any unintended deletions. Always backup first!', 'orphaned-acf-media'); ?></p>
                </div>

                <div class="scan-buttons">
                    <button id="scan-orphaned-media" class="button button-primary">
                        <?php _e('Scan for Orphaned Media', 'orphaned-acf-media'); ?>
                    </button>
                    <button id="refresh-scan" class="button" title="<?php _e('Clear cache and perform fresh scan', 'orphaned-acf-media'); ?>">
                        <?php _e('Refresh', 'orphaned-acf-media'); ?>
                    </button>
                </div>
            </div>

            <div id="orphaned-media-results" style="display: none;">
                <div class="orphaned-media-controls">
                    <div class="bulk-actions-left">
                        <button id="select-all-orphaned" class="button">
                            <?php _e('Select All (This Page)', 'orphaned-acf-media'); ?>
                        </button>
                        <button id="bulk-delete-orphaned" class="button button-secondary" disabled>
                            <?php _e('Delete Selected', 'orphaned-acf-media'); ?>
                        </button>
                        <button id="delete-all-safe" class="button button-safe">
                            <?php _e('Delete All "Safe to Delete" Files', 'orphaned-acf-media'); ?>
                        </button>
                    </div>
                    <div class="orphaned-info">
                        <span class="orphaned-count"></span>
                        <span class="pagination-info"></span>
                    </div>
                </div>

                <div class="table-filters">
                    <div class="custom-filter-group">
                        <label for="file-type-filter"><?php _e('Filter by File Type:', 'orphaned-acf-media'); ?></label>
                        <select id="file-type-filter">
                            <option value="all"><?php _e('All File Types', 'orphaned-acf-media'); ?></option>
                            <option value="images"><?php _e('Images', 'orphaned-acf-media'); ?></option>
                            <option value="videos"><?php _e('Videos', 'orphaned-acf-media'); ?></option>
                            <option value="audio"><?php _e('Audio', 'orphaned-acf-media'); ?></option>
                            <option value="pdfs"><?php _e('PDFs', 'orphaned-acf-media'); ?></option>
                            <option value="documents"><?php _e('Documents', 'orphaned-acf-media'); ?></option>
                        </select>
                    </div>

                    <div class="custom-filter-group">
                        <label for="safety-status-filter"><?php _e('Filter by Safety Status:', 'orphaned-acf-media'); ?></label>
                        <select id="safety-status-filter">
                            <option value="all"><?php _e('All Status Types', 'orphaned-acf-media'); ?></option>
                            <option value="safe"><?php _e('Safe to Delete', 'orphaned-acf-media'); ?></option>
                            <option value="used-acf"><?php _e('Used in ACF Fields', 'orphaned-acf-media'); ?></option>
                            <option value="used-content"><?php _e('Used in Content', 'orphaned-acf-media'); ?></option>
                            <option value="used-both"><?php _e('Used in ACF & Content', 'orphaned-acf-media'); ?></option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button id="clear-filters" class="button"><?php _e('Clear', 'orphaned-acf-media'); ?></button>
                        <span class="filter-results-count"></span>
                    </div>
                </div>

                <div class="pagination-controls top-pagination" style="display: none;">
                    <div class="pagination-buttons">
                        <button id="first-page" class="button" disabled>&laquo; <?php _e('First', 'orphaned-acf-media'); ?></button>
                        <button id="prev-page" class="button" disabled>&lsaquo; <?php _e('Previous', 'orphaned-acf-media'); ?></button>
                        <span class="page-numbers">
                            <input type="number" id="current-page-input" value="1" min="1" class="small-text">
                            <span class="page-of"><?php _e('of', 'orphaned-acf-media'); ?> <span id="total-pages">1</span></span>
                        </span>
                        <button id="next-page" class="button" disabled><?php _e('Next', 'orphaned-acf-media'); ?> &rsaquo;</button>
                        <button id="last-page" class="button" disabled><?php _e('Last', 'orphaned-acf-media'); ?> &raquo;</button>
                    </div>
                    <div class="items-per-page">
                        <label for="items-per-page-select"><?php _e('Items per page:', 'orphaned-acf-media'); ?></label>
                        <select id="items-per-page-select">
                            <option value="25">25</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all" />
                            </th>
                            <th scope="col" class="manage-column"><?php _e('Thumbnail', 'orphaned-acf-media'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Filename', 'orphaned-acf-media'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('File Type', 'orphaned-acf-media'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Upload Date', 'orphaned-acf-media'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('File Size', 'orphaned-acf-media'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Safety Status', 'orphaned-acf-media'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Actions', 'orphaned-acf-media'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="orphaned-media-list">
                        <!-- Results will be populated here -->
                    </tbody>
                </table>

                <div class="pagination-controls bottom-pagination" style="display: none;">
                    <div class="pagination-buttons">
                        <button class="button first-page-btn" disabled>&laquo; <?php _e('First', 'orphaned-acf-media'); ?></button>
                        <button class="button prev-page-btn" disabled>&lsaquo; <?php _e('Previous', 'orphaned-acf-media'); ?></button>
                        <span class="page-numbers">
                            <span class="page-display"></span>
                        </span>
                        <button class="button next-page-btn" disabled><?php _e('Next', 'orphaned-acf-media'); ?> &rsaquo;</button>
                        <button class="button last-page-btn" disabled><?php _e('Last', 'orphaned-acf-media'); ?> &raquo;</button>
                    </div>
                </div>
            </div>

            <div id="loading-spinner" style="display: none;">
                <p id="scanning-status"><?php _e('Scanning media files...', 'orphaned-acf-media'); ?></p>
                <div class="scanning-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="scan-progress-fill"></div>
                    </div>
                    <div class="progress-text">
                        <span id="scan-progress-text">Initializing scan...</span>
                    </div>
                </div>
                <div class="spinner is-active"></div>
            </div>
        </div>
<?php
    }

    /**
     * Get orphaned media files with pagination support
     */
    public function get_orphaned_media($page = 1, $per_page = 50, $scan_all = false, $file_type_filter = 'all', $safety_status_filter = 'all')
    {
        $orphaned_media = array();
        $total_orphaned = 0;

        // Always use the comprehensive approach for reliable pagination
        // Cache orphaned IDs to improve performance on subsequent requests
        $cache_key = 'orphaned_acf_media_ids_' . md5(get_current_user_id());
        $cached_orphaned_ids = get_transient($cache_key);

        if ($cached_orphaned_ids === false || $scan_all) {
            // Full scan to get all orphaned media IDs
            $orphaned_ids = array();
            $batch_size = 100;
            $offset = 0;
            $total_attachments = wp_count_posts('attachment')->inherit;

            while ($offset < $total_attachments) {
                $args = array(
                    'post_type' => 'attachment',
                    'posts_per_page' => $batch_size,
                    'offset' => $offset,
                    'post_status' => 'inherit',
                    'fields' => 'ids',
                    'orderby' => 'date',
                    'order' => 'DESC',
                );

                $query = new WP_Query($args);

                if ($query->have_posts()) {
                    foreach ($query->posts as $attachment_id) {
                        // Only include files that are not used in ACF fields
                        // We'll let the detailed view show usage in other areas
                        if (!$this->is_attachment_used_in_acf_fields($attachment_id)) {
                            $orphaned_ids[] = $attachment_id;
                        }
                    }
                }

                wp_reset_postdata();
                $offset += $batch_size;

                // Break if we've processed all available attachments
                if ($query->post_count < $batch_size) {
                    break;
                }
            }

            // Cache the results for 10 minutes
            set_transient($cache_key, $orphaned_ids, 10 * MINUTE_IN_SECONDS);
            $cached_orphaned_ids = $orphaned_ids;
        }

        $total_orphaned = count($cached_orphaned_ids);

        // Get full attachment data for ALL orphaned media to apply filters
        $all_orphaned_media = array();
        foreach ($cached_orphaned_ids as $attachment_id) {
            $all_orphaned_media[] = $this->get_attachment_data($attachment_id);
        }

        // Apply filters
        $filtered_media = $this->apply_filters($all_orphaned_media, $file_type_filter, $safety_status_filter);
        $total_filtered = count($filtered_media);

        // Apply pagination to the filtered results
        $start_index = ($page - 1) * $per_page;
        $orphaned_media = array_slice($filtered_media, $start_index, $per_page);

        // Count total safe to delete files across all filtered results
        $total_safe_to_delete = count(array_filter($filtered_media, function ($media) {
            return $media['is_truly_orphaned'];
        }));

        return array(
            'media' => $orphaned_media,
            'pagination' => array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_filtered,
                'total_pages' => ceil($total_filtered / $per_page),
                'has_more' => ($page * $per_page) < $total_filtered,
                'total_safe_to_delete' => $total_safe_to_delete
            )
        );
    }

    /**
     * Apply filters to orphaned media array
     */
    private function apply_filters($media_array, $file_type_filter, $safety_status_filter)
    {
        if ($file_type_filter === 'all' && $safety_status_filter === 'all') {
            return $media_array;
        }

        $filtered_media = array();

        foreach ($media_array as $media) {
            $include_item = true;

            // Apply file type filter
            if ($file_type_filter !== 'all') {
                $mime_type = $media['mime_type'];
                $filename = $media['filename'];
                $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                switch ($file_type_filter) {
                    case 'images':
                        $include_item = strpos($mime_type, 'image/') === 0 ||
                            in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
                        break;
                    case 'videos':
                        $include_item = strpos($mime_type, 'video/') === 0 ||
                            in_array($file_extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv']);
                        break;
                    case 'audio':
                        $include_item = strpos($mime_type, 'audio/') === 0 ||
                            in_array($file_extension, ['mp3', 'wav', 'ogg', 'wma', 'flac', 'aac']);
                        break;
                    case 'pdfs':
                        $include_item = $mime_type === 'application/pdf' || $file_extension === 'pdf';
                        break;
                    case 'documents':
                        $include_item = in_array($file_extension, ['doc', 'docx', 'txt', 'rtf', 'odt', 'xls', 'xlsx', 'ppt', 'pptx']) ||
                            strpos($mime_type, 'application/') === 0;
                        break;
                    default:
                        $include_item = true;
                        break;
                }
            }

            // Apply safety status filter
            if ($include_item && $safety_status_filter !== 'all') {
                switch ($safety_status_filter) {
                    case 'safe':
                        $include_item = $media['is_truly_orphaned'];
                        break;
                    case 'used-acf':
                        $include_item = $media['is_used_in_acf'] && !$media['is_used_elsewhere'];
                        break;
                    case 'used-content':
                        $include_item = $media['is_used_elsewhere'] && !$media['is_used_in_acf'];
                        break;
                    case 'used-both':
                        $include_item = $media['is_used_in_acf'] && $media['is_used_elsewhere'];
                        break;
                    default:
                        $include_item = true;
                        break;
                }
            }

            if ($include_item) {
                $filtered_media[] = $media;
            }
        }

        return $filtered_media;
    }

    /**
     * Estimate total orphaned media count for pagination
     */
    private function estimate_total_orphaned_count()
    {
        // Cache the count for 5 minutes to avoid repeated expensive queries
        $cache_key = 'orphaned_acf_media_total_count';
        $cached_count = get_transient($cache_key);

        if ($cached_count !== false) {
            return (int) $cached_count;
        }

        // Sample approach: check a small sample and extrapolate
        $sample_size = 200;
        $args = array(
            'post_type' => 'attachment',
            'posts_per_page' => $sample_size,
            'post_status' => 'inherit',
            'fields' => 'ids',
        );

        $sample_query = new WP_Query($args);
        $total_attachments = $sample_query->found_posts;
        $orphaned_in_sample = 0;

        if ($sample_query->have_posts()) {
            foreach ($sample_query->posts as $attachment_id) {
                if (!$this->is_attachment_used_in_acf($attachment_id)) {
                    $orphaned_in_sample++;
                }
            }
        }

        wp_reset_postdata();

        // Extrapolate based on sample
        $orphaned_ratio = $sample_query->post_count > 0 ? $orphaned_in_sample / $sample_query->post_count : 0;
        $estimated_total = (int) ($total_attachments * $orphaned_ratio);

        // Cache the result
        set_transient($cache_key, $estimated_total, 5 * MINUTE_IN_SECONDS);

        return $estimated_total;
    }

    /**
     * Clear the orphaned media cache
     */
    private function clear_orphaned_cache()
    {
        $cache_key = 'orphaned_acf_media_ids_' . md5(get_current_user_id());
        delete_transient($cache_key);
        delete_transient('orphaned_acf_media_total_count');
    }

    /**
     * Get all safe to delete media files (for bulk delete all safe)
     */
    public function get_all_safe_to_delete_media()
    {
        $safe_media = array();
        $batch_size = 50; // Process in smaller batches
        $offset = 0;

        do {
            $args = array(
                'post_type' => 'attachment',
                'posts_per_page' => $batch_size,
                'offset' => $offset,
                'post_status' => 'inherit',
                'fields' => 'ids',
            );

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                foreach ($query->posts as $attachment_id) {
                    if (!$this->is_attachment_used_in_acf($attachment_id)) {
                        $attachment_data = $this->get_attachment_data($attachment_id);
                        if ($attachment_data['is_truly_orphaned']) {
                            $safe_media[] = $attachment_id;
                        }
                    }
                }
            }

            $offset += $batch_size;
        } while ($query->have_posts() && $offset < 1000); // Limit to 1000 for safety

        wp_reset_postdata();
        return $safe_media;
    }

    /**
     * Check if attachment is used anywhere on the website (comprehensive check)
     */
    private function is_attachment_used_in_acf($attachment_id)
    {
        // First check ACF usage
        if ($this->is_attachment_used_in_acf_fields($attachment_id)) {
            return true;
        }

        // Then check other potential usage areas for maximum safety
        return $this->is_attachment_used_elsewhere($attachment_id);
    }

    /**
     * Check if attachment is used in any ACF field
     */
    private function is_attachment_used_in_acf_fields($attachment_id)
    {
        global $wpdb;

        // Search in post meta for ACF fields
        $meta_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_value = %s
            AND meta_key NOT LIKE '_%%'
        ", $attachment_id);

        $count = $wpdb->get_var($meta_query);

        if ($count > 0) {
            return true;
        }

        // Also check in serialized data (for ACF repeater fields, etc.)
        $serialized_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_value LIKE %s
            AND meta_key NOT LIKE '_%%'
        ", '%"' . $attachment_id . '"%');

        $serialized_count = $wpdb->get_var($serialized_query);

        if ($serialized_count > 0) {
            return true;
        }

        // Check in ACF options (for options pages)
        $options_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->options}
            WHERE option_name LIKE 'options_%%'
            AND (option_value = %s OR option_value LIKE %s)
        ", $attachment_id, '%"' . $attachment_id . '"%');

        $options_count = $wpdb->get_var($options_query);

        return $options_count > 0;
    }

    /**
     * Comprehensive check if attachment is used elsewhere on the website
     */
    private function is_attachment_used_elsewhere($attachment_id)
    {
        global $wpdb;
        $file_url = wp_get_attachment_url($attachment_id);
        $file_path = parse_url($file_url, PHP_URL_PATH);
        $filename = basename($file_path);

        // 1. Check post content for direct image references
        $content_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_content LIKE %s
            OR post_content LIKE %s
            OR post_content LIKE %s
            AND post_status IN ('publish', 'private', 'draft', 'future', 'pending')
        ", '%' . $file_url . '%', '%' . $file_path . '%', '%' . $filename . '%');

        if ($wpdb->get_var($content_query) > 0) {
            return true;
        }

        // 2. Check if it's a featured image
        $featured_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_thumbnail_id'
            AND meta_value = %s
        ", $attachment_id);

        if ($wpdb->get_var($featured_query) > 0) {
            return true;
        }

        // 3. Check widgets
        $widget_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->options}
            WHERE option_name LIKE 'widget_%%'
            AND (option_value LIKE %s OR option_value LIKE %s OR option_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($widget_query) > 0) {
            return true;
        }

        // 4. Check theme customizer settings
        $customizer_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->options}
            WHERE option_name LIKE 'theme_mods_%%'
            AND (option_value LIKE %s OR option_value LIKE %s OR option_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($customizer_query) > 0) {
            return true;
        }

        // 5. Check navigation menus
        $menu_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'nav_menu_item'
            AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($menu_query) > 0) {
            return true;
        }

        // 6. Check if attachment is parent to other media (gallery relationships)
        $parent_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_parent = %s
            AND post_type = 'attachment'
        ", $attachment_id);

        if ($wpdb->get_var($parent_query) > 0) {
            return true;
        }

        // 7. Check site icon/logo
        $site_icon = get_option('site_icon');
        if ($site_icon && $site_icon == $attachment_id) {
            return true;
        }

        // 8. Check custom header/background
        $custom_header = get_option('header_image');
        $custom_background = get_option('background_image');

        if (($custom_header && strpos($custom_header, $filename) !== false) ||
            ($custom_background && strpos($custom_background, $filename) !== false)
        ) {
            return true;
        }

        // 9. Check all other post meta (catch-all for any other references)
        $all_meta_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE (meta_value = %s OR meta_value LIKE %s OR meta_value LIKE %s)
            AND meta_key NOT LIKE '_%%'
        ", $attachment_id, '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($all_meta_query) > 0) {
            return true;
        }

        // 10. Check user meta
        $user_meta_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->usermeta}
            WHERE meta_value = %s
            OR meta_value LIKE %s
            OR meta_value LIKE %s
        ", $attachment_id, '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($user_meta_query) > 0) {
            return true;
        }

        // 11. Check Oxygen Builder content (v6 Breakdance-based + Classic)
        if ($this->check_usage_in_oxygen_builder($attachment_id, $file_url, $filename)) {
            return true;
        }

        return false;
    }

    /**
     * Get detailed usage information for an attachment
     */
    private function get_attachment_usage_details($attachment_id)
    {
        global $wpdb;
        $usage_details = array();
        $file_url = wp_get_attachment_url($attachment_id);
        $file_path = parse_url($file_url, PHP_URL_PATH);
        $filename = basename($file_path);

        // Check various usage locations
        $checks = array(
            'acf_fields' => 'ACF Fields',
            'featured_images' => 'Featured Images',
            'post_content' => 'Post/Page Content',
            'widgets' => 'Widgets',
            'menus' => 'Navigation Menus',
            'customizer' => 'Theme Customizer',
            'site_settings' => 'Site Settings',
            'oxygen_builder' => 'Oxygen Builder'
        );

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

    // Individual check methods for detailed usage reporting
    private function check_usage_in_acf_fields($attachment_id, $file_url, $filename)
    {
        return $this->is_attachment_used_in_acf_fields($attachment_id);
    }

    private function check_usage_in_featured_images($attachment_id, $file_url, $filename)
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %s", $attachment_id);
        return $wpdb->get_var($query) > 0;
    }

    private function check_usage_in_post_content($attachment_id, $file_url, $filename)
    {
        global $wpdb;
        $file_path = parse_url($file_url, PHP_URL_PATH);
        $query = $wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE (post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s)
            AND post_status IN ('publish', 'private', 'draft', 'future', 'pending')
        ", '%' . $file_url . '%', '%' . $file_path . '%', '%' . $filename . '%');
        return $wpdb->get_var($query) > 0;
    }

    private function check_usage_in_widgets($attachment_id, $file_url, $filename)
    {
        global $wpdb;
        $query = $wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->options}
            WHERE option_name LIKE 'widget_%%'
            AND (option_value LIKE %s OR option_value LIKE %s OR option_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');
        return $wpdb->get_var($query) > 0;
    }

    private function check_usage_in_menus($attachment_id, $file_url, $filename)
    {
        global $wpdb;
        $query = $wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'nav_menu_item'
            AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');
        return $wpdb->get_var($query) > 0;
    }

    private function check_usage_in_customizer($attachment_id, $file_url, $filename)
    {
        global $wpdb;
        $query = $wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->options}
            WHERE option_name LIKE 'theme_mods_%%'
            AND (option_value LIKE %s OR option_value LIKE %s OR option_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');
        return $wpdb->get_var($query) > 0;
    }

    private function check_usage_in_site_settings($attachment_id, $file_url, $filename)
    {
        $site_icon = get_option('site_icon');
        $custom_header = get_option('header_image');
        $custom_background = get_option('background_image');

        return ($site_icon && $site_icon == $attachment_id) ||
            ($custom_header && strpos($custom_header, $filename) !== false) ||
            ($custom_background && strpos($custom_background, $filename) !== false);
    }

    private function check_usage_in_oxygen_builder($attachment_id, $file_url, $filename)
    {
        global $wpdb;

        // Check if Oxygen Builder is active (classic or v6)
        $oxygen_classic_active = defined('CT_VERSION') || is_plugin_active('oxygen/functions.php');
        $oxygen_v6_active = is_plugin_active('oxygen/functions.php') || class_exists('Breakdance\\Lib\\PluginAPI');

        if (!$oxygen_classic_active && !$oxygen_v6_active) {
            return false;
        }

        // === OXYGEN BUILDER 6 (BREAKDANCE-BASED) CHECKS ===

        // 1. Check _breakdance_data meta (Oxygen 6 / Breakdance main storage)
        $breakdance_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_breakdance_data'
            AND (meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($breakdance_query) > 0) {
            return true;
        }

        // 2. Check breakdance_data meta (alternative storage)
        $breakdance_alt_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'breakdance_data'
            AND (meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($breakdance_alt_query) > 0) {
            return true;
        }

        // 3. Check _breakdance_tree_json meta (JSON tree structure)
        $breakdance_tree_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_breakdance_tree_json'
            AND (meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($breakdance_tree_query) > 0) {
            return true;
        }

        // 4. Check _breakdance_css meta (compiled CSS)
        $breakdance_css_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_breakdance_css'
            AND (meta_value LIKE %s OR meta_value LIKE %s)
        ", '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($breakdance_css_query) > 0) {
            return true;
        }

        // 5. Check breakdance templates and global blocks
        $breakdance_templates_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('breakdance_template', 'breakdance_block', 'breakdance_header', 'breakdance_footer')
            AND pm.meta_key IN ('_breakdance_data', 'breakdance_data', '_breakdance_tree_json')
            AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($breakdance_templates_query) > 0) {
            return true;
        }

        // === CLASSIC OXYGEN BUILDER CHECKS (LEGACY SUPPORT) ===

        // 6. Check ct_builder_shortcodes meta (classic Oxygen content storage)
        $oxygen_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'ct_builder_shortcodes'
            AND (meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($oxygen_query) > 0) {
            return true;
        }

        // 7. Check ct_builder_json meta (classic alternative storage format)
        $oxygen_json_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'ct_builder_json'
            AND (meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($oxygen_json_query) > 0) {
            return true;
        }

        // 8. Check classic Oxygen templates (ct_template post type)
        $oxygen_templates_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'ct_template'
            AND pm.meta_key IN ('ct_builder_shortcodes', 'ct_builder_json')
            AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($oxygen_templates_query) > 0) {
            return true;
        }

        // 9. Check classic Oxygen reusable parts (oxy_user_library post type)
        $oxygen_parts_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'oxy_user_library'
            AND pm.meta_key IN ('ct_builder_shortcodes', 'ct_builder_json')
            AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($oxygen_parts_query) > 0) {
            return true;
        }

        // === SHARED CHECKS FOR BOTH VERSIONS ===

        // 10. Check Oxygen/Breakdance stylesheets and compiled CSS
        $css_cache_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->options}
            WHERE (option_name LIKE 'oxygen_vsb_css_cache_%%' OR option_name LIKE 'breakdance_css_cache_%%' OR option_name LIKE '_breakdance_css_%%')
            AND (option_value LIKE %s OR option_value LIKE %s)
        ", '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($css_cache_query) > 0) {
            return true;
        }

        // 11. Check custom CSS and JS (both classic and v6)
        $custom_styles_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key IN ('ct_builder_css', 'ct_builder_js', 'ct_custom_css', 'ct_custom_js', '_breakdance_css', '_breakdance_js', 'breakdance_custom_css', 'breakdance_custom_js')
            AND (meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($custom_styles_query) > 0) {
            return true;
        }

        // 12. Check global settings for both versions
        $global_settings_query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->options}
            WHERE (option_name LIKE 'oxygen_vsb_%%' OR option_name LIKE 'breakdance_%%' OR option_name LIKE '_breakdance_%%')
            AND (option_value LIKE %s OR option_value LIKE %s OR option_value LIKE %s)
        ", '%' . $attachment_id . '%', '%' . $file_url . '%', '%' . $filename . '%');

        if ($wpdb->get_var($global_settings_query) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get attachment data for display
     */
    private function get_attachment_data($attachment_id)
    {
        $attachment = get_post($attachment_id);
        $file_path = get_attached_file($attachment_id);
        $file_url = wp_get_attachment_url($attachment_id);
        $file_size = file_exists($file_path) ? size_format(filesize($file_path)) : 'Unknown';
        $mime_type = get_post_mime_type($attachment_id);

        // Get usage details for safety information
        $usage_details = $this->get_attachment_usage_details($attachment_id);

        // Comprehensive check to determine if truly safe to delete
        $is_used_in_acf = $this->is_attachment_used_in_acf_fields($attachment_id);
        $is_used_elsewhere = $this->is_attachment_used_elsewhere($attachment_id);
        $is_truly_safe = !$is_used_in_acf && !$is_used_elsewhere;

        return array(
            'id' => $attachment_id,
            'title' => $attachment->post_title,
            'filename' => basename($file_path),
            'url' => $file_url,
            'thumbnail' => wp_get_attachment_image($attachment_id, array(80, 80)),
            'upload_date' => get_the_date('Y-m-d H:i', $attachment_id),
            'file_size' => $file_size,
            'mime_type' => $mime_type,
            'usage_details' => $usage_details,
            'is_truly_orphaned' => $is_truly_safe,
            'is_used_in_acf' => $is_used_in_acf,
            'is_used_elsewhere' => $is_used_elsewhere
        );
    }

    /**
     * AJAX handler for deleting single orphaned media
     */
    public function ajax_delete_orphaned_media()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'orphaned_acf_media_nonce')) {
            wp_die('Security check failed');
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $attachment_id = intval($_POST['attachment_id']);

        // Final safety check before deletion
        if ($this->is_attachment_used_elsewhere($attachment_id)) {
            wp_send_json_error(array(
                'message' => __('Safety Check Failed: This media file is being used elsewhere on your website and cannot be safely deleted.', 'orphaned-acf-media'),
                'details' => $this->get_attachment_usage_details($attachment_id)
            ));
            return;
        }

        if ($attachment_id && wp_delete_attachment($attachment_id, true)) {
            // Clear cache after successful deletion
            $this->clear_orphaned_cache();
            wp_send_json_success(array(
                'message' => __('Media file deleted successfully.', 'orphaned-acf-media')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete media file.', 'orphaned-acf-media')
            ));
        }
    }

    /**
     * AJAX handler for bulk deleting orphaned media
     */
    public function ajax_bulk_delete_orphaned_media()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'orphaned_acf_media_nonce')) {
            wp_die('Security check failed');
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $attachment_ids = array_map('intval', $_POST['attachment_ids']);
        $deleted_count = 0;
        $failed_count = 0;
        $safety_blocked = 0;
        $blocked_files = array();

        foreach ($attachment_ids as $attachment_id) {
            if (!$attachment_id) {
                $failed_count++;
                continue;
            }

            // Final safety check before deletion
            if ($this->is_attachment_used_elsewhere($attachment_id)) {
                $safety_blocked++;
                $attachment_data = $this->get_attachment_data($attachment_id);
                $blocked_files[] = array(
                    'id' => $attachment_id,
                    'filename' => $attachment_data['filename'],
                    'usage' => $attachment_data['usage_details']
                );
                continue;
            }

            if (wp_delete_attachment($attachment_id, true)) {
                $deleted_count++;
            } else {
                $failed_count++;
            }
        }

        $message = '';
        if ($deleted_count > 0) {
            $message .= sprintf(__('Successfully deleted %d media files. ', 'orphaned-acf-media'), $deleted_count);
        }
        if ($safety_blocked > 0) {
            $message .= sprintf(__('%d files were blocked by safety checks (still in use). ', 'orphaned-acf-media'), $safety_blocked);
        }
        if ($failed_count > 0) {
            $message .= sprintf(__('%d files failed to delete due to errors.', 'orphaned-acf-media'), $failed_count);
        }

        // Clear cache if any files were deleted
        if ($deleted_count > 0) {
            $this->clear_orphaned_cache();
        }

        wp_send_json_success(array(
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count,
            'safety_blocked' => $safety_blocked,
            'blocked_files' => $blocked_files,
            'message' => trim($message)
        ));
    }

    /**
     * AJAX handler for deleting all safe media files
     */
    public function ajax_delete_all_safe_media()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'orphaned_acf_media_nonce')) {
            wp_die('Security check failed');
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Get batch size from request or use default
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 10;
        $batch_offset = isset($_POST['batch_offset']) ? intval($_POST['batch_offset']) : 0;

        // Get cached orphaned media IDs (same as main scan uses)
        $cache_key = 'orphaned_acf_media_ids_' . md5(get_current_user_id());
        $cached_orphaned_ids = get_transient($cache_key);

        if ($cached_orphaned_ids === false) {
            wp_send_json_error(array(
                'message' => __('No cached orphaned media found. Please run a scan first.', 'orphaned-acf-media')
            ));
            return;
        }

        // Get full attachment data for all orphaned media to find safe ones
        $all_safe_ids = array();
        foreach ($cached_orphaned_ids as $attachment_id) {
            $attachment_data = $this->get_attachment_data($attachment_id);
            if ($attachment_data['is_truly_orphaned']) {
                $all_safe_ids[] = $attachment_id;
            }
        }

        // Apply batch processing to safe IDs
        $total_safe = count($all_safe_ids);
        $batch_ids = array_slice($all_safe_ids, $batch_offset, $batch_size);

        $processed_count = count($batch_ids);
        $deleted_count = 0;
        $failed_count = 0;

        foreach ($batch_ids as $attachment_id) {
            // Final safety check before deletion
            $attachment_data = $this->get_attachment_data($attachment_id);
            if ($attachment_data['is_truly_orphaned']) {
                if (wp_delete_attachment($attachment_id, true)) {
                    $deleted_count++;
                } else {
                    $failed_count++;
                }
            } else {
                $failed_count++;
            }
        }

        // Check if there are more files to process
        $has_more = ($batch_offset + $batch_size) < $total_safe;

        // Clear cache if any files were deleted
        if ($deleted_count > 0) {
            $this->clear_orphaned_cache();
        }

        wp_send_json_success(array(
            'batch_processed' => $processed_count,
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count,
            'has_more' => $has_more,
            'next_offset' => $batch_offset + $batch_size,
            'total_found' => $total_safe,
            'progress_percent' => $total_safe > 0 ? (($batch_offset + $processed_count) / $total_safe) * 100 : 100,
            'message' => sprintf(
                __('Processed %d files. Deleted: %d, Failed: %d', 'orphaned-acf-media'),
                $processed_count,
                $deleted_count,
                $failed_count
            )
        ));
    }

    /**
     * AJAX handler for clearing orphaned media cache
     */
    public function ajax_clear_orphaned_cache()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'orphaned_acf_media_nonce')) {
            wp_die('Security check failed');
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $this->clear_orphaned_cache();

        wp_send_json_success(array(
            'message' => __('Cache cleared successfully. Performing fresh scan...', 'orphaned-acf-media')
        ));
    }
}

// Initialize the plugin
new OrphanedACFMedia();

// AJAX handler for scanning (accessible to logged-in users)
add_action('wp_ajax_scan_orphaned_media', 'handle_scan_orphaned_media');

function handle_scan_orphaned_media()
{
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'orphaned_acf_media_nonce')) {
        wp_die('Security check failed');
    }

    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 50;
    $scan_all = isset($_POST['scan_all']) ? (bool) $_POST['scan_all'] : false;

    // Filter parameters
    $file_type_filter = isset($_POST['file_type_filter']) ? sanitize_text_field($_POST['file_type_filter']) : 'all';
    $safety_status_filter = isset($_POST['safety_status_filter']) ? sanitize_text_field($_POST['safety_status_filter']) : 'all';

    $plugin = new OrphanedACFMedia();
    $result = $plugin->get_orphaned_media($page, $per_page, $scan_all, $file_type_filter, $safety_status_filter);

    wp_send_json_success($result);
}
