<?php

/**
 * Utility Helper Class
 *
 * Provides shared utility methods across the plugin
 *
 * @package OrphanedACFMedia
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OrphanedACFMedia_Utils
{
    /**
     * Format file size for display
     *
     * @param int $size File size in bytes
     * @return string Formatted file size
     */
    public static function format_file_size($size)
    {
        if ($size >= 1073741824) {
            return number_format($size / 1073741824, 2) . ' GB';
        } elseif ($size >= 1048576) {
            return number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        } else {
            return $size . ' bytes';
        }
    }

    /**
     * Get human-readable file type from mime type
     *
     * @param string $mime_type
     * @return string
     */
    public static function get_file_type_label($mime_type)
    {
        $types = array(
            'image' => __('Image', 'orphaned-acf-media'),
            'video' => __('Video', 'orphaned-acf-media'),
            'audio' => __('Audio', 'orphaned-acf-media'),
            'application/pdf' => __('PDF', 'orphaned-acf-media'),
            'application/msword' => __('Word Document', 'orphaned-acf-media'),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => __('Word Document', 'orphaned-acf-media'),
            'application/vnd.ms-excel' => __('Excel Spreadsheet', 'orphaned-acf-media'),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => __('Excel Spreadsheet', 'orphaned-acf-media'),
            'application/vnd.ms-powerpoint' => __('PowerPoint Presentation', 'orphaned-acf-media'),
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => __('PowerPoint Presentation', 'orphaned-acf-media'),
            'text/plain' => __('Text File', 'orphaned-acf-media'),
            'application/zip' => __('ZIP Archive', 'orphaned-acf-media'),
        );

        // Check for specific mime types first
        if (isset($types[$mime_type])) {
            return $types[$mime_type];
        }

        // Check for general categories
        foreach ($types as $type => $label) {
            if (strpos($mime_type, $type) === 0) {
                return $label;
            }
        }

        return __('Unknown', 'orphaned-acf-media');
    }

    /**
     * Sanitize and validate attachment ID
     *
     * @param mixed $id
     * @return int|false
     */
    public static function validate_attachment_id($id)
    {
        $id = intval($id);

        if ($id <= 0) {
            return false;
        }

        // Check if attachment exists
        $attachment = get_post($id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }

        return $id;
    }

    /**
     * Check if user has required permissions
     *
     * @return bool
     */
    public static function user_can_manage_media()
    {
        return current_user_can('upload_files') && current_user_can('delete_posts');
    }

    /**
     * Log plugin errors
     *
     * @param string $message
     * @param array $context
     */
    public static function log_error($message, $context = array())
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = 'OrphanedACFMedia: ' . $message;

            if (!empty($context)) {
                $log_message .= ' Context: ' . wp_json_encode($context);
            }

            error_log($log_message);
        }
    }

    /**
     * Create cache key for attachment
     *
     * @param int $attachment_id
     * @param string $suffix
     * @return string
     */
    public static function get_cache_key($attachment_id, $suffix = '')
    {
        $key = 'orphaned_acf_' . $attachment_id;

        if ($suffix) {
            $key .= '_' . $suffix;
        }

        return $key;
    }

    /**
     * Clear all plugin cache
     */
    public static function clear_all_cache()
    {
        // Clear WordPress cache
        wp_cache_flush_group('orphaned_acf_media');

        // Clear transients
        global $wpdb;

        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
        ", '_transient_orphaned_acf_media_%'));

        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
        ", '_transient_timeout_orphaned_acf_media_%'));

        // Clear user-specific transients
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
        ", '_transient_orphaned_acf_%'));

        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
        ", '_transient_timeout_orphaned_acf_%'));
    }

    /**
     * Get supported file extensions
     *
     * @return array
     */
    public static function get_supported_extensions()
    {
        return array(
            'images' => array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'ico', 'svg'),
            'videos' => array('mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp'),
            'audio' => array('mp3', 'wav', 'ogg', 'wma', 'aac', 'flac', 'm4a'),
            'documents' => array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf'),
            'archives' => array('zip', 'rar', '7z', 'tar', 'gz'),
        );
    }

    /**
     * Get file extension from URL or path
     *
     * @param string $file
     * @return string
     */
    public static function get_file_extension($file)
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    /**
     * Check if file extension is supported
     *
     * @param string $extension
     * @return bool
     */
    public static function is_supported_extension($extension)
    {
        $supported = self::get_supported_extensions();

        foreach ($supported as $category => $extensions) {
            if (in_array(strtolower($extension), $extensions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get file category from extension
     *
     * @param string $extension
     * @return string
     */
    public static function get_file_category($extension)
    {
        $supported = self::get_supported_extensions();

        foreach ($supported as $category => $extensions) {
            if (in_array(strtolower($extension), $extensions)) {
                return $category;
            }
        }

        return 'other';
    }

    /**
     * Generate nonce for AJAX requests
     *
     * @return string
     */
    public static function get_ajax_nonce()
    {
        return wp_create_nonce('orphaned_acf_media_nonce');
    }

    /**
     * Verify nonce for AJAX requests
     *
     * @param string $nonce
     * @return bool
     */
    public static function verify_ajax_nonce($nonce)
    {
        return wp_verify_nonce($nonce, 'orphaned_acf_media_nonce');
    }

    /**
     * Get memory usage info
     *
     * @return array
     */
    public static function get_memory_info()
    {
        return array(
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        );
    }

    /**
     * Check if we're approaching memory limit
     *
     * @param float $threshold Percentage of memory limit (0.8 = 80%)
     * @return bool
     */
    public static function is_memory_limit_approaching($threshold = 0.8)
    {
        $current = memory_get_usage(true);
        $limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));

        if ($limit <= 0) {
            return false; // Unlimited memory
        }

        return ($current / $limit) >= $threshold;
    }

    /**
     * Convert bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    public static function bytes_to_human($bytes)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get attachment thumbnail URL safely
     *
     * @param int $attachment_id
     * @param string $size
     * @return string|false
     */
    public static function get_attachment_thumbnail($attachment_id, $size = 'thumbnail')
    {
        if (!$attachment_id) {
            return false;
        }

        $thumbnail = wp_get_attachment_image_url($attachment_id, $size);

        if (!$thumbnail) {
            // Try to get the full size if thumbnail doesn't exist
            $thumbnail = wp_get_attachment_image_url($attachment_id, 'full');
        }

        return $thumbnail;
    }

    /**
     * Safe JSON response for AJAX
     *
     * @param mixed $data
     * @param bool $success
     */
    public static function json_response($data, $success = true)
    {
        if ($success) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data);
        }
    }

    /**
     * Get plugin info
     *
     * @return array
     */
    public static function get_plugin_info()
    {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return get_plugin_data(ORPHANED_ACF_MEDIA_PLUGIN_DIR . 'orphaned-acf-media.php');
    }

    /**
     * Check if plugin debug mode is enabled
     *
     * @return bool
     */
    public static function is_debug_mode()
    {
        // Use defined() and constant() to avoid "undefined constant" notices.
        return defined('WP_DEBUG') && WP_DEBUG && (defined('ORPHANED_ACF_MEDIA_DEBUG') && constant('ORPHANED_ACF_MEDIA_DEBUG'));
    }
}
