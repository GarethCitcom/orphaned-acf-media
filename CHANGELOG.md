# Changelog

All notable changes to the Orphaned ACF Media plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.1] - 2025-10-19

### Fixed
- **Fatal Error Fix**: Resolved critical fatal error when ACF plugin is missing during activation
  - Fixed: `Call to a member function acf_missing_notice() on null` error
  - Replaced admin_interface dependency with self-contained notice method
  - Enhanced error handling for missing ACF dependency
- **Activation Reliability**: Improved plugin activation process and error handling
- **User Experience**: Added clear ACF installation guidance with direct plugin install link

### Enhanced
- **Dependency Management**: Better handling of missing required plugins
- **Error Messages**: More informative and actionable error notices
- **Production Stability**: Prevents activation failures on fresh WordPress installations

## [2.1.0] - 2025-10-18

### Added
- **WooCommerce Integration**: Comprehensive safety checks for WooCommerce content including:
  - Product gallery images (_product_image_gallery)
  - Product featured images (_thumbnail_id)
  - Product category and tag thumbnails
  - Product variations and variation images
  - WooCommerce customizer settings and theme options
  - Product descriptions and content
  - WooCommerce-specific options and configurations

### Enhanced
- **Safety System**: Extended safety checks to include WooCommerce e-commerce content
- **Usage Detection**: Added "WooCommerce (Products/Categories)" to usage details display
- **E-commerce Protection**: Prevents accidental deletion of critical WooCommerce media

### Fixed
- **Plugin Header Validation**: Resolved "The plugin does not have a valid header" activation error
- **Delete All Safe Files**: Fixed AJAX action mismatch and batching issues for proper functionality
- **Bulk Operations**: Added missing bulk delete handler and enhanced error handling

## [2.0.0] - 2025-10-18

### Added
- **Complete Architecture Refactor**: Transformed 1,415-line monolithic file into modular object-oriented structure with includes/ folder organization
- **Modular Components**: Created dedicated classes for Media Scanner, Admin Interface, AJAX Handler, and Utilities
- **Enhanced Oxygen Builder Support**: Fixed detection and added comprehensive Oxygen Builder v6 integration with `_oxygen_data` field support
- **Advanced Debugging System**: Comprehensive logging for troubleshooting detection issues and performance monitoring
- **Cache Refresh Functionality**: Added missing AJAX handler for cache clearing operations

### Fixed
- **Oxygen Builder Detection**: Corrected plugin detection path from `oxygen/functions.php` to `oxygen/plugin.php`
- **Cache Clearing Error**: Fixed "An error occurred while clearing cache" by implementing missing AJAX handler
- **Query Optimization**: Removed over-escaping in catch-all queries that prevented Oxygen Builder detection
- **Usage Detection Logic**: Enhanced catch-all meta queries to properly detect page builder content

### Improved
- **Performance**: Optimized caching system and memory management across all components
- **Maintainability**: Single responsibility classes with clear separation of concerns for easier development
- **Safety System**: Modular safety checks with better error handling and comprehensive logging
- **Code Organization**: Logical grouping of related functionality in dedicated directories
- **Extensibility**: Plugin architecture ready for future features and API integrations

### Changed
- **File Structure**: Reorganized into includes/ folder with core/, admin/, ajax/, and utils/ subdirectories
- **Class Architecture**: Converted to object-oriented structure with dedicated controller classes
- **Loading System**: Improved dependency management and component initialization
- **Error Handling**: Enhanced error logging and user feedback across all operations

### Removed
- **Debug Logging**: Cleaned up temporary debug code and logging statements
- **Monolithic Code**: Replaced single large file with organized modular structure
- **Temporary Files**: Removed test files and debugging utilities

## [1.3.3] - 2025-10-18

### Added
- **User Consent System**: Implemented backup consent checkbox requiring user confirmation before enabling scan operations for enhanced safety
- **Quick Loading Spinners**: Added subtle loading indicators for filter changes, pagination, and items per page adjustments for better user feedback
- **Enhanced Safety Flow**: Backup consent section automatically hides after successful scan completion for streamlined workflow

### Improved
- **Filter UX**: Filter and pagination operations now show quick loading spinners instead of full scan progress for more appropriate feedback
- **Consistent Loading States**: Unified loading experience across all user interactions with quick spinners and opacity changes
- **Interface Workflow**: Controls and filters remain visible even when no orphaned media is found for better usability
- **User Safety**: Users must explicitly confirm backup creation before accessing potentially destructive operations
- **Loading Feedback**: Enhanced visual feedback for all interactive operations with professional loading animations

### Changed
- **Loading Behavior**: Clear Filters button now uses quick loading spinner consistent with other filter operations
- **Consent Flow**: Scan buttons are disabled by default until user confirms backup creation
- **Interface Persistence**: Results container remains visible with controls accessible even when no orphaned media found

## [1.3.2] - 2025-10-18

### Added
- **ACF Extended Performance Mode Support**: Full compatibility with ACF Extended Performance Mode that consolidates all ACF field data into a single 'acf' meta field
- **Consolidated Storage Detection**: Enhanced media detection to search within consolidated ACF data storage for improved performance setups
- **Options Page Support**: Added detection for ACF Extended Performance Mode in ACF options pages (options_acf field)
- **Comprehensive Pattern Matching**: Implemented detection for both JSON format (`"123"`) and PHP serialized format (`:123;`) within consolidated fields
- **Technical Documentation**: Added comprehensive documentation explaining ACF Extended compatibility and implementation details

### Fixed
- **False Positive Prevention**: Prevents incorrect identification of media files as orphaned when ACF Extended Performance Mode is enabled
- **Detection Coverage**: Ensures complete media usage detection regardless of which ACF storage method is used (standard vs Performance Mode)

### Improved
- **Compatibility**: Maintains backward compatibility with standard ACF field storage while adding Performance Mode support
- **Performance**: All new detection queries include proper caching with 5-minute duration for optimal performance
- **Documentation**: Enhanced technical notes with detailed explanations of ACF Extended storage methods and detection patterns

## [1.3.1] - 2025-10-18

### Fixed
- **Pagination Performance**: Page navigation now instant with subtle opacity fade instead of showing full scanning progress spinner
- **Delete All Safe Files Button**: Corrected property checking from safety_status to is_truly_orphaned for proper safe file detection
- **Cache Consistency**: Fixed cache handling issues to ensure reliable safe file detection across all operations

### Improved
- **Error Messages**: Enhanced user guidance with more descriptive messages for scan requirements and file states
- **Button States**: "Delete All Safe Files" now shows "Scan Required" when no scan data is available
- **User Feedback**: Added specific notifications for different deletion scenarios and requirements
- **UI Polish**: Enhanced button initialization and state management for better user experience

## [1.3.0] - 2025-10-18

### Added
- **Comprehensive Caching System**: Implemented wp_cache for all database queries to significantly improve performance
- **Method-Level Caching**: Individual caching strategies for ACF fields, content, widgets, customizer, and Oxygen Builder checks
- **Cache Management**: Enhanced cache clearing with wp_cache_flush_group for organized cache control
- **Performance Optimization**: 5-minute caching duration for optimal balance between performance and data freshness

### Fixed
- **WordPress Repository Compliance**: Resolved all Plugin Check warnings and errors for WordPress.org submission
- **Input Validation Security**: Enhanced $_POST handling with proper wp_unslash() and isset() checks
- **SQL Security Hardening**: Fixed all SQL preparation issues and properly escaped LIKE wildcards with placeholders
- **Direct Database Query Warnings**: Added appropriate caching to address all WordPress.DB.DirectDatabaseQuery warnings

### Improved
- **Database Performance**: Reduced database load through intelligent caching of repeated queries
- **UI Responsiveness**: Faster response times for media scanning operations, especially with large libraries
- **Resource Efficiency**: Lower server resource usage during bulk operations and repeated scans
- **Scalability**: Better performance with larger WordPress installations and extensive media libraries

### Security
- **Enhanced Input Sanitization**: All user inputs now properly validated, unslashed, and sanitized
- **SQL Injection Prevention**: All database queries use proper $wpdb->prepare() with placeholders
- **Nonce Validation**: Improved security checks for all AJAX operations

## [1.2.2] - 2025-10-18

### Fixed
- **Critical Oxygen Builder 6 JSON Parsing**: Now correctly parses the actual _oxygen_data meta field with complex JSON structure
- **Proper JSON Structure Handling**: Added comprehensive parsing for {"tree_json_string": "..."} structure used by Oxygen v6
- **Media Pattern Detection**: Detects media references in nested JSON including ID, filename, URL, and media object structures
- **Recursive Search Algorithm**: Implements deep search through complex nested Oxygen element trees
- **Database-Verified Detection**: Based on actual Oxygen v6 database structure analysis and real-world testing

### Added
- **Multiple Search Patterns**: Checks for "id":5964, "filename":"file.png", URLs, srcset, and media arrays
- **JSON Parser Methods**: New check_oxygen_v6_data_structure() and recursive_media_search() methods
- **Comprehensive Pattern Matching**: Handles escaped URLs, partial filenames, and nested media objects

### Improved
- **Accurate Detection**: Now properly identifies media usage in actual Oxygen Builder 6 installations
- **Performance Optimized**: Efficient JSON parsing with targeted pattern matching
- **Real-World Compatibility**: Verified against actual Oxygen v6 database structures

## [1.2.1] - 2025-10-18

### Fixed
- **Critical Oxygen Builder 6 Detection**: Added proper support for Breakdance-based Oxygen Builder 6 meta fields
- **Enhanced Meta Field Support**: Now checks _breakdance_data, breakdance_data, _breakdance_tree_json, and _breakdance_css
- **Improved Template Detection**: Added support for breakdance_template, breakdance_block, breakdance_header, and breakdance_footer post types
- **Better CSS Detection**: Enhanced detection of Breakdance compiled CSS and cache files
- **Plugin Recognition**: Improved detection of both classic Oxygen Builder and Breakdance-based Oxygen Builder 6

### Improved
- **Dual Version Support**: Maintains backward compatibility with classic Oxygen Builder while adding full v6 support
- **Enhanced Safety Coverage**: More comprehensive detection of media usage in Oxygen Builder 6 environments
- **Better Page Builder Support**: Enhanced integration with modern page builder architectures

## [1.2.0] - 2025-10-17

### Added
- **Oxygen Builder Integration**: Comprehensive support for Oxygen Builder 6 content detection
- **Multi-Format Detection**: Checks ct_builder_shortcodes, ct_builder_json, and all Oxygen storage formats
- **Template Protection**: Scans Oxygen templates (ct_template post type) and user library parts (oxy_user_library)
- **CSS/JS Safety**: Detects media usage in Oxygen custom CSS, JavaScript, and compiled stylesheets (oxygen_vsb_css_cache)
- **Global Settings Check**: Scans Oxygen VSB settings and global configurations for media references
- **Auto-Detection**: Automatically detects if Oxygen Builder plugin is active on the website

### Improved
- **Enhanced Safety Coverage**: Now protects media used in 11+ different areas including page builders
- **Better Builder Support**: First page builder to receive dedicated integration support
- **Comprehensive Protection**: Covers all known Oxygen Builder storage methods and content types

### Security
- **Page Builder Safety**: Prevents accidental deletion of media files used in Oxygen Builder designs
- **Template Preservation**: Protects media used in reusable templates and components

## [1.1.0] - 2025-10-17

### Added
- **Scan Progress Indicator**: Real-time progress bar during media scanning with percentage completion and status messages
- **Media Library Integration**: New "Library" button to view attachments directly in WordPress Media Library
- **Smart Button States**: Dynamic enable/disable of "Delete All Safe Files" button based on available files
- **File Count Display**: Button shows exact number of safe files available for deletion

### Improved
- **Auto-Apply Filters**: Filters now apply instantly when selections change without requiring manual "Apply" button
- **User Interface**: Streamlined interface with removal of unnecessary "Apply Filters" button
- **Visual Feedback**: Enhanced progress tracking and status messages throughout all operations
- **Button Layout**: Optimized action button arrangement for better usability and responsive design
- **User Experience**: More intuitive workflow with automatic updates and clearer visual cues

### Removed
- Manual "Apply Filters" button (filters now auto-apply)

## [1.0.0] - 2025-10-17

### Added - Initial Release

#### 🛡️ Comprehensive Safety System
- **Multi-layered Safety Checks**: Implemented comprehensive safety verification across 10+ usage areas including ACF fields, featured images, post content, widgets, navigation menus, theme customizer, and site settings
- **Real-time Verification**: Added final safety check performed immediately before each deletion operation
- **Detailed Usage Analysis**: Created system to show exactly where each media file is used across the website
- **Backup Warnings**: Integrated clear backup recommendations and comprehensive safety documentation throughout the interface

#### 🔍 Advanced Filtering & Search
- **Server-side Filtering**: Implemented robust filtering by file type (images, videos, audio, PDFs, documents) and safety status with backend processing
- **Consistent Results**: Ensured filters work correctly across all paginated results with proper server-side implementation
- **Smart Categorization**: Added automatic file type detection and safety status classification based on comprehensive analysis
- **Real-time Updates**: Created system where filters apply immediately with accurate result counts and proper pagination updates

#### 📊 Intelligent Pagination
- **Efficient Handling**: Designed architecture to handle large media libraries with thousands of files using optimized queries and caching
- **Customizable Display**: Implemented user-selectable pagination options (25, 50, 100, 200 items per page) with proper state management
- **Performance Optimized**: Created smart caching system using WordPress transients for improved performance on subsequent scans
- **Accurate Counts**: Added comprehensive counting system showing total files found and safe-to-delete counts across all filtered pages

#### ⚡ Bulk Operations
- **Selective Deletion**: Implemented checkbox-based multi-file selection with proper state management across pagination
- **Delete All Safe Files**: Created one-click deletion system for all confirmed safe-to-delete files with comprehensive batch processing
- **Progress Tracking**: Added real-time progress monitoring with detailed reporting, success/failure counts, and user feedback
- **Batch Processing**: Implemented server-friendly batch processing system that handles large operations efficiently without timeout issues
- **Error Handling**: Created comprehensive error reporting and recovery system with detailed user feedback and logging

#### 💻 Professional Interface
- **WordPress Standards**: Designed clean, responsive interface following WordPress admin design guidelines and accessibility standards
- **Accessibility Features**: Implemented proper ARIA labels, keyboard navigation support, and screen reader compatibility
- **Mobile Responsive**: Created fully responsive design that works perfectly on tablets, mobile devices, and desktop computers
- **Intuitive Controls**: Designed clear navigation system with helpful tooltips, documentation, and contextual help

#### 🔧 Technical Infrastructure
- **Smart Caching System**: Implemented WordPress transient-based caching with proper invalidation and refresh mechanisms
- **Database Optimization**: Created efficient database queries with proper indexing and minimal performance impact
- **Security Implementation**: Added comprehensive security with nonce verification, capability checks, input sanitization, and SQL injection prevention
- **Error Management**: Built robust error handling with user-friendly messages, detailed logging, and proper recovery procedures

#### 📋 Safety & Validation
- **ACF Integration**: Deep integration with Advanced Custom Fields including standard fields, repeater fields, flexible content, and options pages
- **WordPress Core Compatibility**: Comprehensive checking of featured images, post content, widgets, navigation menus, and core WordPress features
- **Theme Integration**: Full compatibility checking with theme customizer, site icons, custom headers/backgrounds, and theme modification settings
- **Advanced Detection**: Implemented detection of user profile pictures, serialized data, gallery relationships, and custom post type meta fields

#### 🎯 User Experience
- **Comprehensive Documentation**: Created extensive documentation including usage guides, troubleshooting, and best practices
- **Safety Warnings**: Integrated clear backup recommendations and safety guidelines throughout the user interface
- **Progress Feedback**: Implemented real-time feedback systems for all operations with clear status indicators and completion messages
- **Flexible Workflow**: Designed workflow that accommodates both careful selective deletion and efficient bulk cleanup operations

### Technical Specifications

#### Performance Features
- WordPress transient-based caching system for improved performance
- Server-friendly batch processing in manageable chunks
- Memory-efficient queries optimized for large datasets
- Non-blocking background operations with progress tracking

#### Security Features
- CSRF protection via WordPress nonces for all AJAX operations
- Restricted access to users with `manage_options` capability
- Complete input sanitization and validation for all user inputs
- Prepared statements for all database queries preventing SQL injection

#### Compatibility
- WordPress 5.0+ compatibility with full testing up to WordPress 6.4
- PHP 7.4+ support with modern coding standards
- Advanced Custom Fields (ACF) integration with all field types
- Mobile and tablet responsive design with touch-friendly controls

#### Database Optimization
- Efficient queries with proper indexing and minimal server impact
- Non-intrusive scanning that doesn't affect site performance
- Automatic cache management with smart invalidation
- Transaction safety with proper error handling and rollback capabilities

### Known Limitations
- Requires Advanced Custom Fields (ACF) plugin to be installed and activated
- Deletion operations are permanent and cannot be undone (backup recommended)
- Very specialized media usage patterns might not be detected
- Performance on shared hosting may vary based on server resources

### Future Development
- Enhanced integration with popular page builders
- Advanced reporting and analytics features
- Automated scheduling for regular cleanup operations
- Extended file type support and detection capabilities