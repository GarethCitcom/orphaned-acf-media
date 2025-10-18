# Technical Notes - Orphaned ACF Media Plugin

## Direct Database Query Usage

This plugin uses direct database queries for performance reasons when scanning for media usage across WordPress. This is necessary due to the complex nature of media detection across:

### Why Direct Queries Are Needed

1. **Complex Cross-Table Searches**: The plugin needs to search across `wp_posts`, `wp_postmeta`, `wp_options`, and `wp_usermeta` tables simultaneously
2. **Pattern Matching**: Media files can be referenced in various formats (URLs, IDs, filenames) requiring LIKE queries with wildcards
3. **Page Builder Integration**: Oxygen Builder stores data in complex JSON structures that require specialized parsing
4. **Performance**: WordPress APIs like `WP_Query` would require hundreds of individual queries instead of optimized batch operations

### Security Measures

- All queries use `$wpdb->prepare()` for SQL injection protection
- Input sanitization with `sanitize_text_field()` and `intval()`
- Capability checks (`manage_options`) for all operations
- Nonce verification for all AJAX requests

### Performance Optimizations

- Comprehensive caching system using `wp_cache_set()` and `wp_cache_get()`
- 5-minute cache duration for all database results
- Batch processing for bulk operations
- Progressive loading with pagination

### Query Types Used

1. **ACF Field Detection**: Search `wp_postmeta` for attachment references
2. **Content Scanning**: Search `wp_posts.post_content` for media URLs
3. **Widget Analysis**: Search `wp_options` for widget configurations
4. **Theme Customizer**: Check theme modification options
5. **Page Builder Content**: Parse Oxygen Builder JSON structures
6. **Featured Images**: Check `_thumbnail_id` meta values
7. **ACF Extended Performance Mode**: Search consolidated 'acf' meta field

### ACF Extended Performance Mode Compatibility

When ACF Extended Performance Mode is enabled, all ACF field data is consolidated into a single `acf` meta field instead of individual field keys. This optimization improves performance but requires specialized detection:

**Standard ACF Storage**:
```
meta_key: field_123abc_image
meta_value: 5964
```

**ACF Extended Performance Mode**:
```
meta_key: acf
meta_value: {"field_123abc_image":5964,"field_456def_text":"content",...}
```

The plugin detects both storage methods by:
- Searching standard individual ACF field keys
- Searching within the consolidated 'acf' meta field using JSON pattern matching
- Checking both serialized PHP format (`s:4:"5964";`) and JSON format (`"5964"`)
- Scanning both post meta and options tables for ACF Extended data

This ensures complete compatibility regardless of which ACF storage method is used.

### WordPress.org Compliance

The direct database queries are documented and justified for performance reasons. Alternative approaches using WordPress APIs would result in:
- 10-100x slower performance
- Higher memory usage
- Potential timeout issues on large sites
- Less accurate results for complex page builder content

All queries are properly prepared, cached, and follow WordPress security best practices.