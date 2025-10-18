# Orphaned ACF Media

A comprehensive WordPress plugin that finds and safely removes media files that are not used in any ACF (Advanced Custom Fields) fields. Features advanced safety checks, Oxygen Builder integration, filtering, pagination, and bulk operations to help clean up unused attachments and free up server storage space.

## 🚀 Features

### 🛡️ **Comprehensive Safety System**
- **Multi-layered Safety Checks**: Scans ACF fields, featured images, post content, widgets, navigation menus, theme customizer, and site settings
- **ACF Extended Support**: Full compatibility with ACF Extended Performance Mode (consolidated 'acf' meta field)
- **Real-time Verification**: Final safety check performed before each deletion operation
- **Detailed Usage Analysis**: Shows exactly where each media file is used across your website
- **Backup Warnings**: Clear recommendations and safety documentation

### 🔍 **Advanced Filtering & Search**
- **Server-side Filtering**: Filter by file type (images, videos, audio, PDFs, documents) and safety status
- **Consistent Results**: Filters work correctly across all paginated results
- **Smart Categorization**: Automatic file type detection and safety status classification
- **Real-time Updates**: Filters apply immediately with accurate result counts

### 📊 **Intelligent Pagination**
- **Efficient Handling**: Designed for large media libraries with thousands of files
- **Customizable Display**: Choose 25, 50, 100, or 200 items per page
- **Performance Optimized**: Smart caching system using WordPress transients
- **Accurate Counts**: Shows total files found and safe-to-delete counts across all pages

### ⚡ **Bulk Operations**
- **Selective Deletion**: Select and delete multiple files with checkboxes
- **Delete All Safe Files**: One-click deletion of all confirmed safe files
- **Progress Tracking**: Real-time progress monitoring with detailed reporting
- **Batch Processing**: Handles large operations efficiently with server-friendly batch sizes
- **Error Handling**: Comprehensive error reporting and recovery

### 💻 **Professional Interface**
- **WordPress Standards**: Clean, responsive design following WordPress admin guidelines
- **Accessibility**: Proper ARIA labels, keyboard navigation, and screen reader support
- **Mobile Responsive**: Works perfectly on tablets and mobile devices
- **Intuitive Controls**: Clear navigation with helpful tooltips and documentation

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Advanced Custom Fields (ACF) plugin
- Administrator capabilities for usage

## 🔧 Installation

### Automatic Installation
1. Upload the plugin to your `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin panel
3. Ensure Advanced Custom Fields (ACF) plugin is installed and activated
4. Navigate to **Media > Orphaned ACF Media** to start scanning

### Manual Installation
1. Download the plugin ZIP file
2. Extract to your `/wp-content/plugins/orphaned-acf-media/` directory
3. Activate through the WordPress admin panel
4. Ensure ACF plugin is active
5. Access via **Media > Orphaned ACF Media**

## 🎯 Usage Guide

### Initial Scan
1. **Navigate** to Media > Orphaned ACF Media
2. **Review** the safety warnings and backup recommendations
3. **Click** "Scan for Orphaned Media" to perform initial analysis
4. **Wait** for the scan to complete (may take time for large libraries)

### Filtering Results
- **File Type Filter**: Choose specific file types (images, videos, audio, PDFs, documents)
- **Safety Status Filter**: Filter by safety status (safe to delete, used in ACF, used in content, used in both)
- **Auto-Apply**: Filters automatically apply when changed
- **Clear Filters**: Reset all filters to show complete results

### Bulk Operations
- **Select Individual Files**: Use checkboxes to select specific files
- **Select All (This Page)**: Select all visible files on current page
- **Delete Selected**: Remove chosen files with confirmation dialog
- **Delete All Safe Files**: Remove all confirmed safe files across all pages

### Safety Features
- **Multi-point Verification**: Each file checked against 10+ usage areas
- **Final Safety Check**: Last-second verification before deletion
- **Usage Details**: See exactly where files are used
- **Protected Files**: In-use files clearly marked and protected

## 🛡️ Safety Checks

The plugin performs comprehensive safety checks across multiple areas:

### ACF Field Types
- Standard fields (text, textarea, image, file, gallery)
- Repeater fields and sub-fields
- Flexible content fields
- Options pages and custom field locations

### WordPress Core Usage
- Featured images (post thumbnails)
- Post and page content (embedded images, galleries)
- Widget content (image widgets, custom HTML)
- Navigation menu items (custom links, descriptions)

### Theme & Customization
- Theme customizer settings (logos, headers, backgrounds)
- Site icons and favicons
- Custom headers and backgrounds
- Theme modification settings

### Advanced Detection
- User profile pictures and meta
- Serialized data in custom fields
- Gallery relationships and parent-child attachments
- Custom post type meta fields

## ⚙️ Technical Specifications

### Performance Features
- **Smart Caching**: WordPress transient-based caching system
- **Batch Processing**: Server-friendly processing in manageable chunks
- **Memory Efficient**: Optimized queries to handle large datasets
- **Background Operations**: Non-blocking operations with progress tracking

### Security Features
- **Nonce Verification**: All AJAX operations secured with WordPress nonces
- **Capability Checks**: Restricted to users with `manage_options` capability
- **Input Sanitization**: All user input properly sanitized and validated
- **SQL Injection Prevention**: Prepared statements for all database queries

### Database Optimization
- **Efficient Queries**: Optimized database queries with proper indexing
- **Minimal Impact**: Non-intrusive scanning that doesn't affect site performance
- **Cache Management**: Automatic cache invalidation and refresh
- **Transaction Safety**: Proper error handling and rollback capabilities

## 🔍 Troubleshooting

### Common Issues

**Scan Shows No Results**
- Ensure ACF plugin is active and configured
- Check that you have media files uploaded
- Try clearing the cache with "Refresh" button

**Files Not Deleting**
- Review safety status - files in use are protected
- Check file permissions on server
- Verify you have administrator capabilities

**Performance Issues**
- Reduce items per page in pagination settings
- Clear plugin cache and perform fresh scan
- Check server memory limits and PHP execution time

**Filter Not Working**
- Ensure JavaScript is enabled in browser
- Clear browser cache and reload page
- Check for plugin conflicts in browser console

### Debug Mode
Enable WordPress debug mode to see detailed error information:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📊 Performance Benchmarks

### Tested Environments
- **Small Sites**: 100-500 media files - Instant results
- **Medium Sites**: 500-2000 media files - 2-5 second scans
- **Large Sites**: 2000-10000 media files - 10-30 second scans
- **Enterprise**: 10000+ media files - 30+ second scans with pagination

### Optimization Tips
- Use pagination settings appropriate for your server
- Schedule scans during low-traffic periods for large sites
- Increase PHP memory limit for sites with 5000+ media files
- Consider server-side caching for improved performance

## 🤝 Contributing

We welcome contributions to improve the plugin:

1. **Report Bugs**: Use GitHub issues for bug reports with detailed information
2. **Feature Requests**: Submit enhancement ideas with use cases
3. **Code Contributions**: Fork, develop, and submit pull requests
4. **Documentation**: Help improve documentation and user guides
5. **Testing**: Test on different environments and report compatibility

## 📞 Support

### Getting Help
- **Documentation**: Comprehensive guides and FAQ
- **Community Support**: WordPress.org support forums
- **Premium Support**: Available through CitCom support channels
- **Bug Reports**: GitHub issues for technical problems

### Support Channels
- WordPress.org Plugin Support Forum
- GitHub Issues for bug reports
- Email support for premium users
- Documentation and knowledge base

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🏢 About CitCom

**CitCom** specializes in WordPress plugin development, digital solutions, and web optimization services. We focus on creating reliable, efficient tools that help WordPress site owners manage and optimize their websites.

- **Website**: [https://citcom.co.uk](https://citcom.co.uk)
- **Support**: [https://citcom.support](https://citcom.support)
- **Plugin Directory**: [https://plugins.citcom.support](https://plugins.citcom.support)

## 📝 Changelog

### Version 1.3.3 - User Experience Improvements
- ✅ **Added User Consent**: Implemented backup consent system requiring user confirmation before enabling scan operations
- ✅ **Enhanced Loading Feedback**: Added quick loading spinners for filter changes, pagination, and items per page adjustments
- ✅ **Improved Interface Flow**: Backup consent section automatically hides after successful scan completion
- ✅ **Better Filter UX**: Filter and pagination operations now show subtle loading indicators instead of full scan progress
- ✅ **Consistent Loading States**: Unified loading experience across all user interactions with quick spinners and opacity changes
- ✅ **Enhanced Safety**: Users must explicitly confirm backup creation before accessing potentially destructive operations
- ✅ **Streamlined Workflow**: Controls and filters remain visible even when no orphaned media is found for better usability

### Version 1.3.2 - ACF Extended Performance Mode Compatibility
- ✅ **ACF Extended Support**: Added full compatibility with ACF Extended Performance Mode consolidated 'acf' meta field
- ✅ **Enhanced Detection**: Now detects media usage in consolidated ACF data storage for improved performance setups
- ✅ **Comprehensive Coverage**: Checks both standard ACF field storage and Performance Mode consolidated storage
- ✅ **Options Support**: Includes ACF Extended Performance Mode detection for ACF options pages
- 🔧 **Technical Improvement**: Prevents false positives when ACF Extended Performance Mode is enabled
- 📚 **Documentation**: Added technical documentation explaining ACF Extended compatibility

### Version 1.3.1 - Bug Fixes & User Experience Improvements
- 🐛 **Fixed Pagination Performance**: Page navigation now instant with subtle opacity fade instead of showing full scanning progress
- 🐛 **Fixed "Delete All Safe Files" Button**: Corrected property checking from safety_status to is_truly_orphaned for proper safe file detection
- ✅ **Enhanced Error Messages**: Improved user guidance with more descriptive messages for scan requirements and file states
- ✅ **Better Button States**: "Delete All Safe Files" now shows "Scan Required" when no scan data is available
- ✅ **Improved User Feedback**: Added specific notifications for different deletion scenarios and requirements
- ✅ **Cache Consistency**: Fixed cache handling issues to ensure reliable safe file detection across all operations
- ✅ **UI Polish**: Enhanced button initialization and state management for better user experience

### Version 1.3.0 - Performance Optimization & WordPress Plugin Repository Compliance
- 🚀 **Comprehensive Caching System**: Added wp_cache implementation for all database queries to significantly improve performance
- ✅ **WordPress Repository Ready**: Fixed all Plugin Check warnings and errors for WordPress.org submission
- ✅ **Enhanced Input Validation**: Improved $_POST handling with proper wp_unslash() and isset() checks
- ✅ **SQL Security Hardening**: Fixed all SQL preparation issues and properly escaped LIKE wildcards
- ✅ **Performance Optimized**: Added 5-minute caching for all media usage checks reducing database load
- ✅ **Cache Management**: Enhanced cache clearing with wp_cache_flush_group for organized cache control
- ✅ **Method-Level Caching**: Individual caching for ACF fields, content, widgets, customizer, and Oxygen Builder checks
- ✅ **Scalability Improved**: Better performance with large media libraries through intelligent caching strategies

### Version 1.0.0 - Initial Release
- ✅ **Comprehensive Safety System**: Multi-layered safety checks across 10+ usage areas
- ✅ **Advanced Filtering**: Server-side filtering by file type and safety status
- ✅ **Intelligent Pagination**: Efficient handling of large media libraries
- ✅ **Bulk Operations**: Select and delete multiple files with progress tracking
- ✅ **Delete All Safe Files**: One-click deletion with batch processing
- ✅ **Smart Caching System**: Performance-optimized WordPress transient caching
- ✅ **Detailed Usage Analysis**: Shows exactly where files are used
- ✅ **Professional UI**: Clean, responsive admin interface
- ✅ **Safety Warnings**: Clear backup recommendations and documentation
- ✅ **Error Handling**: Robust error handling with user-friendly messages
- ✅ **WordPress Standards**: Built following WordPress coding standards

---

**Ready to clean up your WordPress media library? Install Orphaned ACF Media today!**
