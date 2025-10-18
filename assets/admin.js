/* Orphaned ACF Media Admin JavaScript */

(function($) {
    'use strict';

    let isScanning = false;
    let orphanedMediaData = [];
    let currentPage = 1;
    let itemsPerPage = 50;
    let totalPages = 1;
    let paginationData = {};

    $(document).ready(function() {
        initializeEventHandlers();

        // Initialize button states on page load
        updateDeleteAllSafeButton(0);
        updateBulkDeleteButton();
    });

    function initializeEventHandlers() {
        // Backup consent checkbox
        $('#backup-consent-checkbox').on('change', function() {
            const isChecked = $(this).prop('checked');
            const $scanBtn = $('#scan-orphaned-media');
            const $refreshBtn = $('#refresh-scan');

            if (isChecked) {
                $scanBtn.prop('disabled', false).attr('title', 'Scan for orphaned media files');
                $refreshBtn.prop('disabled', false).attr('title', 'Clear cache and perform fresh scan');
            } else {
                $scanBtn.prop('disabled', true).attr('title', 'Please confirm backup before scanning');
                $refreshBtn.prop('disabled', true).attr('title', 'Please confirm backup before scanning');
            }
        });

        // Scan button click
        $('#scan-orphaned-media').on('click', function() {
            if (!isScanning && !$(this).prop('disabled')) {
                scanOrphanedMedia();
            }
        });

        // Refresh button click
        $('#refresh-scan').on('click', function() {
            if (!isScanning && !$(this).prop('disabled')) {
                refreshScan();
            }
        });

        // Select all checkbox
        $('#cb-select-all').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.media-checkbox').prop('checked', isChecked);
            updateBulkDeleteButton();
        });

        // Individual checkbox change
        $(document).on('change', '.media-checkbox', function() {
            updateSelectAllCheckbox();
            updateBulkDeleteButton();
        });

        // Select all button
        $('#select-all-orphaned').on('click', function() {
            $('.media-checkbox').prop('checked', true);
            updateSelectAllCheckbox();
            updateBulkDeleteButton();
        });

        // Bulk delete button
        $('#bulk-delete-orphaned').on('click', function() {
            bulkDeleteOrphanedMedia();
        });

        // Single delete buttons
        $(document).on('click', '.delete-single-media', function(e) {
            e.preventDefault();
            const attachmentId = $(this).data('attachment-id');
            deleteSingleMedia(attachmentId, $(this));
        });

        // Delete all safe button
        $('#delete-all-safe').on('click', function() {
            deleteAllSafeMedia();
        });

        // Pagination event handlers
        $('#first-page, .first-page-btn').on('click', function() {
            if (currentPage > 1) {
                goToPage(1);
            }
        });

        $('#prev-page, .prev-page-btn').on('click', function() {
            if (currentPage > 1) {
                goToPage(currentPage - 1);
            }
        });

        $('#next-page, .next-page-btn').on('click', function() {
            if (currentPage < totalPages) {
                goToPage(currentPage + 1);
            }
        });

        $('#last-page, .last-page-btn').on('click', function() {
            if (currentPage < totalPages) {
                goToPage(totalPages);
            }
        });

        $('#current-page-input').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                const newPage = parseInt($(this).val());
                if (newPage >= 1 && newPage <= totalPages) {
                    goToPage(newPage);
                } else {
                    $(this).val(currentPage);
                }
            }
        });

        $('#items-per-page-select').on('change', function() {
            itemsPerPage = parseInt($(this).val());
            currentPage = 1;
            loadPage(1);
        });

        // Filter controls
        $('#clear-filters').on('click', function() {
            $('#file-type-filter').val('all');
            $('#safety-status-filter').val('all');
            $('.filter-results-count').text('');
            // Use loadPage for consistent UX with quick spinner
            currentPage = 1;
            loadPage(1);
        });

        // Apply filters when dropdown changes
        $('#file-type-filter, #safety-status-filter').on('change', function() {
            // Auto-apply filters when dropdown changes
            currentPage = 1;
            loadPage(1);
        });

        // Media Library button click
        $(document).on('click', '.btn-media-library', function(e) {
            e.preventDefault();
            const attachmentId = $(this).data('attachment-id');
            openMediaLibrary(attachmentId);
        });
    }

    function refreshScan() {
        if (isScanning) return;

        isScanning = true;
        $('#refresh-scan').prop('disabled', true).text('Clearing...');

        $.ajax({
            url: orphanedACFMedia.ajaxUrl,
            type: 'POST',
            data: {
                action: 'clear_orphaned_cache',
                nonce: orphanedACFMedia.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message and reload page for complete refresh
                    showNotice('success', 'Cache cleared successfully. Reloading page...');

                    // Reload the page after a short delay to show the success message
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice('error', 'Failed to clear cache.');
                    $('#refresh-scan').prop('disabled', false).text('Refresh');
                    isScanning = false;
                }
            },
            error: function() {
                showNotice('error', 'An error occurred while clearing cache.');
                $('#refresh-scan').prop('disabled', false).text('Refresh');
                isScanning = false;
            }
        });
    }

    function scanOrphanedMedia(page = 1) {
        if (isScanning) return;

        isScanning = true;
        currentPage = page;
        $('#scan-orphaned-media').prop('disabled', true).text('Scanning...');
        $('#refresh-scan').prop('disabled', true);
        $('#loading-spinner').show();
        $('#orphaned-media-results').hide();

        // Initialize progress
        updateScanProgress(0, 'Initializing scan...');

        $.ajax({
            url: orphanedACFMedia.ajaxUrl,
            type: 'POST',
            data: {
                action: 'scan_orphaned_media',
                page: currentPage,
                per_page: itemsPerPage,
                file_type_filter: $('#file-type-filter').val() || 'all',
                safety_status_filter: $('#safety-status-filter').val() || 'all',
                scan_all: true, // Fresh scan for full scanning process
                nonce: orphanedACFMedia.nonce
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                // Simulate progress for demonstration
                var progress = 0;
                var progressInterval = setInterval(function() {
                    progress += 10;
                    if (progress <= 90) {
                        updateScanProgress(progress, 'Scanning media files...');
                    } else {
                        updateScanProgress(95, 'Processing results...');
                        clearInterval(progressInterval);
                    }
                }, 200);
                return xhr;
            },
            success: function(response) {
                updateScanProgress(100, 'Scan complete!');
                if (response.success) {
                    orphanedMediaData = response.data.media;
                    paginationData = response.data.pagination;
                    totalPages = paginationData.total_pages;
                    displayOrphanedMedia(orphanedMediaData);
                    updatePaginationControls();

                    // Hide backup consent section after successful scan
                    $('.backup-consent').fadeOut(500);
                } else {
                    showNotice('error', 'Failed to scan for orphaned media files.');
                }
            },
            error: function() {
                showNotice('error', 'An error occurred while scanning for orphaned media files.');
            },
            complete: function() {
                isScanning = false;
                $('#scan-orphaned-media').prop('disabled', false).text('Scan for Orphaned Media');
                $('#refresh-scan').prop('disabled', false);
                $('#loading-spinner').hide();
            }
        });
    }

    function goToPage(page) {
        if (page !== currentPage && page >= 1 && page <= totalPages) {
            loadPage(page);
        }
    }

    // Make goToPage globally accessible
    window.goToPage = goToPage;

    function loadPage(page) {
        if (isScanning) return;

        // Simple page loading with opacity change and small spinner
        currentPage = page;
        const $results = $('#orphaned-media-results');
        const $pagination = $('.pagination-controls');
        const $quickSpinner = $('#quick-loading-spinner');

        $results.css('opacity', '0.6');
        $pagination.css('opacity', '0.6');
        $quickSpinner.show();

        $.ajax({
            url: orphanedACFMedia.ajaxUrl,
            type: 'POST',
            data: {
                action: 'scan_orphaned_media',
                page: currentPage,
                per_page: itemsPerPage,
                file_type_filter: $('#file-type-filter').val() || 'all',
                safety_status_filter: $('#safety-status-filter').val() || 'all',
                scan_all: false, // Use cached results for pagination
                nonce: orphanedACFMedia.nonce
            },
            success: function(response) {
                if (response.success) {
                    orphanedMediaData = response.data.media;
                    paginationData = response.data.pagination;
                    totalPages = paginationData.total_pages;
                    displayOrphanedMedia(orphanedMediaData);
                    updatePaginationControls();
                } else {
                    showNotice('error', 'Failed to load page.');
                }
            },
            error: function() {
                showNotice('error', 'An error occurred while loading the page.');
            },
            complete: function() {
                $results.css('opacity', '1');
                $pagination.css('opacity', '1');
                $quickSpinner.hide();
            }
        });
    }

    function updatePaginationControls() {
        // Show pagination if we have multiple pages or items
        if (totalPages > 1 || paginationData.total_items > itemsPerPage) {
            $('.pagination-controls').show();
        } else {
            $('.pagination-controls').hide();
        }

        // Update pagination info
        const start = ((currentPage - 1) * itemsPerPage) + 1;
        const end = Math.min(currentPage * itemsPerPage, paginationData.total_items);
        $('.pagination-info').text(`Showing ${start}-${end} of ${paginationData.total_items} items`);

        // Update page input and total
        $('#current-page-input').val(currentPage);
        $('#total-pages').text(totalPages);
        $('.page-display').text(`Page ${currentPage} of ${totalPages}`);

        // Update button states
        const isFirst = currentPage === 1;
        const isLast = currentPage === totalPages;

        $('#first-page, .first-page-btn').prop('disabled', isFirst);
        $('#prev-page, .prev-page-btn').prop('disabled', isFirst);
        $('#next-page, .next-page-btn').prop('disabled', isLast);
        $('#last-page, .last-page-btn').prop('disabled', isLast);

        // Update current page input constraints
        $('#current-page-input').attr('max', totalPages);
    }

    function displayOrphanedMedia(mediaFiles) {
        const $results = $('#orphaned-media-results');
        const $list = $('#orphaned-media-list');
        const $count = $('.orphaned-count');

        // Clear previous results
        $list.empty();

        // Remove any existing "no orphaned media" message
        $('.no-orphaned-media').remove();

        // Check if we have pagination data
        const totalItems = paginationData.total_items || 0;

        if (totalItems === 0) {
            // No orphaned files found at all
            $results.show(); // Keep the results container visible
            $('.wp-list-table').hide(); // Hide only the table
            $('.pagination-controls').hide(); // Hide pagination
            showNoOrphanedMedia();
            return;
        }

        if (mediaFiles.length === 0 && totalItems > 0) {
            // No files on this specific page, but files exist on other pages
            $list.append(`
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <p>No orphaned media files on this page.</p>
                        <p><a href="#" onclick="goToPage(1); return false;">Go to first page</a></p>
                    </td>
                </tr>
            `);
        } else {
            // Populate table with media files
            mediaFiles.forEach(function(media) {
                const row = createMediaRow(media);
                $list.append(row);
            });
        }

        // Update count
        // Update count and show breakdown
        const safeTodDelete = mediaFiles.filter(media => media.is_truly_orphaned).length;
        const totalSafeOverall = paginationData.total_safe_to_delete || 0;
        const totalSafeText = totalSafeOverall > 0 ? ` (${totalSafeOverall} safe to delete overall)` : '';

        $count.text(`${totalItems} file${totalItems !== 1 ? 's' : ''} not used in ACF fields found${totalSafeText}`);

        // Update Delete All Safe button state
        updateDeleteAllSafeButton(totalSafeOverall);

        // Show results
        $results.show();
        $('.wp-list-table').show(); // Ensure table is visible when we have results

        // Reset checkboxes and buttons
        $('#cb-select-all').prop('checked', false);
        updateBulkDeleteButton();
    }

    function createMediaRow(media) {
        const thumbnail = media.thumbnail || '<div class="attachment-preview type-' + media.mime_type.split('/')[0] + '"><div class="thumbnail"><div class="centered"><img src="' + getFileTypeIcon(media.mime_type) + '" draggable="false" alt=""></div></div></div>';

        // Determine safety status with more detailed information
        let safetyStatus = '';
        let safetyClass = '';
        let usageDetailsHtml = '';

        if (media.is_truly_orphaned) {
            safetyStatus = 'Safe to Delete';
            safetyClass = 'safe';
            usageDetailsHtml = '<div class="usage-details"><small>Not found in ACF fields or site content</small></div>';
        } else {
            // File is used somewhere - show detailed status
            if (media.is_used_in_acf && media.is_used_elsewhere) {
                safetyStatus = 'Used in ACF & Content';
                safetyClass = 'danger';
            } else if (media.is_used_in_acf) {
                safetyStatus = 'Used in ACF Fields';
                safetyClass = 'danger';
            } else if (media.is_used_elsewhere) {
                safetyStatus = 'Used in Content';
                safetyClass = 'warning';
            } else {
                safetyStatus = 'Usage Unknown';
                safetyClass = 'warning';
            }

            if (media.usage_details && media.usage_details.length > 0) {
                usageDetailsHtml = '<div class="usage-details"><ul>' +
                    media.usage_details.map(detail => `<li>${escapeHtml(detail)}</li>`).join('') +
                    '</ul></div>';
            }
        }

        return `
            <tr class="media-item-row" data-attachment-id="${media.id}" data-truly-orphaned="${media.is_truly_orphaned}">
                <td class="check-column">
                    <input type="checkbox" class="media-checkbox" value="${media.id}" ${!media.is_truly_orphaned ? 'disabled' : ''}>
                </td>
                <td class="column-thumbnail">
                    ${thumbnail}
                </td>
                <td class="column-filename">
                    <div class="attachment-details">
                        <div class="attachment-info">
                            <h4>${escapeHtml(media.title || media.filename)}</h4>
                            <p class="media-meta">${escapeHtml(media.filename)}</p>
                        </div>
                    </div>
                </td>
                <td class="column-file-type">
                    ${escapeHtml(media.mime_type)}
                </td>
                <td class="column-upload-date">
                    ${media.upload_date}
                </td>
                <td class="column-file-size">
                    ${media.file_size}
                </td>
                <td class="column-safety-status">
                    <span class="safety-status ${safetyClass}">${safetyStatus}</span>
                    ${usageDetailsHtml}
                </td>
                <td class="column-actions">
                    <div class="action-buttons">
                        <a href="${media.url}" target="_blank" class="btn-view-media" title="View media file in new tab">
                            <span class="dashicons dashicons-external"></span>
                            View
                        </a>
                        <a href="#" class="btn-media-library" data-attachment-id="${media.id}" title="View in Media Library">
                            <span class="dashicons dashicons-admin-media"></span>
                            Library
                        </a>
                        ${media.is_truly_orphaned ?
                            `<button type="button" class="btn-delete-media delete-single-media" data-attachment-id="${media.id}" title="Delete this media file">
                                <span class="dashicons dashicons-trash"></span>
                                Delete
                            </button>` :
                            `<span class="delete-disabled" title="File is in use and cannot be deleted">
                                <span class="dashicons dashicons-lock"></span>
                                Protected
                            </span>`
                        }
                    </div>
                </td>
            </tr>
        `;
    }

    function getFileTypeIcon(mimeType) {
        // Default WordPress media icons based on file type
        const iconBase = '/wp-includes/images/media/';

        if (mimeType.startsWith('image/')) {
            return iconBase + 'default.png';
        } else if (mimeType.startsWith('video/')) {
            return iconBase + 'video.png';
        } else if (mimeType.startsWith('audio/')) {
            return iconBase + 'audio.png';
        } else if (mimeType === 'application/pdf') {
            return iconBase + 'document.png';
        } else {
            return iconBase + 'default.png';
        }
    }

    function deleteSingleMedia(attachmentId, $button) {
        if (!confirm(orphanedACFMedia.confirmDelete)) {
            return;
        }

        const $row = $button.closest('.media-item-row');
        $row.addClass('deleting');
        $button.addClass('deleting').html('<span class="dashicons dashicons-update"></span> Deleting...');

        $.ajax({
            url: orphanedACFMedia.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_orphaned_media',
                attachment_id: attachmentId,
                nonce: orphanedACFMedia.nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.addClass('deleted');
                    $button.html('<span class="dashicons dashicons-yes"></span> Deleted');

                    // Remove from orphanedMediaData array
                    orphanedMediaData = orphanedMediaData.filter(media => media.id != attachmentId);

                    // Update count
                    updateOrphanedCount();

                    // Fade out and remove row after delay
                    setTimeout(function() {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            updateSelectAllCheckbox();
                            updateBulkDeleteButton();

                            // Check if no media left
                            if ($('#orphaned-media-list tr').length === 0) {
                                showNoOrphanedMedia();
                                $('#orphaned-media-results').hide();
                            }
                        });
                    }, 1000);

                    showNotice('success', response.data.message);
                } else {
                    $row.removeClass('deleting');
                    $button.removeClass('deleting').html('<span class="dashicons dashicons-trash"></span> Delete');
                    showNotice('error', response.data.message || 'Failed to delete media file.');
                }
            },
            error: function() {
                $row.removeClass('deleting');
                $button.removeClass('deleting').html('<span class="dashicons dashicons-trash"></span> Delete');
                showNotice('error', 'An error occurred while deleting the media file.');
            }
        });
    }

    function bulkDeleteOrphanedMedia() {
        const selectedIds = [];
        $('.media-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            showNotice('warning', 'Please select media files to delete.');
            return;
        }

        // Count how many are truly safe to delete
        const safeToDeleteCount = selectedIds.filter(id => {
            const $row = $(`.media-item-row[data-attachment-id="${id}"]`);
            return $row.attr('data-truly-orphaned') === 'true';
        }).length;

        if (safeToDeleteCount === 0) {
            showNotice('warning', 'None of the selected files are safe to delete. They are all currently in use.');
            return;
        }

        const confirmMessage = safeToDeleteCount !== selectedIds.length ?
            `${safeToDeleteCount} out of ${selectedIds.length} selected files are safe to delete. Files in use will be automatically skipped. Continue?` :
            orphanedACFMedia.confirmBulkDelete;

        if (!confirm(confirmMessage)) {
            return;
        }

        // Mark selected rows as deleting
        selectedIds.forEach(function(id) {
            const $row = $(`.media-item-row[data-attachment-id="${id}"]`);
            if ($row.attr('data-truly-orphaned') === 'true') {
                $row.addClass('deleting');
                $row.find('.delete-single-media').addClass('deleting').html('<span class="dashicons dashicons-update"></span> Deleting...');
            }
        });

        $('#bulk-delete-orphaned').prop('disabled', true).html('<span class="progress-indicator"></span>Deleting...');

        $.ajax({
            url: orphanedACFMedia.ajaxUrl,
            type: 'POST',
            data: {
                action: 'bulk_delete_orphaned_media',
                attachment_ids: selectedIds,
                nonce: orphanedACFMedia.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;

                    // Handle successful deletions
                    if (data.deleted_count > 0) {
                        selectedIds.forEach(function(id) {
                            const $row = $(`.media-item-row[data-attachment-id="${id}"]`);
                            if ($row.hasClass('deleting')) {
                                $row.addClass('deleted');
                                $row.find('.delete-single-media').html('<span class="dashicons dashicons-yes"></span> Deleted');

                                // Remove from orphanedMediaData array
                                orphanedMediaData = orphanedMediaData.filter(media => media.id != id);
                            }
                        });
                    }

                    // Update count
                    updateOrphanedCount();

                    // Show detailed message about safety blocks
                    let detailedMessage = data.message;
                    if (data.blocked_files && data.blocked_files.length > 0) {
                        detailedMessage += '<br><br><strong>Files protected by safety checks:</strong><ul>';
                        data.blocked_files.forEach(function(file) {
                            detailedMessage += `<li>${file.filename} (Used in: ${file.usage.join(', ')})</li>`;
                        });
                        detailedMessage += '</ul>';
                    }

                    // Remove deleted rows after delay
                    setTimeout(function() {
                        selectedIds.forEach(function(id) {
                            const $row = $(`.media-item-row[data-attachment-id="${id}"]`);
                            if ($row.hasClass('deleted')) {
                                $row.fadeOut(300, function() {
                                    $(this).remove();

                                    // Check if no media left
                                    if ($('#orphaned-media-list tr').length === 0) {
                                        showNoOrphanedMedia();
                                        $('#orphaned-media-results').hide();
                                    }
                                });
                            }
                        });

                        updateSelectAllCheckbox();
                        updateBulkDeleteButton();
                    }, 1000);

                    showNotice(data.safety_blocked > 0 ? 'warning' : 'success', detailedMessage);
                } else {
                    // Restore rows on failure
                    selectedIds.forEach(function(id) {
                        const $row = $(`.media-item-row[data-attachment-id="${id}"]`);
                        $row.removeClass('deleting');
                        $row.find('.delete-single-media').removeClass('deleting').html('<span class="dashicons dashicons-trash"></span> Delete');
                    });

                    showNotice('error', response.data.message || 'Failed to delete selected media files.');
                }
            },
            error: function() {
                // Restore rows on error
                selectedIds.forEach(function(id) {
                    const $row = $(`.media-item-row[data-attachment-id="${id}"]`);
                    $row.removeClass('deleting');
                    $row.find('.delete-single-media').removeClass('deleting').html('<span class="dashicons dashicons-trash"></span> Delete');
                });

                showNotice('error', 'An error occurred while deleting the media files.');
            },
            complete: function() {
                $('#bulk-delete-orphaned').prop('disabled', false).text('Delete Selected');
            }
        });
    }

    function updateSelectAllCheckbox() {
        const $visibleCheckboxes = $('.media-checkbox').filter(':visible');
        const $visibleCheckedBoxes = $('.media-checkbox:checked').filter(':visible');
        const $selectAll = $('#cb-select-all');

        if ($visibleCheckboxes.length === 0) {
            $selectAll.prop('checked', false);
        } else if ($visibleCheckedBoxes.length === $visibleCheckboxes.length) {
            $selectAll.prop('checked', true);
        } else {
            $selectAll.prop('checked', false);
        }
    }

    function updateBulkDeleteButton() {
        const selectedCount = $('.media-checkbox:checked').length;
        const safeSelectedCount = $('.media-checkbox:checked:not(:disabled)').length;
        const $bulkButton = $('#bulk-delete-orphaned');

        if (selectedCount > 0 && safeSelectedCount > 0) {
            let buttonText = `Delete Selected (${safeSelectedCount}`;
            if (selectedCount !== safeSelectedCount) {
                buttonText += ` of ${selectedCount} safe`;
            }
            buttonText += ')';
            $bulkButton.prop('disabled', false).text(buttonText);
        } else if (selectedCount > 0 && safeSelectedCount === 0) {
            $bulkButton.prop('disabled', true).text('Delete Selected (None safe to delete)');
        } else {
            $bulkButton.prop('disabled', true).text('Delete Selected');
        }
    }

    function updateOrphanedCount() {
        const count = orphanedMediaData.length;
        $('.orphaned-count').text(`${count} orphaned media file${count !== 1 ? 's' : ''} found`);
    }

    function showNoOrphanedMedia() {
        const message = `
            <div class="no-orphaned-media">
                <h3>✅ Great! No orphaned media files found.</h3>
                <p>All your media files are being used in ACF fields.</p>
            </div>
        `;

        if ($('.no-orphaned-media').length === 0) {
            $('#orphaned-media-results').after(message);
        }
    }

    function showNotice(type, message) {
        // Remove existing notices
        $('.notice-orphaned-media').remove();

        const noticeClass = `notice notice-${type} notice-orphaned-media is-dismissible`;
        const notice = `
            <div class="${noticeClass}">
                <p>${escapeHtml(message)}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `;

        $('.wrap h1').after(notice);

        // Auto-dismiss success notices after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $('.notice-orphaned-media .notice-dismiss').trigger('click');
            }, 5000);
        }

        // Handle dismiss button
        $('.notice-orphaned-media .notice-dismiss').on('click', function() {
            $(this).closest('.notice').fadeOut(300, function() {
                $(this).remove();
            });
        });
    }

    function deleteAllSafeMedia() {
        // Check if there are any orphaned media files to delete
        if (orphanedMediaData.length === 0) {
            showNotice('warning', 'No orphaned media files found. Please run a scan first to identify files that can be deleted.');
            return;
        }

        // Check if there are any safe files to delete
        const safeFiles = orphanedMediaData.filter(media => media.is_truly_orphaned);
        if (safeFiles.length === 0) {
            showNotice('info', 'No safe-to-delete files found in the current scan results.');
            return;
        }

        const confirmMessage = `This will delete ALL ${safeFiles.length} safe-to-delete media files across your entire website. This action cannot be undone. Are you absolutely sure you want to continue?`;

        if (!confirm(confirmMessage)) {
            return;
        }

        // Show progress modal
        showDeleteProgress();

        const $button = $('#delete-all-safe');
        $button.addClass('processing').prop('disabled', true).text('Processing...');

        let totalDeleted = 0;
        let totalFailed = 0;
        let batchOffset = 0;

        function processBatch() {
            $.ajax({
                url: orphanedACFMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_all_safe_media',
                    batch_size: 10,
                    batch_offset: batchOffset,
                    nonce: orphanedACFMedia.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        totalDeleted += data.deleted_count;
                        totalFailed += data.failed_count;

                        // Update progress
                        updateDeleteProgress({
                            progress: data.progress_percent,
                            deleted: totalDeleted,
                            failed: totalFailed,
                            message: data.message,
                            isComplete: !data.has_more
                        });

                        if (data.has_more) {
                            batchOffset = data.next_offset;
                            // Continue processing with a small delay
                            setTimeout(processBatch, 500);
                        } else {
                            // Process complete
                            completeDeleteAll(totalDeleted, totalFailed);
                        }
                    } else {
                        showNotice('error', response.data.message || 'Failed to delete media files.');
                        hideDeleteProgress();
                        resetDeleteAllButton();
                    }
                },
                error: function() {
                    showNotice('error', 'An error occurred while deleting media files.');
                    hideDeleteProgress();
                    resetDeleteAllButton();
                }
            });
        }

        // Start processing
        processBatch();
    }

    function showDeleteProgress() {
        const progressHtml = `
            <div class="delete-progress" id="delete-progress-modal">
                <h4>Deleting Safe Media Files</h4>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="progress-text">Initializing...</div>
                <div class="progress-stats">
                    <div class="progress-stat deleted">
                        <strong class="deleted-count">0</strong><br>
                        <small>Deleted</small>
                    </div>
                    <div class="progress-stat failed">
                        <strong class="failed-count">0</strong><br>
                        <small>Failed</small>
                    </div>
                </div>
            </div>
        `;

        $('body').append(progressHtml);
    }

    function updateDeleteProgress(data) {
        const $progress = $('#delete-progress-modal');

        $progress.find('.progress-fill').css('width', data.progress + '%');
        $progress.find('.progress-text').text(data.message);
        $progress.find('.deleted-count').text(data.deleted);
        $progress.find('.failed-count').text(data.failed);

        if (data.isComplete) {
            $progress.find('.progress-text').text('Deletion complete! Refreshing results...');
        }
    }

    function hideDeleteProgress() {
        $('#delete-progress-modal').fadeOut(300, function() {
            $(this).remove();
        });
    }

    function completeDeleteAll(totalDeleted, totalFailed) {
        setTimeout(function() {
            hideDeleteProgress();
            resetDeleteAllButton();

            let message = '';
            if (totalDeleted > 0) {
                message += `Successfully deleted ${totalDeleted} media files. `;
            }
            if (totalFailed > 0) {
                message += `${totalFailed} files failed to delete. `;
            }

            showNotice(totalDeleted > 0 ? 'success' : 'warning', message || 'No files were deleted.');

            // Refresh the current page results
            scanOrphanedMedia(currentPage);
        }, 2000);
    }

    function resetDeleteAllButton() {
        $('#delete-all-safe').removeClass('processing').prop('disabled', false).text('Delete All Safe Files');
    }

    function updateScanProgress(percentage, message) {
        $('#scan-progress-fill').css('width', percentage + '%');
        $('#scan-progress-text').text(message);
    }

    function updateDeleteAllSafeButton(totalSafeFiles) {
        const $button = $('#delete-all-safe');
        if (totalSafeFiles > 0) {
            $button.prop('disabled', false).text(`Delete All Safe Files (${totalSafeFiles})`);
        } else {
            // Check if we have any orphaned media data at all
            if (orphanedMediaData.length === 0) {
                $button.prop('disabled', true).text('Delete All Safe Files (Scan Required)');
            } else {
                $button.prop('disabled', true).text('Delete All Safe Files (0)');
            }
        }
    }

    function openMediaLibrary(attachmentId) {
        // Open WordPress Media Library with the specific attachment
        // This creates a URL that will open the media library and highlight the specific file
        const mediaUrl = `${window.location.protocol}//${window.location.host}/wp-admin/upload.php?item=${attachmentId}`;
        window.open(mediaUrl, '_blank');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})(jQuery);