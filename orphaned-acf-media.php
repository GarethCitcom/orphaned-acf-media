<?php

/**
 * Plugin Name: Orphaned ACF Media
 * Plugin URI: https://plugins.citcom.support/orphaned-acf-media
 * Description: Find and delete media files that are not used in any ACF fields. Helps clean up unused attachments in your WordPress site.
 * Version: 2.1.4
 * Author: Gareth Hale, CitCom.
 * Author URI: https://citcom.co.uk
 * License: GPL2
 * Text Domain: orphaned-acf-media
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load the refactored plugin
require_once plugin_dir_path(__FILE__) . 'includes/orphaned-acf-media-refactored.php';
