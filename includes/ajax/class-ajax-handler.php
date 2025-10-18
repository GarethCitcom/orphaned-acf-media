<?php

/**
 * AJAX Handler Class
 *
 * Handles all AJAX requests for the plugin
 *
 * @package OrphanedACFMedia
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OrphanedACFMedia_AJAX
{

    private $media_scanner;

    public function __construct()
    {
        // Initialize media scanner
        $this->media_scanner = new OrphanedACFMedia_MediaScanner();
    }

    /**
     * Register AJAX hooks
     */
    public function register_hooks()
    {
        add_action('wp_ajax_scan_orphaned_media', array($this, 'handle_scan_orphaned_media'));
        add_action('wp_ajax_delete_orphaned_media', array($this, 'handle_delete_orphaned_media'));
        add_action('wp_ajax_bulk_delete_orphaned_media', array($this, 'handle_delete_orphaned_media')); // Alias for bulk delete
        add_action('wp_ajax_delete_all_safe_orphaned_media', array($this, 'handle_delete_all_safe_orphaned_media'));
        add_action('wp_ajax_clear_orphaned_cache', array($this, 'handle_clear_orphaned_cache'));
    }

    /**
     * Handle scan orphaned media AJAX request
     */
    public function handle_scan_orphaned_media()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'orphaned_acf_media_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Sanitize input parameters
        $page = isset($_POST['page']) ? intval(wp_unslash($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? intval(wp_unslash($_POST['per_page'])) : 50;
        $scan_all = isset($_POST['scan_all']) ? sanitize_text_field(wp_unslash($_POST['scan_all'])) === 'true' : false;
        $file_type_filter = isset($_POST['file_type_filter']) ? sanitize_text_field(wp_unslash($_POST['file_type_filter'])) : 'all';
        $safety_status_filter = isset($_POST['safety_status_filter']) ? sanitize_text_field(wp_unslash($_POST['safety_status_filter'])) : 'all';

        try {
            $result = $this->media_scanner->get_orphaned_media($page, $per_page, $scan_all, $file_type_filter, $safety_status_filter);

            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'An error occurred while scanning for orphaned media.'));
        }
    }

    /**
     * Handle delete orphaned media AJAX request
     */
    public function handle_delete_orphaned_media()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'orphaned_acf_media_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Get and validate attachment IDs
        $attachment_ids = isset($_POST['attachment_ids']) ? array_map('intval', wp_unslash($_POST['attachment_ids'])) : array();

        if (!is_array($attachment_ids) || empty($attachment_ids)) {
            wp_send_json_error(array('message' => 'No attachment IDs provided.'));
        }

        $deleted_count = 0;
        $failed_deletions = array();
        $results = array();

        foreach ($attachment_ids as $attachment_id) {
            $attachment_id = intval($attachment_id);

            if ($attachment_id <= 0) {
                continue;
            }

            // Final safety check before deletion
            if (
                !$this->media_scanner->is_attachment_used_in_acf($attachment_id) &&
                !$this->media_scanner->is_attachment_used_elsewhere($attachment_id)
            ) {

                $deleted = wp_delete_attachment($attachment_id, true);

                if ($deleted) {
                    $deleted_count++;
                    $results[] = array(
                        'id' => $attachment_id,
                        'status' => 'deleted',
                        'message' => 'Successfully deleted'
                    );
                } else {
                    $failed_deletions[] = $attachment_id;
                    $results[] = array(
                        'id' => $attachment_id,
                        'status' => 'failed',
                        'message' => 'Failed to delete attachment'
                    );
                }
            } else {
                $failed_deletions[] = $attachment_id;
                $results[] = array(
                    'id' => $attachment_id,
                    'status' => 'skipped',
                    'message' => 'File is in use - skipped for safety'
                );
            }
        }

        // Clear cache after deletions
        $this->clear_orphaned_cache();

        $response = array(
            'deleted_count' => $deleted_count,
            'failed_count' => count($failed_deletions),
            'results' => $results,
            'message' => sprintf(
                'Deleted %d file(s). %d file(s) could not be deleted.',
                $deleted_count,
                count($failed_deletions)
            )
        );

        wp_send_json_success($response);
    }

    /**
     * Handle delete all safe orphaned media AJAX request
     */
    public function handle_delete_all_safe_orphaned_media()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'orphaned_acf_media_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        try {
            // Get batch parameters
            $batch_size = isset($_POST['batch_size']) ? intval(wp_unslash($_POST['batch_size'])) : 10;
            $batch_offset = isset($_POST['batch_offset']) ? intval(wp_unslash($_POST['batch_offset'])) : 0;

            // Get all safe to delete media
            $safe_media = $this->media_scanner->get_all_safe_to_delete_media();

            if (empty($safe_media)) {
                wp_send_json_error(array('message' => 'No safe files found to delete. Please perform a scan first.'));
            }

            $total_files = count($safe_media);

            // Get the batch slice
            $batch_media = array_slice($safe_media, $batch_offset, $batch_size);

            if (empty($batch_media)) {
                // No more files to process
                wp_send_json_success(array(
                    'deleted_count' => 0,
                    'failed_count' => 0,
                    'total_files' => $total_files,
                    'has_more' => false,
                    'progress_percent' => 100,
                    'message' => 'Batch processing complete'
                ));
            }

            $deleted_count = 0;
            $failed_deletions = array();

            foreach ($batch_media as $media) {
                $attachment_id = $media['id'];

                // Final safety check
                if (isset($media['is_truly_orphaned']) && $media['is_truly_orphaned']) {
                    $deleted = wp_delete_attachment($attachment_id, true);

                    if ($deleted) {
                        $deleted_count++;
                    } else {
                        $failed_deletions[] = $attachment_id;
                    }
                } else {
                    $failed_deletions[] = $attachment_id;
                }
            }

            // Calculate progress
            $processed = $batch_offset + count($batch_media);
            $progress_percent = round(($processed / $total_files) * 100, 2);
            $has_more = $processed < $total_files;
            $next_offset = $has_more ? $batch_offset + $batch_size : 0;

            // Clear cache after each batch
            $this->clear_orphaned_cache();

            $response = array(
                'deleted_count' => $deleted_count,
                'failed_count' => count($failed_deletions),
                'total_files' => $total_files,
                'processed' => $processed,
                'has_more' => $has_more,
                'next_offset' => $next_offset,
                'progress_percent' => $progress_percent,
                'message' => sprintf(
                    'Processed batch: %d deleted, %d failed out of %d files in this batch',
                    $deleted_count,
                    count($failed_deletions),
                    count($batch_media)
                )
            );

            wp_send_json_success($response);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'An error occurred while deleting safe files: ' . $e->getMessage()));
        }
    }

    /**
     * Handle clear cache AJAX request
     */
    public function handle_clear_orphaned_cache()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'orphaned_acf_media_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        try {
            $this->clear_orphaned_cache();
            wp_send_json_success(array(
                'message' => 'Cache cleared successfully'
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to clear cache'));
        }
    }

    /**
     * Clear orphaned media cache
     */
    private function clear_orphaned_cache()
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
}
