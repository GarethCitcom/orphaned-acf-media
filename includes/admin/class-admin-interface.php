<?php

/**
 * Admin Interface Class
 *
 * Handles admin page rendering and UI components
 *
 * @package OrphanedACFMedia
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OrphanedACFMedia_Admin
{

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_media_page(
            __('Orphaned ACF Media', 'orphaned-acf-media'),
            __('Orphaned ACF Media', 'orphaned-acf-media'),
            'manage_options',
            'orphaned-acf-media',
            array($this, 'admin_page')
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on our admin page
        if ($hook !== 'media_page_orphaned-acf-media') {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'orphaned-acf-media-admin',
            ORPHANED_ACF_MEDIA_PLUGIN_URL . 'assets/admin.css',
            array(),
            ORPHANED_ACF_MEDIA_VERSION
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'orphaned-acf-media-admin',
            ORPHANED_ACF_MEDIA_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            ORPHANED_ACF_MEDIA_VERSION,
            true
        );

        // Localize script
        wp_localize_script('orphaned-acf-media-admin', 'orphanedACFMedia', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('orphaned_acf_media_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this media file? This action cannot be undone.', 'orphaned-acf-media'),
                'confirmBulkDelete' => __('Are you sure you want to delete the selected media files? This action cannot be undone.', 'orphaned-acf-media'),
                'confirmDeleteAllSafe' => __('Are you sure you want to delete ALL safe-to-delete files? This action cannot be undone.', 'orphaned-acf-media'),
                'noFilesSelected' => __('Please select at least one file to delete.', 'orphaned-acf-media'),
                'scanRequired' => __('Please perform a scan first to identify safe files.', 'orphaned-acf-media')
            )
        ));
    }

    /**
     * Render admin page
     */
    public function admin_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="orphaned-acf-media-header">
                <?php $this->render_safety_warning(); ?>
                <?php $this->render_backup_warning(); ?>
                <?php $this->render_backup_consent(); ?>
                <?php $this->render_scan_buttons(); ?>
            </div>

            <?php $this->render_results_section(); ?>
            <?php $this->render_loading_spinner(); ?>
        </div>
    <?php
    }

    /**
     * Render safety warning section
     */
    private function render_safety_warning()
    {
    ?>
        <div class="safety-warning">
            <h3><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Safety Features', 'orphaned-acf-media'); ?></h3>
            <p><?php esc_html_e('This plugin includes comprehensive safety checks across multiple areas of your WordPress site:', 'orphaned-acf-media'); ?></p>
            <ul>
                <li><?php esc_html_e('Advanced Custom Fields (ACF) - All field types including repeaters and flexible content', 'orphaned-acf-media'); ?></li>
                <li><?php esc_html_e('Featured Images & Post Content (Gutenberg blocks, classic editor, shortcodes)', 'orphaned-acf-media'); ?></li>
                <li><?php esc_html_e('Widgets & Navigation Menus', 'orphaned-acf-media'); ?></li>
                <li><?php esc_html_e('Theme Customizer Settings', 'orphaned-acf-media'); ?></li>
                <li><?php esc_html_e('Site Icon & Custom Headers/Backgrounds', 'orphaned-acf-media'); ?></li>
                <li><?php esc_html_e('Oxygen Builder 6 & Classic Content (_oxygen_data)', 'orphaned-acf-media'); ?></li>
                <li><?php esc_html_e('WooCommerce Integration - Product galleries, featured images, categories, settings, and content', 'orphaned-acf-media'); ?></li>
            </ul>
        </div>
    <?php
    }

    /**
     * Render backup warning section
     */
    private function render_backup_warning()
    {
    ?>
        <div class="backup-warning">
            <h3><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Important Backup Warning', 'orphaned-acf-media'); ?></h3>
            <p><?php esc_html_e('Although this plugin includes comprehensive safety checks and multiple layers of protection, we strongly recommend creating a complete backup of your website (including files and database) before performing any media deletion operations.', 'orphaned-acf-media'); ?></p>
        </div>
    <?php
    }

    /**
     * Render backup consent section
     */
    private function render_backup_consent()
    {
    ?>
        <div class="backup-consent">
            <p class="danger-note"><?php esc_html_e('While every precaution has been taken to safely identify and remove only truly orphaned media files, we cannot be held responsible for any unintended deletions. Always backup first!', 'orphaned-acf-media'); ?></p>
            <label class="backup-consent-label">
                <input type="checkbox" id="backup-consent-checkbox" class="backup-consent-checkbox" />
                <?php esc_html_e('I confirm that I have created a complete backup of my website (including files and database) and understand that media deletion operations cannot be undone.', 'orphaned-acf-media'); ?>
            </label>
        </div>
    <?php
    }

    /**
     * Render scan buttons section
     */
    private function render_scan_buttons()
    {
    ?>
        <div class="scan-buttons">
            <button id="scan-orphaned-media" class="button button-primary" disabled title="<?php esc_attr_e('Please confirm backup before scanning', 'orphaned-acf-media'); ?>">
                <?php esc_html_e('Scan for Orphaned Media', 'orphaned-acf-media'); ?>
            </button>
            <button id="refresh-scan" class="button" disabled title="<?php esc_attr_e('Please confirm backup before scanning', 'orphaned-acf-media'); ?>">
                <?php esc_html_e('Refresh', 'orphaned-acf-media'); ?>
            </button>
        </div>
    <?php
    }

    /**
     * Render results section
     */
    private function render_results_section()
    {
    ?>
        <div id="orphaned-media-results" style="display: none;">
            <!-- Small loading spinner for filters/pagination -->
            <div id="quick-loading-spinner" style="display: none;">
                <div class="quick-spinner">
                    <span class="dashicons dashicons-update-alt spinner-icon"></span>
                    <span class="loading-text"><?php esc_html_e('Loading...', 'orphaned-acf-media'); ?></span>
                </div>
            </div>

            <?php $this->render_media_controls(); ?>
            <?php $this->render_table_filters(); ?>
            <?php $this->render_media_table(); ?>
            <?php $this->render_pagination_controls(); ?>
        </div>
    <?php
    }

    /**
     * Render media controls section
     */
    private function render_media_controls()
    {
    ?>
        <div class="orphaned-media-controls">
            <div class="bulk-actions-left">
                <button id="select-all-orphaned" class="button">
                    <?php esc_html_e('Select All (This Page)', 'orphaned-acf-media'); ?>
                </button>
                <button id="bulk-delete-orphaned" class="button button-secondary" disabled>
                    <?php esc_html_e('Delete Selected', 'orphaned-acf-media'); ?>
                </button>
                <button id="delete-all-safe" class="button button-safe">
                    <?php esc_html_e('Delete All "Safe to Delete" Files', 'orphaned-acf-media'); ?>
                </button>
            </div>
            <div class="orphaned-info">
                <span class="orphaned-count"></span>
                <span class="pagination-info"></span>
            </div>
        </div>
    <?php
    }

    /**
     * Render table filters section
     */
    private function render_table_filters()
    {
    ?>
        <div class="table-filters">
            <div class="custom-filter-group">
                <label for="file-type-filter"><?php esc_html_e('Filter by File Type:', 'orphaned-acf-media'); ?></label>
                <select id="file-type-filter">
                    <option value="all"><?php esc_html_e('All File Types', 'orphaned-acf-media'); ?></option>
                    <option value="images"><?php esc_html_e('Images', 'orphaned-acf-media'); ?></option>
                    <option value="videos"><?php esc_html_e('Videos', 'orphaned-acf-media'); ?></option>
                    <option value="audio"><?php esc_html_e('Audio', 'orphaned-acf-media'); ?></option>
                    <option value="pdfs"><?php esc_html_e('PDFs', 'orphaned-acf-media'); ?></option>
                    <option value="documents"><?php esc_html_e('Documents', 'orphaned-acf-media'); ?></option>
                </select>
            </div>
            <div class="custom-filter-group">
                <label for="safety-status-filter"><?php esc_html_e('Filter by Safety Status:', 'orphaned-acf-media'); ?></label>
                <select id="safety-status-filter">
                    <option value="all"><?php esc_html_e('All Files', 'orphaned-acf-media'); ?></option>
                    <option value="safe"><?php esc_html_e('Safe to Delete', 'orphaned-acf-media'); ?></option>
                    <option value="warning"><?php esc_html_e('Needs Review', 'orphaned-acf-media'); ?></option>
                </select>
            </div>
            <div class="filter-actions">
                <button id="clear-filters" class="button">
                    <?php esc_html_e('Clear Filters', 'orphaned-acf-media'); ?>
                </button>
                <span class="filter-results-count"></span>
            </div>
            <div class="items-per-page-group">
                <label for="items-per-page-select"><?php esc_html_e('Items per page:', 'orphaned-acf-media'); ?></label>
                <select id="items-per-page-select">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </div>
        </div>
    <?php
    }

    /**
     * Render media table
     */
    private function render_media_table()
    {
    ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all" />
                    </th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Thumbnail', 'orphaned-acf-media'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Filename', 'orphaned-acf-media'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('File Type', 'orphaned-acf-media'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Upload Date', 'orphaned-acf-media'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('File Size', 'orphaned-acf-media'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Safety Status', 'orphaned-acf-media'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Actions', 'orphaned-acf-media'); ?></th>
                </tr>
            </thead>
            <tbody id="orphaned-media-list">
                <!-- Results will be populated here -->
            </tbody>
        </table>
    <?php
    }

    /**
     * Render pagination controls
     */
    private function render_pagination_controls()
    {
    ?>
        <div class="pagination-controls bottom-pagination" style="display: none;">
            <div class="pagination-buttons">
                <button class="button first-page-btn" disabled>&laquo; <?php esc_html_e('First', 'orphaned-acf-media'); ?></button>
                <button class="button prev-page-btn" disabled>&lsaquo; <?php esc_html_e('Previous', 'orphaned-acf-media'); ?></button>
                <span class="page-numbers">
                    <span class="page-display"></span>
                </span>
                <button class="button next-page-btn" disabled><?php esc_html_e('Next', 'orphaned-acf-media'); ?> &rsaquo;</button>
                <button class="button last-page-btn" disabled><?php esc_html_e('Last', 'orphaned-acf-media'); ?> &raquo;</button>
            </div>
            <div class="pagination-info-bottom">
                <span class="pagination-details"></span>
            </div>
        </div>
    <?php
    }

    /**
     * Render loading spinner
     */
    private function render_loading_spinner()
    {
    ?>
        <div id="loading-spinner" style="display: none;">
            <p id="scanning-status"><?php esc_html_e('Scanning media files...', 'orphaned-acf-media'); ?></p>
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
    <?php
    }

    /**
     * Display ACF missing notice
     */
    public function acf_missing_notice()
    {
    ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    esc_html__('Orphaned ACF Media requires the %s plugin to be installed and activated.', 'orphaned-acf-media'),
                    '<strong>' . esc_html__('Advanced Custom Fields', 'orphaned-acf-media') . '</strong>'
                );
                ?>
            </p>
        </div>
<?php
    }
}
