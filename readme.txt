=== Orphaned ACF Media ===
Contributors: citcom
Tags: media, cleanup, acf, advanced-custom-fields, attachments, orphaned, optimization, storage, maintenance, admin
Requires at least: 5.0
Tested up to: 6.8.3
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Find and safely remove media files that are not used in any ACF fields. Advanced safety checks, filtering, and bulk operations.

== Description ==

**Orphaned ACF Media** is a comprehensive WordPress plugin that helps you identify and safely remove media files that are not used in any Advanced Custom Fields (ACF) fields. Perfect for cleaning up your WordPress media library and freeing up valuable server storage space.

= 🚀 Key Features =

**🛡️ Comprehensive Safety System**
* Multi-layered safety checks across 10+ usage areas
* Real-time verification before each deletion
* Detailed usage analysis showing exactly where files are used
* Clear backup warnings and safety documentation

**🔍 Advanced Filtering & Search**
* Server-side filtering by file type and safety status
* Filter by images, videos, audio, PDFs, documents
* Consistent results across all paginated pages
* Real-time filter application with accurate counts

**📊 Intelligent Pagination**
* Efficient handling of large media libraries (1000+ files)
* Customizable display (25, 50, 100, 200 items per page)
* Smart caching system using WordPress transients
* Accurate total counts across all filtered results

**⚡ Bulk Operations**
* Select and delete multiple files with checkboxes
* "Delete All Safe Files" for one-click cleanup
* Progress tracking with detailed reporting
* Batch processing for server-friendly operations

**💻 Professional Interface**
* Clean, responsive WordPress admin design
* Mobile-friendly interface
* Accessibility features and keyboard navigation
* Intuitive controls with helpful documentation

= 🛡️ What Gets Checked =

The plugin performs comprehensive safety checks across multiple areas:

**ACF Field Usage:**
* Standard fields (image, file, gallery, etc.)
* Repeater fields and sub-fields
* Flexible content fields
* ACF Options pages

**WordPress Core Usage:**
* Featured images (post thumbnails)
* Post and page content
* Widget content
* Navigation menu items

**Theme & Customization:**
* Theme customizer settings
* Site icons and favicons
* Custom headers and backgrounds
* User profile pictures

**Advanced Detection:**
* Serialized data in custom fields
* Gallery relationships
* Custom post type meta fields
* All database references

= 🎯 Perfect For =

* **Website Administrators** cleaning up media libraries
* **Developers** maintaining client websites
* **Agencies** optimizing multiple WordPress sites
* **Content Managers** organizing media assets
* **Site Owners** reducing server storage costs

= 🔧 Requirements =

* WordPress 5.0 or higher
* Advanced Custom Fields (ACF) plugin
* PHP 7.4 or higher
* Administrator capabilities

= 🚀 Getting Started =

1. Install and activate the plugin
2. Ensure ACF plugin is active
3. Navigate to Media → Orphaned ACF Media
4. Review safety warnings and create backups
5. Click "Scan for Orphaned Media"
6. Use filters to narrow results
7. Select files and delete safely

== Installation ==

= Automatic Installation =

1. Go to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "Orphaned ACF Media"
4. Click "Install Now" and then "Activate"
5. Go to Media → Orphaned ACF Media to start

= Manual Installation =

1. Download the plugin ZIP file
2. Upload to your `/wp-content/plugins/` directory
3. Extract the files
4. Activate through the WordPress admin panel
5. Ensure ACF plugin is installed and active

= After Installation =

1. Navigate to **Media → Orphaned ACF Media**
2. Review the safety information and backup warnings
3. Click **"Scan for Orphaned Media"** to begin
4. Use filters to refine your results
5. Select and delete unused media files safely

== Frequently Asked Questions ==

= Is it safe to delete media files? =

Yes! The plugin includes comprehensive safety checks across 10+ areas of your website. It will never delete a file that's actually in use. However, we always recommend creating a complete backup before performing any deletion operations.

= What file types are supported? =

The plugin works with all WordPress media file types including:
* Images (JPG, PNG, GIF, WebP, SVG)
* Videos (MP4, AVI, MOV, WMV, WebM)
* Audio (MP3, WAV, OGG, AAC)
* Documents (PDF, DOC, XLS, PPT)
* Any other file type uploaded to WordPress

= Can I filter the results? =

Absolutely! You can filter by:
* **File Type**: Images, Videos, Audio, PDFs, Documents
* **Safety Status**: Safe to delete, Used in ACF, Used in content, Used in both
* Filters work across all pages and apply in real-time

= How does it handle large media libraries? =

The plugin is designed for efficiency:
* Smart pagination (25-200 items per page)
* Intelligent caching system
* Batch processing for deletions
* Optimized database queries
* Successfully tested with 10,000+ media files

= What if I have thousands of media files? =

The plugin handles large libraries efficiently:
* Use appropriate pagination settings for your server
* Scan operations are cached for performance
* Batch deletions prevent timeouts
* Progress tracking shows real-time status

= Does it work with other plugins? =

The plugin focuses on ACF usage but also checks for usage in:
* WordPress core features
* Widgets and menus
* Theme customizer
* Most common plugin patterns
* Custom post types and fields

= Can I undo deletions? =

WordPress media deletions are permanent. That's why we strongly recommend:
* Creating complete backups before using the plugin
* Reviewing the safety status of each file
* Testing on a staging site first
* Using selective deletion rather than bulk operations initially

== Screenshots ==

1. **Main Interface** - Clean admin interface with safety warnings and scan controls
2. **Results Table** - Comprehensive results with filtering and pagination options
3. **Safety Analysis** - Detailed safety status showing file usage across your website
4. **Bulk Operations** - Progress tracking for bulk deletions with detailed reporting
5. **Advanced Filtering** - Server-side filtering by file type and safety status

== Changelog ==

= 1.2.0 - 2025-10-17 =

**Oxygen Builder Integration & Enhanced Safety**

* ✅ **Oxygen Builder Support**: Comprehensive safety checks for Oxygen Builder content, templates, and reusable parts
* ✅ **Multi-Storage Detection**: Checks ct_builder_shortcodes, ct_builder_json, and all Oxygen content formats
* ✅ **Template Protection**: Scans Oxygen templates (ct_template) and user library parts (oxy_user_library)
* ✅ **CSS/JS Safety**: Detects media usage in Oxygen custom CSS, JavaScript, and compiled stylesheets
* ✅ **Global Settings**: Checks Oxygen VSB settings and global configurations for media references
* ✅ **Enhanced Coverage**: Now protects media used in 11+ different areas including page builders
* ✅ **Auto-Detection**: Automatically detects if Oxygen Builder is active on the website

= 1.1.0 - 2025-10-17 =

**Enhanced User Experience & Interface Improvements**

* ✅ **Smart Button States**: Delete All Safe Files button now dynamically enables/disables based on available files and shows file count
* ✅ **Auto-Apply Filters**: Removed Apply Filters button - filters now apply instantly when selections change for smoother workflow
* ✅ **Media Library Integration**: Added "Library" button to view attachments directly in WordPress Media Library with proper highlighting
* ✅ **Scan Progress Indicator**: Enhanced loading screen with animated progress bar showing real-time scan status and completion percentage
* ✅ **Improved Button Layout**: Optimized action button arrangement for better usability and responsive design
* ✅ **Better Visual Feedback**: Enhanced progress tracking during all operations with clear status messages
* ✅ **Streamlined Interface**: Removed unnecessary buttons and simplified user interactions for more intuitive experience

= 1.0.0 - 2025-10-17 =

**Initial Release - Comprehensive Media Cleanup Solution**

* ✅ **Comprehensive Safety System**: Multi-layered safety checks across ACF fields, featured images, post content, widgets, navigation menus, theme customizer, and site settings
* ✅ **Advanced Filtering**: Server-side filtering by file type (images, videos, audio, PDFs, documents) and safety status with consistent results across paginated pages
* ✅ **Intelligent Pagination**: Efficient handling of large media libraries with customizable items per page (25, 50, 100, 200) and smart caching
* ✅ **Bulk Operations**: Select and delete multiple files with progress tracking, detailed reporting, and comprehensive error handling
* ✅ **Delete All Safe Files**: One-click deletion of all confirmed safe-to-delete files with batch processing and real-time progress monitoring
* ✅ **Smart Caching System**: Performance-optimized WordPress transient caching for fast subsequent scans and reduced server load
* ✅ **Detailed Usage Analysis**: Shows exactly where each media file is used across your website with comprehensive reporting
* ✅ **Professional UI**: Clean, responsive admin interface following WordPress design standards with mobile support
* ✅ **Safety Warnings**: Clear backup recommendations, comprehensive safety documentation, and usage guidelines
* ✅ **Error Handling**: Robust error handling with user-friendly messages, detailed logging, and recovery options
* ✅ **WordPress Standards**: Built following WordPress coding standards with proper nonces, capability checks, and security measures

== Upgrade Notice ==

= 1.2.0 =
MAJOR FEATURE: Oxygen Builder integration! Now safely detects media used in Oxygen Builder content, templates, reusable parts, CSS, and global settings. Essential for Oxygen users to prevent accidental deletion of builder assets.

= 1.1.0 =
Major UX improvements! Auto-applying filters, scan progress indicator, Media Library integration, and smart button states. Enhanced interface makes media cleanup even more intuitive and efficient.

= 1.0.0 =
Initial release with comprehensive media cleanup features, advanced safety checks, and efficient bulk operations. Perfect for cleaning up unused ACF media files and optimizing your WordPress media library.

== Support ==

Need help with the plugin? Here are your support options:

**📚 Documentation**
* Comprehensive README with usage guides
* FAQ section with common questions
* Video tutorials and walkthroughs

**💬 Community Support**
* WordPress.org plugin support forums
* Community-driven help and solutions
* User-contributed tips and tricks

**🐛 Bug Reports**
* GitHub issues for technical problems
* Detailed bug reporting with examples
* Feature requests and enhancement ideas

**🎯 Premium Support**
* Priority email support for complex issues
* Custom implementation assistance
* WordPress consultation services

Visit [https://citcom.support](https://citcom.support) for more information.

== Privacy Policy ==

This plugin does not collect, store, or transmit any user data outside of your WordPress installation. All scanning and analysis is performed locally on your server. The plugin only accesses your WordPress media library and database to identify unused files.

== Credits ==

Developed by **CitCom** - WordPress specialists focused on creating reliable, efficient tools for website optimization and management.

* Website: [https://citcom.co.uk](https://citcom.co.uk)
* Support: [https://citcom.support](https://citcom.support)
* Plugin Directory: [https://plugins.citcom.support](https://plugins.citcom.support)