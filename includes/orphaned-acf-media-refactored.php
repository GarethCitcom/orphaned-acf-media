<?php

/**
 * Orphaned ACF Media - Refactored Core Plugin Controller
 *
 * This file contains the main plugin controller class that orchestrates
 * all plugin components and functionality.
 *
 * @package OrphanedACFMedia
 * @version 2.1.0
 * @author Gareth Hale, CitCom.
 * @link https://citcom.co.uk
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ORPHANED_ACF_MEDIA_VERSION', '2.1.0');
define('ORPHANED_ACF_MEDIA_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)) . '/');
define('ORPHANED_ACF_MEDIA_PLUGIN_URL', plugin_dir_url(dirname(__FILE__)) . '/');

/**
 * Main plugin class for Orphaned ACF Media management
 *
 * This class serves as the main controller, orchestrating the different components
 * of the plugin while keeping the main file clean and organized.
 */
class OrphanedACFMedia
{

    private $admin_interface;
    private $ajax_handler;

    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init()
    {
        // Check if ACF is available
        if (!function_exists('get_field')) {
            add_action('admin_notices', array($this, 'acf_missing_notice'));
            return;
        }

        // Load required files
        $this->load_dependencies();

        // Initialize components
        $this->init_components();

        // Register hooks
        $this->register_hooks();
    }

    /**
     * Load all required files
     */
    private function load_dependencies()
    {
        // Utility classes
        require_once ORPHANED_ACF_MEDIA_PLUGIN_DIR . 'includes/utils/class-utils.php';

        // Core classes
        require_once ORPHANED_ACF_MEDIA_PLUGIN_DIR . 'includes/core/class-media-scanner.php';

        // Admin classes
        require_once ORPHANED_ACF_MEDIA_PLUGIN_DIR . 'includes/admin/class-admin-interface.php';

        // AJAX classes
        require_once ORPHANED_ACF_MEDIA_PLUGIN_DIR . 'includes/ajax/class-ajax-handler.php';
    }

    /**
     * Initialize all components
     */
    private function init_components()
    {
        $this->admin_interface = new OrphanedACFMedia_Admin();
        $this->ajax_handler = new OrphanedACFMedia_AJAX();
    }

    /**
     * Register all hooks
     */
    private function register_hooks()
    {
        // Admin hooks
        add_action('admin_menu', array($this->admin_interface, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this->admin_interface, 'enqueue_admin_assets'));

        // AJAX hooks
        $this->ajax_handler->register_hooks();
    }

    /**
     * Display ACF missing notice
     */
    public function acf_missing_notice()
    {
        $this->admin_interface->acf_missing_notice();
    }
}

// Initialize the plugin
new OrphanedACFMedia();
