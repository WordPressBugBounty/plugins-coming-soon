/**
 * Admin JavaScript for SeedProd WordPress-native pages
 */

(function( $ ) {
	'use strict';

	$( document ).ready( function() {
		
		// Fix menu expansion for hidden pages
		(function() {
			var currentPage = new URLSearchParams(window.location.search).get('page');
			var $seedprodMenu = $('#toplevel_page_seedprod_lite');
			
			if (!$seedprodMenu.length) return;
			
			// Handle hidden pages that need menu expansion
			switch(currentPage) {
				case 'seedprod_lite_theme_kits_selection':
					// Expand menu and highlight Website Builder
					$seedprodMenu.removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
					$seedprodMenu.find('.wp-submenu').show();
					$seedprodMenu.find('a[href="admin.php?page=seedprod_lite_website_builder"]').parent().addClass('current');
					break;
					
				case 'seedprod_lite_template_selection':
					// Expand menu and highlight Landing Pages
					$seedprodMenu.removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
					$seedprodMenu.find('.wp-submenu').show();
					$seedprodMenu.find('.wp-submenu li').removeClass('current');
					$seedprodMenu.find('a[href="admin.php?page=seedprod_lite_landing_pages"]').parent().addClass('current');
					break;
			}
		})();
		
		// Handle notification dismissal
		$(document).on('click', '.seedprod-notification-dismiss', function(e) {
			e.preventDefault();
			
			var $notification = $(this).closest('.seedprod-notification-bar');
			var notificationId = $notification.data('id');
			
			// Fade out the notification immediately for better UX
			$notification.fadeOut(300);
			
			// Make AJAX call to dismiss notification permanently
			$.post(seedprod_admin.ajax_url, {
				action: 'seedprod_lite_notification_dismiss',
				id: notificationId,
				_wpnonce: seedprod_admin.notification_dismiss_nonce
			}, function(response) {
				// Notification already hidden, no further action needed
			}).fail(function() {
				// If dismiss fails, show notification again
				$notification.fadeIn(300);
			});
		});
		
		// Dashboard-specific functionality (exclude landing pages)
		if ( $( '.seedprod-dashboard-page' ).length && ! $( '.seedprod-landing-pages-page' ).length ) {
			initDashboard();
		}
		
		// Landing Pages-specific functionality
		if ( $( '.seedprod-landing-pages-page' ).length ) {
			initLandingPages();
		}
		
		// Template Selection-specific functionality
		if ( $( '.seedprod-template-selection-page' ).length ) {
			initTemplateSelection();
		}
		
		// Theme Kits Selection-specific functionality
		if ( $( '.seedprod-theme-kits-selection-page' ).length ) {
			initThemeKitsSelection();
		}
		
		// Settings-specific functionality
		if ( $( '.seedprod-settings-page' ).length ) {
			initSettings();
		}
		
		// Subscribers-specific functionality
		if ( $( '#seedprod-subscribers-tab' ).length ) {
			initSubscribers();
		}
		
		// AI Themes external link handler
		$('a[href*="seedprod_lite_ai_themes"]').on('click', function(e) {
			e.preventDefault();
			window.open(seedprod_admin.urls.ai_theme_builder, '_blank');
		});
		
	});
	
	/**
	 * Initialize dashboard functionality
	 */
	function initDashboard() {
		
		// License activation handler
		$('#seedprod-license-form').on('submit', function(e) {
			e.preventDefault();
			
			var $form = $(this);
			var $button = $form.find('button');
			var $message = $('#seedprod-license-message');
			var licenseKey = $('#seedprod-license-key').val();
			
			if (!licenseKey) {
				$message.html('<div class="seedprod-message-error">' + seedprod_admin.strings.license_empty + '</div>');
				return;
			}
			
			// Show loading state
			$button.prop('disabled', true);
			$button.find('.button-text').hide();
			$button.find('.button-spinner').css('display', 'inline-block');
			$message.empty();
			
			// Make AJAX call to validate license
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_save_api_key',
					api_key: licenseKey,
					_wpnonce: seedprod_admin.nonce
				},
				success: function(response) {
					if (response.status === 'true') {
						$message.html('<div class="seedprod-message-success">' +
							'<span class="dashicons dashicons-yes"></span> ' +
							seedprod_admin.strings.license_success + '</div>');
						// Keep the button in success state
						$button.find('.button-spinner').hide();
						$button.find('.button-text').show();
						setTimeout(function() {
							// Reload with activation success parameter
							window.location.href = window.location.pathname + '?page=seedprod_lite&activated=true';
						}, 1500);
					} else {
						var errorMsg = response.msg || seedprod_admin.strings.license_invalid;

						// Show WordPress admin notice for error
						var noticeClass = 'notice-error';
						var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible seedprod-admin-notice"><p>' + errorMsg + '</p></div>');

						// Insert notice at top of dashboard container
						if ($('.seedprod-dashboard-container').length) {
							$('.seedprod-dashboard-container').prepend($notice);
						} else {
							// Fallback - insert after header
							$('.seedprod-header').after($notice);
						}

						// Make dismissible
						$notice.on('click', '.notice-dismiss', function() {
							$(this).remove();
						});

						// Also show inline error message
						$message.html('<div class="seedprod-message-error">' +
							'<span class="dashicons dashicons-warning"></span> ' +
							errorMsg + '</div>');

						// Reset button on error
						$button.prop('disabled', false);
						$button.find('.button-spinner').hide();
						$button.find('.button-text').show();
					}
				},
				error: function() {
					$message.html('<div class="seedprod-message-error">' + 
						'<span class="dashicons dashicons-warning"></span> ' +
						seedprod_admin.strings.license_error + '</div>');
					// Reset button on error
					$button.prop('disabled', false);
					$button.find('.button-spinner').hide();
					$button.find('.button-text').show();
				}
			});
		});
		
		// Toggle switches for enable/disable modes
		$('.seedprod-toggle').on('change', function() {
			var $toggle = $(this);
			var mode = $toggle.data('mode');
			var isChecked = $toggle.is(':checked');
			
			// Disable toggle during AJAX
			$toggle.prop('disabled', true);
			
			// Get current settings
			var settings = {
				enable_coming_soon_mode: $('.seedprod-toggle[data-mode="coming_soon"]').is(':checked'),
				enable_maintenance_mode: $('.seedprod-toggle[data-mode="maintenance"]').is(':checked'),
				enable_login_mode: $('.seedprod-toggle[data-mode="login"]').is(':checked'),
				enable_404_mode: $('.seedprod-toggle[data-mode="404"]').is(':checked')
			};
			
			// Apply the change
			if (mode === 'coming_soon') {
				settings.enable_coming_soon_mode = isChecked;
				// If enabling coming soon, disable maintenance
				if (isChecked && settings.enable_maintenance_mode) {
					settings.enable_maintenance_mode = false;
					$('.seedprod-toggle[data-mode="maintenance"]').prop('checked', false);
					updateStatusBadge('maintenance', false);
				}
			} else if (mode === 'maintenance') {
				settings.enable_maintenance_mode = isChecked;
				// If enabling maintenance, disable coming soon
				if (isChecked && settings.enable_coming_soon_mode) {
					settings.enable_coming_soon_mode = false;
					$('.seedprod-toggle[data-mode="coming_soon"]').prop('checked', false);
					updateStatusBadge('coming_soon', false);
				}
			} else if (mode === 'login') {
				settings.enable_login_mode = isChecked;
			} else if (mode === '404') {
				settings.enable_404_mode = isChecked;
			}
			
			// Save settings via AJAX
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_save_settings',
					settings: JSON.stringify(settings),
					_wpnonce: seedprod_admin.nonce
				},
				success: function(response) {
					// Update status badge
					updateStatusBadge(mode, isChecked);
					
					// Refresh page for coming soon and maintenance modes to update admin bar notice
					if (mode === 'coming_soon' || mode === 'maintenance') {
						// Show success state briefly before refresh
						setTimeout(function() {
							window.location.reload();
						}, 500);
					} else {
						// Re-enable toggle for other modes
						$toggle.prop('disabled', false);
					}
				},
				error: function() {
					// Revert toggle on error
					$toggle.prop('checked', !isChecked);
					$toggle.prop('disabled', false);
					alert(seedprod_admin.strings.settings_error);
				}
			});
		});
		
		// Copy to clipboard functionality
		$('#seedprod-copy-system-info').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var textarea = document.getElementById('seedprod-system-info-text');
			
			if (!textarea) return;
			
			// Try modern clipboard API first (works on HTTPS)
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(textarea.value).then(function() {
					$button.text(seedprod_admin.strings.copied || 'Copied!');
					setTimeout(function() {
						$button.text(seedprod_admin.strings.copy_to_clipboard || 'Copy to Clipboard');
					}, 2000);
				}).catch(function() {
					// Fallback to old method
					copyUsingExecCommand();
				});
			} else {
				// Use fallback for HTTP/localhost
				copyUsingExecCommand();
			}
			
			function copyUsingExecCommand() {
				// Select the text
				textarea.select();
				textarea.setSelectionRange(0, 99999); // For mobile devices
				
				try {
					// Copy using the old execCommand
					var successful = document.execCommand('copy');
					if (successful) {
						$button.text(seedprod_admin.strings.copied || 'Copied!');
						setTimeout(function() {
							$button.text(seedprod_admin.strings.copy_to_clipboard || 'Copy to Clipboard');
						}, 2000);
					}
				} catch (err) {
					console.error('Failed to copy text: ', err);
				}
				
				// Deselect the text
				if (window.getSelection) {
					window.getSelection().removeAllRanges();
				}
			}
		});
		
		// Plugin installation/activation handlers
		$('.seedprod-plugin-button').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var $item = $button.closest('.seedprod-plugin-item, .seedprod-plugin-card');
			var pluginKey = $item.data('plugin');
			var pluginSlug = $button.data('plugin-slug');
			var pluginId = $button.data('plugin-id');
			var status = parseInt($button.data('status'));
			
			// Prevent double clicks
			if ($button.prop('disabled')) {
				return;
			}
			
			// Show loading state (WordPress native spinner)
			$button.prop('disabled', true);
			// Remove any attention-grabbing indicators when user takes action
			$button.removeClass('seedprod-needs-activation');
			var originalText = $button.text();
			$button.data('original-text', originalText);
			
			// Change text and add WordPress native spinner
			var loadingText = '';
			if (status === 0) {
				loadingText = seedprod_admin.strings.installing || 'Installing...';
			} else if (status === 1) {
				loadingText = seedprod_admin.strings.deactivating || 'Deactivating...';
			} else if (status === 2) {
				loadingText = seedprod_admin.strings.activating || 'Activating...';
			}
			
			var spinner = $('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>');
			$button.empty().append(spinner).append(loadingText);
			
			// Determine action based on status
			var action = '';
			if (status === 0) {
				action = 'seedprod_lite_v2_install_plugin';
			} else if (status === 1) {
				action = 'seedprod_lite_v2_deactivate_plugin';
			} else if (status === 2) {
				action = 'seedprod_lite_v2_activate_plugin';
			}
			
			// Prepare data - use plugin_id for install, plugin slug for activate/deactivate
			var data = {
				action: action,
				_wpnonce: seedprod_admin.nonce
			};

			if (status === 0) {
				// Installing - send plugin ID (not URL for security)
				data.plugin_id = pluginId;
			} else {
				// Activating/deactivating - send plugin slug
				data.plugin = pluginSlug;
			}

			// Set timeout for stuck installations (30 seconds for install, 10 seconds for activate/deactivate)
			var timeoutDuration = (status === 0) ? 30000 : 10000;
			var requestTimeout = setTimeout(function() {
				// If we get here, the request is stuck
				// Reset button state
				$button.prop('disabled', false);
				$button.find('.spinner').remove();
				$button.text(originalText);

				// Show timeout error
				var timeoutMsg = (status === 0) ?
					(seedprod_admin.strings.plugin_install_timeout || 'Installation is taking longer than expected. Please try again or check your server logs.') :
					(seedprod_admin.strings.plugin_timeout || 'Operation timed out. Please try again.');
				showNotice('error', timeoutMsg);
			}, timeoutDuration);

			// Make AJAX request
			$.post(seedprod_admin.ajax_url, data, function(response) {
				// Clear the timeout since request completed
				clearTimeout(requestTimeout);
				if (response.success) {
					// Update button based on new status
					if (status === 0) {
						// Was not installed, now inactive - keep as primary button for activate
						$button.data('status', 2);
						// Update status text
						$item.find('.seedprod-plugin-status strong').text(seedprod_admin.strings.plugin_inactive);
						// Add attention-grabbing indicator since user needs to activate
						$button.addClass('seedprod-needs-activation');
						// Remove the indicator after 10 seconds
						setTimeout(function() {
							$button.removeClass('seedprod-needs-activation');
						}, 10000);
						showNotice('success', response.data.message || seedprod_admin.strings.plugin_installed);
					} else if (status === 1) {
						// Was active, now inactive - switch to primary button for activate
						$button.data('status', 2);
						$button.removeClass('seedprod-button-secondary').addClass('button-primary seedprod-button-primary');
						// Update status text
						$item.find('.seedprod-plugin-status strong').text(seedprod_admin.strings.plugin_inactive);
						showNotice('success', response.data || seedprod_admin.strings.plugin_deactivated);
						// Reload page after deactivation to ensure UI is fully updated
						setTimeout(function() {
							window.location.reload();
						}, 1000);
					} else if (status === 2) {
						// Was inactive, now active - switch to secondary button for deactivate
						$button.data('status', 1);
						$button.removeClass('button-primary seedprod-button-primary').addClass('seedprod-button-secondary');
						// Update status text
						$item.find('.seedprod-plugin-status strong').text(seedprod_admin.strings.plugin_active);
						showNotice('success', response.data || seedprod_admin.strings.plugin_activated);
						// Reload page after activation to ensure UI is fully updated
						setTimeout(function() {
							window.location.reload();
						}, 1000);
					}
				} else {
					var errorMsg = response.data || seedprod_admin.strings.plugin_error;
					showNotice('error', errorMsg);
				}
			}).fail(function() {
				// Clear the timeout since request completed (even if failed)
				clearTimeout(requestTimeout);
				showNotice('error', seedprod_admin.strings.plugin_network_error);
			}).always(function() {
				// Clear timeout in case it's still running
				clearTimeout(requestTimeout);
				// Reset button state (remove WordPress native spinner)
				$button.prop('disabled', false);
				$button.find('.spinner').remove();
				// Update button text based on the new status
				var newStatus = $button.data('status');
				var newText = '';
				if (newStatus === 0) {
					newText = seedprod_admin.strings.plugin_install;
				} else if (newStatus === 1) {
					newText = seedprod_admin.strings.plugin_deactivate;
				} else if (newStatus === 2) {
					newText = seedprod_admin.strings.plugin_activate;
				}
				$button.text(newText);
			});
		});
		
		/**
		 * Update status badge for toggles
		 */
		function updateStatusBadge(mode, isActive) {
			var $item = $('.seedprod-toggle[data-mode="' + mode + '"]').closest('.seedprod-setup-item');
			var $badge = $item.find('.seedprod-status-badge');
			var $icon = $item.find('.seedprod-setup-icon');
			
			if ($badge.length === 0) {
				// Create badge if it doesn't exist
				$badge = $('<span class="seedprod-status-badge"></span>');
				$item.find('.seedprod-setup-item-controls').append($badge);
			}
			
			if (isActive) {
				$badge.removeClass('seedprod-status-inactive')
					.addClass('seedprod-status-active')
					.text(seedprod_admin.strings.status_active);
				$icon.addClass('active');
			} else {
				$badge.removeClass('seedprod-status-active')
					.addClass('seedprod-status-inactive')
					.text(seedprod_admin.strings.status_inactive);
				$icon.removeClass('active');
			}
		}
		
		/**
		 * Helper function to show notices
		 */
		function showNotice(type, message) {
			var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
			var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible seedprod-admin-notice"><p>' + message + '</p></div>');
			
			// Insert notice in appropriate container based on page
			if ($('.seedprod-dashboard-container').length) {
				// Dashboard page
				$('.seedprod-dashboard-container').prepend($notice);
			} else if ($('.seedprod-settings-container').length) {
				// Settings page
				$('.seedprod-settings-container').prepend($notice);
			} else {
				// Fallback - insert after any SeedProd header
				$('.seedprod-header').after($notice);
			}
			
			// Auto dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
			
			// Make dismissible
			$notice.on('click', '.notice-dismiss', function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			});
			
			// Add dismiss button if not present
			if (!$notice.find('.notice-dismiss').length) {
				$notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + 
					seedprod_admin.strings.dismiss_notice + '</span></button>');
			}
		}
	}
	
	/**
	 * Initialize theme kits selection functionality
	 */
	function initThemeKitsSelection() {
		var themeKitsState = {
			currentTab: 'all-templates',
			search: '',
			filter: 'all',
			sort: '',
			themes: {},
			favorites: [],
			currentPage: 1,
			totalPages: 1,
			searchTimeout: null
		};
		
		// Initialize based on current tab
		if (typeof seedprodThemeKitsData !== 'undefined') {
			themeKitsState.currentTab = seedprodThemeKitsData.activeTab || 'all-templates';
		}
		
		// Load themes immediately (we're already in document ready)
		if (themeKitsState.currentTab === 'all-templates') {
			loadThemes();
		} else if (themeKitsState.currentTab === 'favorite-templates') {
			loadFavoriteThemes();
		}
		
		// Search functionality with debouncing
		$('#seedprod-template-search').on('keyup', function() {
			var searchTerm = $(this).val();
			themeKitsState.search = searchTerm;
			
			// When searching, reset filter to 'all' to search across all themes
			if (searchTerm && themeKitsState.filter !== 'all') {
				themeKitsState.filter = 'all';
				$('.seedprod-filter-pill').removeClass('active');
				$('.seedprod-filter-pill[data-filter="all"]').addClass('active');
			}
			
			// Clear existing timeout
			if (themeKitsState.searchTimeout) {
				clearTimeout(themeKitsState.searchTimeout);
			}
			
			// Set new timeout for 300ms delay
			themeKitsState.searchTimeout = setTimeout(function() {
				themeKitsState.currentPage = 1;
				if (themeKitsState.currentTab === 'all-templates') {
					loadThemes();
				} else if (themeKitsState.currentTab === 'favorite-templates') {
					loadFavoriteThemes();
				}
			}, 300);
		});
		
		// Filter functionality
		$('.seedprod-filter-pill').on('click', function() {
			var filter = $(this).data('filter');
			themeKitsState.filter = filter;
			themeKitsState.currentPage = 1;
			
			// Clear search when selecting a filter
			themeKitsState.search = '';
			$('#seedprod-template-search').val('');
			
			// Update active states
			$('.seedprod-filter-pill').removeClass('active');
			$(this).addClass('active');
			
			loadThemes();
		});
		
		// Sort functionality
		$('#seedprod-theme-sort').on('change', function() {
			themeKitsState.sort = $(this).val();
			themeKitsState.currentPage = 1;
			loadThemes();
		});
		
		// Pagination functionality
		$('#seedprod-first-page').on('click', function() {
			if (!$(this).prop('disabled')) {
				themeKitsState.currentPage = 1;
				loadThemes();
			}
		});
		
		$('#seedprod-prev-page').on('click', function() {
			if (!$(this).prop('disabled') && themeKitsState.currentPage > 1) {
				themeKitsState.currentPage--;
				loadThemes();
			}
		});
		
		$('#seedprod-next-page').on('click', function() {
			if (!$(this).prop('disabled') && themeKitsState.currentPage < themeKitsState.totalPages) {
				themeKitsState.currentPage++;
				loadThemes();
			}
		});
		
		$('#seedprod-last-page').on('click', function() {
			if (!$(this).prop('disabled')) {
				themeKitsState.currentPage = themeKitsState.totalPages;
				loadThemes();
			}
		});
		
		// Template hover effects (reuse the same hover logic as landing pages)
		$(document).on('mouseenter', '.seedprod-theme-kits-grid .seedprod-template-card', function() {
			$(this).find('.seedprod-template-overlay').show();
		});
		
		$(document).on('mouseleave', '.seedprod-theme-kits-grid .seedprod-template-card', function() {
			// Don't hide overlay if card is in processing state
			if (!$(this).hasClass('coming-sooncessing')) {
				$(this).find('.seedprod-template-overlay').hide();
			}
		});
		
		// Theme selection (import)
		$(document).on('click', '.seedprod-theme-kits-grid .seedprod-template-select', function(e) {
			e.preventDefault();
			var $button = $(this);
			var themeId = $(this).data('template-id');
			var themeName = $(this).data('template-name');
			
			// Get total theme pages count first
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_get_total_theme_pages',
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					var totalThemePages = response.data ? response.data.count : 0;
					
					// If there are existing theme pages, ask if they want to delete them
					if (totalThemePages > 0) {
						if (confirm(seedprod_admin.strings.theme_import_confirm.replace('%d', totalThemePages))) {
							// Delete existing theme pages first
							deleteThemePages(function() {
								// Then import the new theme
								importTheme(themeId, themeName, $button);
							});
						} else {
							// User cancelled - do nothing
							return;
						}
					} else {
						// No existing pages, just import
						importTheme(themeId, themeName, $button);
					}
				},
				error: function() {
					// On error, just try to import anyway
					importTheme(themeId, themeName, $button);
				}
			});
		});
		
		// Function to delete theme pages
		function deleteThemePages(callback) {
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_delete_theme_pages',
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						if (callback) callback();
					} else {
						alert(seedprod_admin.strings.theme_delete_error.replace('%s', response.data || seedprod_admin.strings.unknown_error));
					}
				},
				error: function() {
					alert(seedprod_admin.strings.theme_delete_general_error);
				}
			});
		}
		
		// Function to import theme
		function importTheme(themeId, themeName, $button) {
			var $templateCard = $button.closest('.seedprod-template-card');
			var $overlay = $button.closest('.seedprod-template-overlay');

			// Keep overlay visible during processing
			$templateCard.addClass('coming-sooncessing');
			$overlay.addClass('coming-sooncessing-active');

			// Show loading state
			$button.prop('disabled', true);
			$button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-update').addClass('seedprod-spin');
			
			// Show importing message
			showNotice(seedprod_admin.strings.theme_import_starting.replace('%s', themeName), 'info');
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_import_theme_request',
					theme_id: themeId,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						showNotice(seedprod_admin.strings.theme_import_success.replace('%s', themeName), 'success');
						// Redirect to theme templates page after short delay
						setTimeout(function() {
							window.location.href = seedprod_admin.admin_url + 'admin.php?page=seedprod_lite_website_builder';
						}, 2000);
					} else {
						showNotice(seedprod_admin.strings.theme_import_error.replace('%s', response.data || seedprod_admin.strings.unknown_error), 'error');
						// Reset button state
						$templateCard.removeClass('coming-sooncessing');
						$overlay.removeClass('coming-sooncessing-active');
						$button.prop('disabled', false);
						$button.find('.dashicons').removeClass('dashicons-update seedprod-spin').addClass('dashicons-yes');
					}
				},
				error: function() {
					showNotice(seedprod_admin.strings.template_load_error, 'error');
					// Reset button state
					$templateCard.removeClass('coming-sooncessing');
					$overlay.removeClass('coming-sooncessing-active');
					$button.prop('disabled', false);
					$button.find('.dashicons').removeClass('dashicons-update seedprod-spin').addClass('dashicons-yes');
				}
			});
		}
		
		// Theme preview
		$(document).on('click', '.seedprod-theme-kits-grid .seedprod-template-preview', function(e) {
			e.preventDefault();
			var themeId = $(this).data('template-id');
			// Open preview in new tab (matching Vue.js implementation)
			window.open('https://preview.seedprod.com/' + themeId, '_blank');
			
			// Commented out iframe modal implementation for now
			// var themeName = $(this).data('template-name') || 'Theme Kit Preview';
			// previewThemeKit(themeId, themeName);
		});
		
		// Close preview modal for theme kits - commented out for now
		// $(document).on('click', '#seedprod-template-preview-modal .seedprod-modal-close, #seedprod-template-preview-modal .seedprod-modal-overlay', function() {
		// 	// Check if we're on theme kits page
		// 	if ($('.seedprod-theme-kits-selection-page').length > 0) {
		// 		closeThemePreviewModal();
		// 	}
		// });
		
		// Favorite toggle (matching landing page templates)
		$(document).on('click', '.seedprod-theme-kits-grid .seedprod-favorite-toggle', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $toggle = $(this);
			var templateId = $toggle.data('template-id');
			var isFavorited = $toggle.hasClass('seedprod-favorited');
			var method = isFavorited ? 'detach' : 'attach';
			
			// Optimistically update UI
			$toggle.toggleClass('seedprod-favorited');
			
			// Save favorite state via AJAX
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_toggle_favorite_theme',
					template_id: templateId,
					method: method,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (!response.success) {
						// Revert on error
						$toggle.toggleClass('seedprod-favorited');
						console.error('Failed to toggle favorite:', response.data);
					}
				},
				error: function() {
					// Revert on error
					$toggle.toggleClass('seedprod-favorited');
					console.error('Failed to toggle favorite');
				}
			});
		});
		
		/**
		 * Load themes from API
		 */
		function loadThemes() {
			var $grid = $('#all-themes-grid');
			showLoadingSpinner($grid);
			
			// Build API URL
			var apiUrl = seedprodThemeKitsData.apiUrl + 'themes?page=' + themeKitsState.currentPage;
			
			// Map filter to category ID (10 for WooCommerce)
			var categoryId = themeKitsState.filter === 'woocommerce' ? '10' : '';
			
			// Build request data
			var requestData = {
				action: 'seedprod_lite_v2_get_theme_kits',
				nonce: seedprodThemeKitsData.nonce,
				api_url: apiUrl,
				filter: 'themes',
				category: categoryId,
				search: themeKitsState.search,
				sort: themeKitsState.sort
			};
			
			$.ajax({
				url: seedprodThemeKitsData.ajaxUrl,
				type: 'POST',
				data: requestData,
				success: function(response) {
					if (response.success && response.data) {
						// For All Themes, pagination info is in data.templates
						if (response.data.templates) {
							themeKitsState.themes = response.data.templates;
							renderThemes(response.data, false); // false = not favorites tab
							updatePagination(response.data.templates); // Pass templates object with pagination
						} else {
							// Fallback for unexpected structure
							themeKitsState.themes = response.data;
							renderThemes(response.data, false);
							updatePagination(response.data);
						}
					} else {
						showError($grid, response.data || seedprod_admin.strings.error_loading_templates);
					}
				},
				error: function() {
					showError($grid, seedprod_admin.strings.error_loading_templates);
				}
			});
		}
		
		/**
		 * Load favorite themes from API
		 */
		function loadFavoriteThemes() {
			var $grid = $('#favorite-themes-grid');
			showLoadingSpinner($grid);
			
			// Build API URL for favorites - same as All Themes but we'll pass filter separately
			var apiUrl = seedprodThemeKitsData.apiUrl + 'themes?page=' + themeKitsState.currentPage;
			
			var requestData = {
				action: 'seedprod_lite_v2_get_theme_kits',
				nonce: seedprodThemeKitsData.nonce,
				api_url: apiUrl,
				filter: 'favorites',
				search: themeKitsState.search
			};
			$.ajax({
				url: seedprodThemeKitsData.ajaxUrl,
				type: 'POST',
				data: requestData,
				success: function(response) {
					if (response.success && response.data) {
						// For favorites, the API returns themes in data.data (like Vue component line 1126)
						var data = response.data;
						
						// Check if we have themes in the expected location
						if (data.data && Array.isArray(data.data)) {
							if (data.data.length > 0) {
								// Mark all as favorited (like Vue component line 1127-1129)
								data.data.forEach(function(v) {
									v.favorited = true;
								});
							}
							// Always render, even if empty
							renderThemes(data, true);
							updatePagination(data);
						} else {
							// Show empty state
							renderThemes({data: []}, true);
							updatePagination({data: [], current_page: 1, last_page: 1});
						}
					} else {
						showError($grid, response.data || seedprod_admin.strings.error_loading_templates);
					}
				},
				error: function() {
					showError($grid, seedprod_admin.strings.error_loading_templates);
				}
			});
		}
		
		/**
		 * Render themes in the grid
		 */
		function renderThemes(data, isFavorites) {
			var gridId = isFavorites ? '#favorite-themes-grid' : '#all-themes-grid';
			var $grid = $(gridId);
			
			$grid.empty();
			
			// Handle response structure exactly like Vue component
			var themes = [];
			var favs = [];
			
			if (isFavorites === true) {
				// Favorites tab: themes come from data.data
				themes = data.data || [];
				// These are already favorited (set in loadFavoriteThemes)
			} else {
				// All themes tab: exactly like Vue component (line 1074-1079)
				if (data && data.templates && data.templates.data) {
					themes = data.templates.data;
					favs = data.favs || [];
					
					// Set favorited exactly like Vue component does (line 1077-1079)
					themes.forEach(function(v) {
						v.favorited = favs.includes(v.id);
					});
				} else if (data && data.templates) {
					themes = data.templates;
					favs = data.favs || [];
					
					themes.forEach(function(v) {
						v.favorited = favs.includes(v.id);
					});
				}
			}
			
			
			if (!themes || themes.length === 0) {
				$grid.html('<div class="seedprod-no-templates">' + 
					'<p>' + seedprod_admin.strings.no_templates_found + '</p></div>');
				$('.seedprod-themes-pagination').hide();
				return;
			}
			
			// Create theme cards
			themes.forEach(function(theme) {
				var card = createThemeCard(theme);
				$grid.append(card);
			});
			
			// Show pagination
			$('.seedprod-themes-pagination').show();
		}
		
		/**
		 * Create a theme card element
		 */
		function createThemeCard(theme) {
			var templateId = theme.id;
			var templateName = theme.name || seedprod_admin.strings.untitled_theme;
			// Use the favorited property directly - it should be a boolean
			var isFavorited = !!theme.favorited; // Convert to boolean
			var heartClass = isFavorited ? 'seedprod-favorited' : '';
			
			
			// Build image URL same as Vue version
			var imageUrl = 'https://assets.seedprod.com/themes/preview-' + templateId + '.jpg';
			
			// Build favorite icon HTML (EXACTLY matching landing page templates)
			var actionIcon = '<span class="seedprod-favorite-toggle ' + heartClass + '" data-template-id="' + templateId + '">' +
				'<span class="dashicons dashicons-heart"></span>' +
			'</span>';
			
			return '<div class="seedprod-template-card" data-template-id="' + templateId + '">' +
				'<div class="seedprod-template-thumbnail">' +
					'<div class="seedprod-theme-image">' +
						'<img src="' + imageUrl + '" alt="' + templateName + '" class="seedprod-template-image" loading="lazy">' +
					'</div>' +
					'<div class="seedprod-template-overlay" style="display: none;">' +
						'<button class="seedprod-template-select" data-template-id="' + templateId + '" data-template-name="' + templateName + '">' +
							'<span class="dashicons dashicons-yes"></span>' +
						'</button>' +
						'<button class="seedprod-template-preview" data-template-id="' + templateId + '" data-template-name="' + templateName + '">' +
							'<span class="dashicons dashicons-search"></span>' +
						'</button>' +
					'</div>' +
				'</div>' +
				'<div class="seedprod-template-name">' +
					'<span class="template-name">' + templateName + '</span>' +
					actionIcon +
				'</div>' +
			'</div>';
		}
		
		/**
		 * Update pagination controls
		 */
		function updatePagination(data) {
			themeKitsState.totalPages = data.last_page || 1;
			themeKitsState.currentPage = data.current_page || 1;
			
			$('#seedprod-current-page').text(themeKitsState.currentPage);
			$('#seedprod-total-pages').text(themeKitsState.totalPages);
			
			// Update button states
			$('#seedprod-first-page, #seedprod-prev-page').prop('disabled', themeKitsState.currentPage <= 1);
			$('#seedprod-next-page, #seedprod-last-page').prop('disabled', themeKitsState.currentPage >= themeKitsState.totalPages);
		}
		
		/**
		 * Show loading spinner
		 */
		function showLoadingSpinner($container) {
			$container.html('<div class="seedprod-templates-loading">' +
				'<span class="spinner is-active"></span>' +
				'<p>' + seedprod_admin.strings.loading_templates + '</p></div>');
		}
		
		/**
		 * Show error message
		 */
		function showError($container, message) {
			$container.html('<div class="seedprod-error">' +
				'<p>' + message + '</p></div>');
		}
		
		// Commented out iframe modal functions - keeping for potential future use
		
		// /**
		//  * Preview theme kit in modal
		//  */
		// function previewThemeKit(themeId, themeName) {
		// 	// Use preview.seedprod.com URL for theme kits (as used in Vue.js version)
		// 	var previewUrl = 'https://preview.seedprod.com/' + themeId;
		// 	
		// 	// Update title
		// 	$('#seedprod-preview-title').text(themeName);
		// 	
		// 	// Show modal first
		// 	$('#seedprod-template-preview-modal').show();
		// 	
		// 	// Add loading state to modal body
		// 	var $modalBody = $('#seedprod-template-preview-modal .seedprod-modal-body');
		// 	var $iframe = $('#seedprod-preview-iframe');
		// 	
		// 	// Hide iframe initially and show loading spinner
		// 	$iframe.hide();
		// 	
		// 	// Add loading spinner if not already present
		// 	if (!$modalBody.find('.seedprod-preview-loading').length) {
		// 		$modalBody.append('<div class="seedprod-preview-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; z-index: 10;">' +
		// 			'<span class="spinner is-active" style="float: none; margin: 0 auto 10px;"></span>' +
		// 			'<p>Loading preview...</p>' +
		// 		'</div>');
		// 	} else {
		// 		$modalBody.find('.seedprod-preview-loading').show();
		// 	}
		// 	
		// 	// Handle iframe load completion
		// 	$iframe.off('load').on('load', function() {
		// 		// Hide loading spinner
		// 		$modalBody.find('.seedprod-preview-loading').hide();
		// 		// Show iframe with fade-in effect
		// 		$iframe.fadeIn(200);
		// 	});
		// 	
		// 	// Set iframe source (this triggers loading)
		// 	$iframe.attr('src', previewUrl);
		// }
		// 
		// /**
		//  * Close preview modal
		//  */
		// function closeThemePreviewModal() {
		// 	$('#seedprod-template-preview-modal').hide();
		// 	// Reset iframe to prevent flash of old content on next open
		// 	var $iframe = $('#seedprod-preview-iframe');
		// 	$iframe.attr('src', '');
		// 	$iframe.hide();
		// 	// Hide any loading spinner
		// 	$('#seedprod-template-preview-modal .seedprod-preview-loading').hide();
		// }
	}
	
	/**
	 * Initialize settings page functionality
	 */
	function initSettings() {
		// License key handlers
		$('#seedprod-verify-license, #seedprod-recheck-license').on('click', function(e) {
			e.preventDefault();
			saveLicenseKey();
		});
		
		$('#seedprod-deactivate-license').on('click', function(e) {
			e.preventDefault();
			deactivateLicenseKey();
		});
		
		// Settings form handler
		$('#seedprod-settings-form').on('submit', function(e) {
			e.preventDefault();
			saveAppSettings();
		});
		
		// System info toggle
		$('#seedprod-toggle-system-info').on('click', function() {
			var $systemInfo = $('#seedprod-system-info');
			var $icon = $(this).find('.dashicons');
			
			if ($systemInfo.is(':visible')) {
				$systemInfo.slideUp();
				$icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
			} else {
				$systemInfo.slideDown();
				$icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
			}
		});
	}
	
	/**
	 * Save/verify license key
	 */
	function saveLicenseKey() {
		var $button = $('#seedprod-verify-license, #seedprod-recheck-license');
		var $message = $('#seedprod-license-message');
		var licenseKey = $('#seedprod-license-key').val();
		
		if (!licenseKey) {
			showInlineNotice($message, 'error', seedprod_admin.strings.license_empty || 'Please enter a license key.');
			return;
		}
		
		// Disable button and show loading
		$button.prop('disabled', true).text(seedprod_admin.strings.verifying);
		$message.empty();
		
		$.ajax({
			url: seedprod_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'seedprod_lite_v2_save_api_key',
				api_key: licenseKey,
				_wpnonce: seedprod_admin.nonce
			},
			success: function(response) {
				$button.prop('disabled', false);
				
				// Check response.status (string) not response.success (boolean)
				if (response.status === 'true') {
					showInlineNotice($message, 'success', response.msg || seedprod_admin.strings.license_verify_success);
					// Reload page to show updated license status
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					$button.text(seedprod_admin.strings.verify_key);
					showInlineNotice($message, 'error', response.msg || seedprod_admin.strings.license_invalid);

					// Hide the active badge if it exists
					$('.seedprod-license-badge-active').hide();
				}
			},
			error: function() {
				$button.prop('disabled', false).text(seedprod_admin.strings.verify_key);
				showInlineNotice($message, 'error', seedprod_admin.strings.license_error);

				// Hide the active badge if it exists
				$('.seedprod-license-badge-active').hide();
			}
		});
	}
	
	/**
	 * Deactivate license key
	 */
	function deactivateLicenseKey() {
		var $button = $('#seedprod-deactivate-license');
		var $message = $('#seedprod-license-message');
		var licenseKey = $('#seedprod-license-key').val();
		
		if (!confirm(seedprod_admin.strings.license_deactivate_confirm)) {
			return;
		}
		
		$button.prop('disabled', true).text(seedprod_admin.strings.license_deactivating);
		$message.empty();
		
		$.ajax({
			url: seedprod_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'seedprod_lite_v2_deactivate_api_key',
				api_key: licenseKey,
				_wpnonce: seedprod_admin.nonce
			},
			success: function(response) {
				$button.prop('disabled', false).text(seedprod_admin.strings.deactivate_key);
				
				if (response.success) {
					showInlineNotice($message, 'success', response.data.message || seedprod_admin.strings.license_deactivate_success);
					// Clear the license field and reload
					$('#seedprod-license-key').val('');
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					showInlineNotice($message, 'error', response.data.message || seedprod_admin.strings.license_deactivate_error);
				}
			},
			error: function() {
				$button.prop('disabled', false).text(seedprod_admin.strings.deactivate_key);
				showInlineNotice($message, 'error', seedprod_admin.strings.license_error);
			}
		});
	}
	
	/**
	 * Save app settings
	 */
	function saveAppSettings() {
		var $button = $('#seedprod-save-settings');
		
		// Remove any existing notices
		$('.seedprod-dashboard-container > .notice').remove();
		
		// Collect form data
		var appSettings = {
			facebook_g_app_id: $('#seedprod-facebook-app-id').val(),
			google_places_app_key: $('#seedprod-google-places-key').val(),
			yelp_app_api_key: $('#seedprod-yelp-api-key').val(),
			disable_seedprod_button: $('#seedprod-disable-button').is(':checked'),
			enable_usage_tracking: $('#seedprod-usage-tracking').is(':checked'),
			disable_seedprod_notification: $('#seedprod-disable-notifications').is(':checked')
		};
		
		$button.prop('disabled', true).text(seedprod_admin.strings.saving);
		
		$.ajax({
			url: seedprod_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'seedprod_lite_v2_save_app_settings',
				app_settings: appSettings,
				_wpnonce: $('#seedprod_settings_nonce').val()
			},
			success: function(response) {
				$button.prop('disabled', false).text(seedprod_admin.strings.save_settings);
				
				if (response.success) {
					// Show success notice above tabs, below header
					var $notice = $('<div class="notice notice-success is-dismissible seedprod-notice-compact"><p>' + 
						(response.data.message || seedprod_admin.strings.settings_save_success) + '</p></div>');
					$('.seedprod-dashboard-container').prepend($notice);
					
					// Add dismiss button functionality
					$notice.on('click', '.notice-dismiss', function() {
						$notice.fadeOut(function() {
							$(this).remove();
						});
					});
					
					// Add dismiss button if WordPress didn't add it
					if (!$notice.find('.notice-dismiss').length) {
						$notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + (seedprod_admin.strings.dismiss_notice || 'Dismiss this notice.') + '</span></button>');
					}
					
					// Scroll to notice to ensure it's visible
					// Try to scroll to the page top, accounting for WP admin bar
					if ($('.seedprod-dashboard-page').length) {
						var scrollTarget = Math.max(0, $('.seedprod-dashboard-page').offset().top - 32);
						window.scrollTo({ top: scrollTarget, behavior: 'smooth' });
					} else {
						// Fallback to simple scroll to top
						window.scrollTo({ top: 0, behavior: 'smooth' });
					}
					
					// Auto-remove after 3 seconds
					setTimeout(function() {
						$notice.fadeOut(function() {
							$(this).remove();
						});
					}, 3000);
				} else {
					var $notice = $('<div class="notice notice-error is-dismissible seedprod-notice-compact"><p>' + 
						(response.data.message || seedprod_admin.strings.settings_save_error) + '</p></div>');
					$('.seedprod-dashboard-container').prepend($notice);
					
					// Add dismiss button functionality
					$notice.on('click', '.notice-dismiss', function() {
						$notice.fadeOut(function() {
							$(this).remove();
						});
					});
					
					// Add dismiss button if WordPress didn't add it  
					if (!$notice.find('.notice-dismiss').length) {
						$notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + (seedprod_admin.strings.dismiss_notice || 'Dismiss this notice.') + '</span></button>');
					}
				}
			},
			error: function() {
				$button.prop('disabled', false).text(seedprod_admin.strings.save_settings);
				var $notice = $('<div class="notice notice-error is-dismissible seedprod-notice-compact"><p>' + (seedprod_admin.strings.error_occurred || 'An error occurred. Please try again.') + '</p></div>');
				$('.seedprod-dashboard-container').prepend($notice);
				
				// Add dismiss button functionality
				$notice.on('click', '.notice-dismiss', function() {
					$notice.fadeOut(function() {
						$(this).remove();
					});
				});
				
				// Add dismiss button if WordPress didn't add it
				if (!$notice.find('.notice-dismiss').length) {
					$notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + (seedprod_admin.strings.dismiss_notice || 'Dismiss this notice.') + '</span></button>');
				}
			}
		});
	}
	
	/**
	 * Helper function to show inline notices
	 */
	function showInlineNotice($element, type, message) {
		var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
		$element.html('<div class="notice inline ' + noticeClass + '"><p>' + message + '</p></div>');
	}
	
	/**
	 * Initialize subscribers functionality
	 */
	function initSubscribers() {
		var subscribersTable = {
			currentPage: 1,
			perPage: 25,
			sortBy: 'created',
			sortOrder: 'desc',
			search: '',
			pageFilter: 'all',
			chartInstance: null
		};
		
		// Load subscribers when tab becomes active
		if ($('#seedprod-subscribers-tab').is(':visible')) {
			loadSubscribers();
		}
		
		// Reload when tab is clicked (if using tab navigation)
		$('.nav-tab').on('click', function() {
			if ($(this).attr('href').indexOf('subscribers') !== -1) {
				setTimeout(loadSubscribers, 100);
			}
		});
		
		// Search functionality
		$('#seedprod-subscriber-search-btn').on('click', function() {
			performSearch();
		});
		
		$('#seedprod-subscriber-search').on('keypress', function(e) {
			if (e.which === 13) {
				e.preventDefault();
				performSearch();
			}
		});
		
		$('#seedprod-subscriber-clear-search').on('click', function() {
			$('#seedprod-subscriber-search').val('');
			subscribersTable.search = '';
			subscribersTable.currentPage = 1;
			$('#seedprod-subscriber-clear-search').hide();
			loadSubscribers();
		});
		
		// Filter functionality
		$('#seedprod-subscriber-page-filter').on('change', function() {
			subscribersTable.pageFilter = $(this).val();
			subscribersTable.currentPage = 1;
			loadSubscribers();
		});
		
		
		// Sort functionality
		$('#seedprod-subscribers-table th.sortable a').on('click', function(e) {
			e.preventDefault();
			var newSortBy = $(this).data('sort');
			
			if (subscribersTable.sortBy === newSortBy) {
				subscribersTable.sortOrder = subscribersTable.sortOrder === 'desc' ? 'asc' : 'desc';
			} else {
				subscribersTable.sortBy = newSortBy;
				subscribersTable.sortOrder = 'desc';
			}
			
			subscribersTable.currentPage = 1;
			loadSubscribers();
		});
		
		// Select all functionality
		$(document).on('change', '#seedprod-subscriber-select-all', function() {
			var isChecked = $(this).is(':checked');
			$('#seedprod-subscribers-list input[type="checkbox"]').prop('checked', isChecked);
		});
		
		$(document).on('change', '.seedprod-subscriber-select-all-bottom', function() {
			var isChecked = $(this).is(':checked');
			$('#seedprod-subscribers-list input[type="checkbox"]').prop('checked', isChecked);
			$('#seedprod-subscriber-select-all').prop('checked', isChecked);
		});
		
		// Individual checkbox changes
		$(document).on('change', '#seedprod-subscribers-list input[type="checkbox"]', function() {
			var totalCheckboxes = $('#seedprod-subscribers-list input[type="checkbox"]').length;
			var checkedCheckboxes = $('#seedprod-subscribers-list input[type="checkbox"]:checked').length;
			$('#seedprod-subscriber-select-all, .seedprod-subscriber-select-all-bottom').prop('checked', totalCheckboxes === checkedCheckboxes);
		});
		
		// Bulk actions
		$('#seedprod-subscriber-bulk-apply').on('click', function() {
			var action = $('#seedprod-subscriber-bulk-action').val();
			var selectedIds = [];
			
			$('#seedprod-subscribers-list input[type="checkbox"]:checked').each(function() {
				selectedIds.push($(this).val());
			});
			
			if (!selectedIds.length) {
				alert(seedprod_admin.strings.subscriber_no_selection);
				return;
			}
			
			if (action === 'delete') {
				deleteSubscribers(selectedIds);
			}
		});
		
		// Individual delete actions
		$(document).on('click', '.seedprod-delete-subscriber', function(e) {
			e.preventDefault();
			var subscriberId = $(this).data('id');
			deleteSubscribers([subscriberId]);
		});
		
		// Export functionality
		$('#seedprod-export-subscribers').on('click', function() {
			exportSubscribers();
		});
		
		// Pagination
		$(document).on('click', '.seedprod-pagination-link', function(e) {
			e.preventDefault();
			var page = parseInt($(this).data('page'));
			if (page && page !== subscribersTable.currentPage) {
				subscribersTable.currentPage = page;
				loadSubscribers();
			}
		});
		
		/**
		 * Perform search
		 */
		function performSearch() {
			var searchTerm = $('#seedprod-subscriber-search').val().trim();
			subscribersTable.search = searchTerm;
			subscribersTable.currentPage = 1;
			
			if (searchTerm) {
				$('#seedprod-subscriber-clear-search').show();
			} else {
				$('#seedprod-subscriber-clear-search').hide();
			}
			
			loadSubscribers();
		}
		
		/**
		 * Load subscribers data
		 */
		function loadSubscribers() {
			var $loading = $('#seedprod-subscribers-list');
			$loading.html('<tr><td colspan="5" class="seedprod-loading-message"><span class="dashicons dashicons-update spin"></span> ' + seedprod_admin.strings.loading + '</td></tr>');
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_get_subscribers_datatable',
					page: subscribersTable.currentPage,
					per_page: subscribersTable.perPage,
					search: subscribersTable.search,
					page_filter: subscribersTable.pageFilter,
					sort_by: subscribersTable.sortBy,
					sort_order: subscribersTable.sortOrder,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						displaySubscribers(response.data);
					} else {
						$loading.html('<tr><td colspan="5" class="seedprod-error-message">' + seedprod_admin.strings.error + ': ' + (response.data || seedprod_admin.strings.subscriber_load_error || 'Could not load subscribers.') + '</td></tr>');
					}
				},
				error: function() {
					$loading.html('<tr><td colspan="5" class="seedprod-error-message">' + (seedprod_admin.strings.network_error || 'Network error. Please try again.') + '</td></tr>');
				}
			});
		}
		
		/**
		 * Display subscribers in table
		 */
		function displaySubscribers(data) {
			var html = '';
			
			if (data.subscribers.length > 0) {
				data.subscribers.forEach(function(subscriber) {
					html += '<tr>';
					html += '<th scope="row" class="check-column"><input type="checkbox" value="' + subscriber.id + '" /></th>';
					html += '<td class="column-email"><strong>' + escapeHtml(subscriber.email) + '</strong></td>';
					html += '<td class="column-name">' + escapeHtml(subscriber.name) + '</td>';
					html += '<td class="column-created">' + subscriber.created + '</td>';
					html += '<td class="column-actions">';
					html += '<button type="button" class="button button-small seedprod-delete-subscriber" data-id="' + subscriber.id + '">' + (seedprod_admin.strings.delete || 'Delete') + '</button>';
					html += '</td>';
					html += '</tr>';
				});
			}
			
			// Add OptinMonster promo row if not active
			if (window.seedprodOptinMonster && !window.seedprodOptinMonster.active) {
				html += '<tr class="seedprod-optinmonster-row">';
				html += '<td class="column-cb"></td>'; // Empty checkbox column
				html += '<td class="column-email" colspan="2">';
				html += '<span class="dashicons dashicons-megaphone" style="color: #087ce1; margin-right: 5px;"></span>';
				html += '<strong style="color: #087ce1;">Pro Tip:</strong> ';
				html += 'Get 3x more subscribers with OptinMonster\'s exit-intent popups';
				html += '</td>';
				html += '<td class="column-created">';
				html += '<a href="https://optinmonster.com/?utm_source=seedprod&utm_medium=subscribers-table&utm_campaign=cross-sell" target="_blank" style="color: #087ce1;">Learn more</a>';
				html += '</td>';
				html += '<td class="column-actions">';
				var optinMonsterUrl = seedprod_admin.admin_url + 'admin.php?page=seedprod_lite_settings&tab=recommended-plugins&filter=optinmonster';
				if (!window.seedprodOptinMonster.installed) {
					html += '<a href="' + optinMonsterUrl + '" class="button button-small button-primary">Install Free</a>';
				} else {
					html += '<a href="' + optinMonsterUrl + '" class="button button-small button-primary">Activate</a>';
				}
				html += '</td>';
				html += '</tr>';
			}
			
			$('#seedprod-subscribers-list').html(html);
			
			// Update count
			$('#seedprod-subscriber-count').text(data.total);
			
			// Update pagination
			updatePagination(data);
			
			// Update sort indicators
			updateSortIndicators();
		}
		
		/**
		 * Update pagination
		 */
		function updatePagination(data) {
			var html = '';
			
			if (data.pages > 1) {
				// Previous page
				if (subscribersTable.currentPage > 1) {
					html += '<a class="prev-page button seedprod-pagination-link" data-page="' + (subscribersTable.currentPage - 1) + '">&lsaquo;</a> ';
				} else {
					html += '<span class="tablenav-pages-navspan button disabled">&lsaquo;</span> ';
				}
				
				// Page numbers
				var startPage = Math.max(1, subscribersTable.currentPage - 2);
				var endPage = Math.min(data.pages, subscribersTable.currentPage + 2);
				
				if (startPage > 1) {
					html += '<a class="page-numbers seedprod-pagination-link" data-page="1">1</a> ';
					if (startPage > 2) {
						html += '<span class="page-numbers dots">…</span> ';
					}
				}
				
				for (var i = startPage; i <= endPage; i++) {
					if (i === subscribersTable.currentPage) {
						html += '<span class="page-numbers current">' + i + '</span> ';
					} else {
						html += '<a class="page-numbers seedprod-pagination-link" data-page="' + i + '">' + i + '</a> ';
					}
				}
				
				if (endPage < data.pages) {
					if (endPage < data.pages - 1) {
						html += '<span class="page-numbers dots">…</span> ';
					}
					html += '<a class="page-numbers seedprod-pagination-link" data-page="' + data.pages + '">' + data.pages + '</a> ';
				}
				
				// Next page
				if (subscribersTable.currentPage < data.pages) {
					html += '<a class="next-page button seedprod-pagination-link" data-page="' + (subscribersTable.currentPage + 1) + '">&rsaquo;</a>';
				} else {
					html += '<span class="tablenav-pages-navspan button disabled">&rsaquo;</span>';
				}
			}
			
			$('#seedprod-subscriber-pagination').html(html);
		}
		
		/**
		 * Update sort indicators
		 */
		function updateSortIndicators() {
			$('#seedprod-subscribers-table th.sortable').removeClass('asc desc');
			var $currentSort = $('#seedprod-subscribers-table th.sortable a[data-sort="' + subscribersTable.sortBy + '"]').parent();
			$currentSort.addClass(subscribersTable.sortOrder);
		}
		
		/**
		 * Delete subscribers
		 */
		function deleteSubscribers(ids) {
			if (!confirm(seedprod_admin.strings.subscriber_delete_confirm)) {
				return;
			}
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_delete_subscribers',
					ids: ids,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						showNotice($('.seedprod-dashboard-container'), 'success', response.data.message || seedprod_admin.strings.subscriber_delete_success);
						loadSubscribers(); // Reload table
					} else {
						showNotice($('.seedprod-dashboard-container'), 'error', response.data || seedprod_admin.strings.subscriber_error);
					}
				},
				error: function() {
					showNotice($('.seedprod-dashboard-container'), 'error', seedprod_admin.strings.subscriber_error);
				}
			});
		}
		
		/**
		 * Export subscribers to CSV
		 */
		function exportSubscribers() {
			var $button = $('#seedprod-export-subscribers');
			var originalText = $button.text();
			
			$button.prop('disabled', true).text(seedprod_admin.strings.exporting);
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_export_subscribers',
					page_filter: subscribersTable.pageFilter,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					$button.prop('disabled', false).text(originalText);
					
					if (response.success) {
						// Create download link
						var blob = new Blob([response.data.csv], { type: 'text/csv' });
						var url = window.URL.createObjectURL(blob);
						var a = document.createElement('a');
						a.href = url;
						a.download = response.data.filename;
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
						window.URL.revokeObjectURL(url);
						
						showNotice($('.seedprod-dashboard-container'), 'success', response.data.message || seedprod_admin.strings.subscriber_export_success);
					} else {
						showNotice($('.seedprod-dashboard-container'), 'error', response.data || seedprod_admin.strings.template_import_error);
					}
				},
				error: function() {
					$button.prop('disabled', false).text(originalText);
					showNotice($('.seedprod-dashboard-container'), 'error', seedprod_admin.strings.template_import_error);
				}
			});
		}
		
		
		/**
		 * Escape HTML for safe display
		 */
		function escapeHtml(text) {
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, function(m) { return map[m]; });
		}
	}
	
	/**
	 * Initialize landing pages functionality
	 */
	function initLandingPages() {
		// Toggle switches for enable/disable modes
		$('.seedprod-landing-pages-page .seedprod-toggle').on('change', function() {
			var $toggle = $(this);
			var mode = $toggle.data('mode');
			var isChecked = $toggle.is(':checked');
			
			// Don't process pro features in lite mode
			if ($toggle.closest('.coming-soon-feature').length && $('.seedprod-lite').length) {
				$toggle.prop('checked', false);
				return false;
			}
			
			// Disable toggle during AJAX
			$toggle.prop('disabled', true);
			
			// Get current settings
			var settings = {
				enable_coming_soon_mode: $('.seedprod-toggle[data-mode="coming_soon"]').is(':checked'),
				enable_maintenance_mode: $('.seedprod-toggle[data-mode="maintenance"]').is(':checked'),
				enable_login_mode: $('.seedprod-toggle[data-mode="login"]').is(':checked'),
				enable_404_mode: $('.seedprod-toggle[data-mode="404"]').is(':checked')
			};
			
			// Apply the change
			if (mode === 'coming_soon') {
				settings.enable_coming_soon_mode = isChecked;
				// If enabling coming soon, disable maintenance
				if (isChecked && settings.enable_maintenance_mode) {
					settings.enable_maintenance_mode = false;
					$('.seedprod-toggle[data-mode="maintenance"]').prop('checked', false);
					updateModeCardStatus('maintenance', false);
				}
			} else if (mode === 'maintenance') {
				settings.enable_maintenance_mode = isChecked;
				// If enabling maintenance, disable coming soon
				if (isChecked && settings.enable_coming_soon_mode) {
					settings.enable_coming_soon_mode = false;
					$('.seedprod-toggle[data-mode="coming_soon"]').prop('checked', false);
					updateModeCardStatus('coming_soon', false);
				}
			} else if (mode === 'login') {
				settings.enable_login_mode = isChecked;
			} else if (mode === '404') {
				settings.enable_404_mode = isChecked;
			}
			
			// Update visual status immediately for better UX
			updateModeCardStatus(mode, isChecked);
			
			// Save settings via AJAX
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_save_settings',
					settings: JSON.stringify(settings),
					_wpnonce: seedprod_admin.nonce
				},
				success: function() {
					// Refresh page for coming soon and maintenance modes to update admin bar notice
					if (mode === 'coming_soon' || mode === 'maintenance') {
						// Show success state briefly before refresh
						setTimeout(function() {
							window.location.reload();
						}, 500);
					} else {
						// Re-enable toggle for other modes
						$toggle.prop('disabled', false);
					}
				},
				error: function() {
					// Revert toggle on error
					$toggle.prop('checked', !isChecked);
					$toggle.prop('disabled', false);
					updateModeCardStatus(mode, !isChecked);
					alert(seedprod_admin.strings.settings_error || 'Could not save settings. Please try again.');
				}
			});
		});
		
		/**
		 * Helper function to update mode card status badge
		 */
		function updateModeCardStatus(mode, isActive) {
			var $card = $('.seedprod-mode-card[data-mode="' + mode + '"]');
			var $label = $card.find('.seedprod-toggle-label');
			
			if (isActive) {
				$label.html('<span class="active">' + (seedprod_admin.strings.active || 'ACTIVE') + '</span>');
			} else {
				$label.html('<span class="inactive">' + (seedprod_admin.strings.inactive || 'INACTIVE') + '</span>');
			}
		}
		
		// New landing page inline form functionality
		if ($('#seedprod-create-page-form').length > 0) {
			
			// Track if user has manually edited the slug
			var slugManuallyEdited = false;
			
			// Slugify function (same as Vue implementation)
			function slugify(text) {
				if (!text) return '';
				return text.toString().toLowerCase()
					.replace(/\s+/g, '-')           // Replace spaces with -
					.replace(/[^\w\-]+/g, '')       // Remove all non-word chars
					.replace(/\-\-+/g, '-')          // Replace multiple - with single -
					.replace(/^-+/, '')              // Trim - from start of text
					.replace(/-+$/, '');             // Trim - from end of text
			}
			
			// Show/hide inline form
			$('#seedprod-show-new-page-form').on('click', function() {
				$('#seedprod-new-page-inline').slideDown();
				$('#seedprod-page-name').focus();
				$(this).prop('disabled', true);
				// Reset the manual edit flag when form is shown
				slugManuallyEdited = false;
			});
			
			// Cancel button
			$('#seedprod-cancel-new-page').on('click', function() {
				$('#seedprod-new-page-inline').slideUp();
				$('#seedprod-show-new-page-form').prop('disabled', false);
				// Reset form and flags
				$('#seedprod-create-page-form')[0].reset();
				slugManuallyEdited = false;
			});
			
			// Auto-generate slug from page name if user hasn't manually edited it
			$('#seedprod-page-name').on('input', function() {
				var pageName = $(this).val();
				
				// Only auto-generate if user hasn't manually edited the slug
				if (!slugManuallyEdited) {
					var slug = slugify(pageName);
					$('#seedprod-page-slug').val(slug);
				}
			});
			
			// When user types in slug field, mark it as manually edited
			$('#seedprod-page-slug').on('input', function() {
				slugManuallyEdited = true;
			});
			
			// Clean up slug field on blur (when user leaves the field)
			$('#seedprod-page-slug').on('blur', function() {
				var originalVal = $(this).val();
				var slug = slugify(originalVal);
				$(this).val(slug);
			});
			
			// Handle form submission (use .off().on() to prevent duplicate handlers)
			$('#seedprod-create-page-form').off('submit').on('submit', function(e) {
				e.preventDefault();
				
				var pageName = $('#seedprod-page-name').val();
				var slug = $('#seedprod-page-slug').val();
				
				// If no slug provided, generate from name
				if (!slug) {
					slug = slugify(pageName);
				}
				
				if (!pageName) {
					alert(seedprod_admin.strings.page_name_required || 'Please enter a page name');
					$('#seedprod-page-name').focus();
					return false;
				}
				
				// Hide any previous error messages
				$('#seedprod-page-slug-error').hide();
				
				// Check if slug already exists
				$.ajax({
					url: seedprod_slug_check_url,
					type: 'POST',
					data: {
						post_name: slug
					},
					success: function(response) {
						if (response.success) {
							// Slug is available, proceed to template selection
							var templateUrl = seedprod_admin.admin_url +
								'admin.php?page=seedprod_lite_template_selection' +
								'&type=lp' +
								'&_wpnonce=' + seedprod_admin.nonce +
								'&name=' + encodeURIComponent(pageName) +
								'&slug=' + encodeURIComponent(slug);
							
							window.location.href = templateUrl;
						} else {
							// Slug already exists - show error message below field
							$('#seedprod-page-slug-error').show();
							$('#seedprod-page-slug').focus();
							
							// Show alert after a brief delay so the error message is visible
							setTimeout(function() {
								alert(seedprod_admin.strings.slug_exists || 'This page URL already exists. Please choose a unique page URL.');
							}, 100);
						}
					},
					error: function(xhr, status, error) {
						// Only show error if it's not a validation error
						if (xhr.status !== 0) {
							alert(seedprod_admin.strings.network_error || 'Network error. Please try again.');
						}
					}
				});
			});
		}
		
		// DataTable row actions
		initLandingPageTableActions();
		
		// Search box clear functionality
		initLandingPageSearchClear();
	}
	
	/**
	 * Initialize search clear functionality for landing pages
	 */
	function initLandingPageSearchClear() {
		// Handle search input clear button (X button)
		// WordPress adds a clear button to search inputs in modern browsers
		var $searchInput = $('#seedprod-landing-pages-search-input');
		
		if ($searchInput.length) {
			// Listen for the search event which fires when the X is clicked
			$searchInput.on('search', function() {
				// If the input is now empty (X was clicked)
				if ($(this).val() === '') {
					// Submit the form to clear the search
					$('#seedprod-landing-pages-form').submit();
				}
			});
			
			// Also handle the input event for older browsers
			$searchInput.on('input', function() {
				// If the input was cleared
				if ($(this).val() === '') {
					// Check if there was a search parameter in the URL
					var urlParams = new URLSearchParams(window.location.search);
					if (urlParams.has('s')) {
						// Clear the search by submitting the form
						$('#seedprod-landing-pages-form').submit();
					}
				}
			});
		}
	}
	
	/**
	 * Initialize landing page table row actions (duplicate, trash, restore, delete)
	 */
	function initLandingPageTableActions() {
		// Helper function to show success message
		function showSuccessMessage(message) {
			var $message = $('<div class="notice notice-success is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 9999; padding: 12px 20px;"><p>' + message + '</p></div>');
			$('body').append($message);
		}
		
		// Duplicate page action
		$(document).on('click', '.seedprod-duplicate-page', function(e) {
			e.preventDefault();

			var $link = $(this);
			var pageId = $link.data('id');

			// Prevent duplicate clicks
			if ($link.hasClass('duplicating')) {
				return;
			}

			// Disable the link during AJAX
			$link.addClass('duplicating').css('pointer-events', 'none').css('opacity', '0.5');
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_duplicate_lpage',
					id: pageId,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success message and reload
						showSuccessMessage(response.data.message);
						setTimeout(function() {
							window.location.reload();
						}, 700);
					} else {
						alert(response.data || seedprod_admin.strings.duplicate_page_error);
						$link.removeClass('duplicating').css('pointer-events', '').css('opacity', '');
					}
				},
				error: function() {
					alert(seedprod_admin.strings.duplicate_page_error);
					$link.removeClass('duplicating').css('pointer-events', '').css('opacity', '');
				}
			});
		});
		
		// Trash page action
		$(document).on('click', '.seedprod-trash-page', function(e) {
			e.preventDefault();

			var $link = $(this);
			var pageId = $link.data('id');

			// Prevent duplicate clicks
			if ($link.hasClass('trashing')) {
				return;
			}

			if (!confirm(seedprod_admin.strings.confirm_page_trash)) {
				return;
			}

			// Disable the link during AJAX
			$link.addClass('trashing').css('pointer-events', 'none').css('opacity', '0.5');

			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_trash_lpage',
					id: pageId,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success message and reload
						showSuccessMessage(response.data.message);
						setTimeout(function() {
							window.location.reload();
						}, 700);
					} else {
						alert(response.data || seedprod_admin.strings.trash_page_error);
						$link.removeClass('trashing').css('pointer-events', '').css('opacity', '');
					}
				},
				error: function() {
					alert(seedprod_admin.strings.trash_page_error);
					$link.removeClass('trashing').css('pointer-events', '').css('opacity', '');
				}
			});
		});
		
		// Restore page action
		$(document).on('click', '.seedprod-restore-page', function(e) {
			e.preventDefault();

			var $link = $(this);
			var pageId = $link.data('id');

			// Prevent duplicate clicks
			if ($link.hasClass('restoring')) {
				return;
			}

			// Disable the link during AJAX
			$link.addClass('restoring').css('pointer-events', 'none').css('opacity', '0.5');
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_restore_lpage',
					id: pageId,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success message and reload
						showSuccessMessage(response.data.message);
						setTimeout(function() {
							window.location.reload();
						}, 700);
					} else {
						alert(response.data || seedprod_admin.strings.restore_page_error);
						$link.removeClass('restoring').css('pointer-events', '').css('opacity', '');
					}
				},
				error: function() {
					alert(seedprod_admin.strings.restore_page_error);
					$link.removeClass('restoring').css('pointer-events', '').css('opacity', '');
				}
			});
		});
		
		// Delete permanently action
		$(document).on('click', '.seedprod-delete-page', function(e) {
			e.preventDefault();

			var $link = $(this);
			var pageId = $link.data('id');

			// Prevent duplicate clicks
			if ($link.hasClass('deleting')) {
				return;
			}

			if (!confirm(seedprod_admin.strings.confirm_page_delete)) {
				return;
			}

			// Disable the link during AJAX
			$link.addClass('deleting').css('pointer-events', 'none').css('opacity', '0.5');

			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_delete_lpage',
					id: pageId,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success message and reload
						showSuccessMessage(response.data.message);
						setTimeout(function() {
							window.location.reload();
						}, 700);
					} else {
						alert(response.data || seedprod_admin.strings.delete_page_error);
						$link.removeClass('deleting').css('pointer-events', '').css('opacity', '');
					}
				},
				error: function() {
					alert(seedprod_admin.strings.delete_page_error);
					$link.removeClass('deleting').css('pointer-events', '').css('opacity', '');
				}
			});
		});
		
		// Handle bulk actions (unified handler)
		$('#doaction, #doaction2').off('click.bulk-actions').on('click.bulk-actions', function(e) {
			var action = $(this).prev('select').val();

			if (action === '-1' || action === '') {
				return;
			}

			// Determine which page we're on
			var currentPage = new URLSearchParams(window.location.search).get('page');

			if (currentPage === 'seedprod_lite_landing_pages') {
				// Handle landing pages bulk actions
				var checkedBoxes = $('#the-list input[name="page_id[]"]:checked');

				if (checkedBoxes.length === 0) {
					alert(seedprod_admin.strings.no_pages_selected);
					e.preventDefault();
					return;
				}

				// Collect page IDs
				var pageIds = [];
				checkedBoxes.each(function() {
					pageIds.push($(this).val());
				});

				// Confirm action
				var confirmMessage = '';
				switch(action) {
					case 'trash':
						confirmMessage = seedprod_admin.strings.confirm_bulk_trash || 'Are you sure you want to trash the selected items?';
						break;
					case 'restore':
						confirmMessage = seedprod_admin.strings.confirm_bulk_restore || 'Are you sure you want to restore the selected items?';
						break;
					case 'delete':
						confirmMessage = seedprod_admin.strings.confirm_bulk_delete || 'Are you sure you want to permanently delete the selected items?';
						break;
					default:
						return;
				}

				if (!confirm(confirmMessage)) {
					e.preventDefault();
					return;
				}

				e.preventDefault();

				// Prevent duplicate clicks
				var $button = $(this);
				if ($button.hasClass('bulk-processing')) {
					return;
				}

				// Disable the button during AJAX
				$button.addClass('bulk-processing').prop('disabled', true);

				$.ajax({
					url: seedprod_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'seedprod_lite_v2_bulk_action_lpages',
						bulk_action: action,
						page_ids: pageIds,
						nonce: seedprod_admin.v2_nonce
					},
					success: function(response) {
						if (response.success) {
							// Show success message and reload
							showSuccessMessage(response.data.message);
							setTimeout(function() {
								window.location.reload();
							}, 700);
						} else {
							alert(response.data || seedprod_admin.strings.bulk_action_error);
							$button.removeClass('bulk-processing').prop('disabled', false);
						}
					},
					error: function() {
						alert(seedprod_admin.strings.bulk_action_error);
						$button.removeClass('bulk-processing').prop('disabled', false);
					}
				});

			} else if (currentPage === 'seedprod_lite_website_builder') {
				// Handle templates bulk actions
				var templateIds = [];
				$('input[name="template_id[]"]:checked').each(function() {
					templateIds.push($(this).val());
				});

				if (templateIds.length === 0) {
					alert(seedprod_admin.strings.no_items_selected);
					e.preventDefault();
					return;
				}

				var confirmMessage = '';
				switch (action) {
					case 'trash':
						confirmMessage = seedprod_admin.strings.confirm_bulk_trash || 'Are you sure you want to trash the selected templates?';
						break;
					case 'restore':
						confirmMessage = seedprod_admin.strings.confirm_bulk_restore || 'Are you sure you want to restore the selected templates?';
						break;
					case 'delete':
						confirmMessage = seedprod_admin.strings.confirm_bulk_delete || 'Are you sure you want to permanently delete the selected templates?';
						break;
				}

				if (!confirm(confirmMessage)) {
					e.preventDefault();
					return;
				}

				e.preventDefault();

				$.ajax({
					url: seedprod_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'seedprod_lite_v2_bulk_action_templates',
						bulk_action: action,
						template_ids: templateIds,
						nonce: seedprod_admin.v2_nonce
					},
					success: function(response) {
						if (response.success) {
							showNotice(response.data.message, 'success');
							setTimeout(function() {
								location.reload();
							}, 1000);
						} else {
							showNotice(response.data.message || seedprod_admin.strings.error, 'error');
						}
					},
					error: function() {
						showNotice(seedprod_admin.strings.error, 'error');
					}
				});
			}
		});
	}
	
	/**
	 * Initialize template selection functionality
	 */
	function initTemplateSelection() {
		var templateState = {
			currentTab: 'all-templates',
			search: '',
			filter: 'all',
			templates: {},
			favorites: [],
			saved: [],
			templateHover: null,
			searchTimeout: null,
			resizeTimeout: null
		};
		
		// Initialize based on current tab
		if (typeof seedprodTemplateData !== 'undefined') {
			templateState.currentTab = seedprodTemplateData.activeTab || 'all-templates';
		}
		
		// Check for active filter pill set by PHP (for pre-filtering based on page type)
		var $activeFilter = $('.seedprod-filter-pill.active');
		if ($activeFilter.length > 0) {
			templateState.filter = $activeFilter.data('filter') || 'all';
		}
		
		// Load templates based on current tab (no need for nested document ready)
		if (templateState.currentTab === 'all-templates') {
			loadTemplates();
		} else if (templateState.currentTab === 'favorite-templates') {
			loadFavoriteTemplates();
		} else if (templateState.currentTab === 'saved-templates') {
			loadSavedTemplates();
		}
		
		// Tab switching functionality
		$('.nav-tab').on('click', function() {
			var href = $(this).attr('href');
			if (href && href.indexOf('tab=') !== -1) {
				var newTab = href.split('tab=')[1].split('&')[0];
				templateState.currentTab = newTab;
			}
		});
		
		// Search functionality with debouncing
		$('#seedprod-template-search').on('input', function() {
			var searchTerm = $(this).val().trim();
			templateState.search = searchTerm;
			
			// Reset filter to 'all' when searching
			if (searchTerm !== '') {
				templateState.filter = 'all';
				$('.seedprod-filter-pill').removeClass('active');
				$('.seedprod-filter-pill[data-filter="all"]').addClass('active');
			}
			
			// Clear existing timeout
			if (templateState.searchTimeout) {
				clearTimeout(templateState.searchTimeout);
			}
			
			// Set new timeout for 300ms delay
			templateState.searchTimeout = setTimeout(function() {
				if (templateState.currentTab === 'all-templates') {
					loadTemplates();
				}
			}, 300);
		});
		
		// Filter pill functionality
		$('.seedprod-filter-pill').on('click', function() {
			var filter = $(this).data('filter');
			templateState.filter = filter;
			
			// Clear search when selecting a filter
			templateState.search = '';
			$('#seedprod-template-search').val('');
			
			// Update active states
			$('.seedprod-filter-pill').removeClass('active');
			$(this).addClass('active');
			
			loadTemplates();
		});
		
		// Template hover effects
		$(document).on('mouseenter', '.seedprod-template-card', function() {
			var templateId = $(this).data('template-id');
			setTemplateHover(templateId, true);
		});
		
		$(document).on('mouseleave', '.seedprod-template-card', function() {
			setTemplateHover(null, false);
		});
		
		// Template selection
		$(document).on('click', '.seedprod-template-select', function(e) {
			e.preventDefault();
			var templateId = $(this).data('template-id');
			selectTemplate(templateId);
		});
		
		// Template preview
		$(document).on('click', '.seedprod-template-preview', function(e) {
			e.preventDefault();
			var templateId = $(this).data('template-id');
			var templateName = $(this).data('template-name') || (seedprod_admin.strings.template_preview || 'Template Preview');
			previewTemplate(templateId, templateName);
		});
		
		// Blank template fallback button
		$(document).on('click', '.seedprod-blank-template-fallback', function(e) {
			e.preventDefault();
			// Use blank template ID (99999 is SeedProd's convention for blank)
			selectTemplate('99999');
		});
		
		// Preview modal close
		$(document).on('click', '.seedprod-modal-close, .seedprod-modal-overlay', function() {
			closePreviewModal();
		});
		
		// Favorite toggle
		$(document).on('click', '.seedprod-favorite-toggle', function(e) {
			e.preventDefault();
			var templateId = $(this).data('template-id');
			toggleFavorite(templateId);
		});
		
		/**
		 * Load templates from API
		 */
		function loadTemplates() {
			var $grid = $('#all-templates-grid');
			
			showLoadingSpinner($grid);
			
			// Map filter names to category IDs (matching Vue.js implementation)
			var categoryMap = {
				'all': '',
				'coming-soon': '1',
				'maintenance': '2',
				'404': '3',
				'sales': '4',
				'webinar': '5',
				'lead-squeeze': '6',
				'thank-you': '7',
				'login': '8'
			};
			
			var category = categoryMap[templateState.filter] || '';
			
			$.ajax({
				url: seedprodTemplateData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_get_templates',
					filter: 'all', // Always use 'all' as filter type
					category: category, // Send category ID to backend
					search: templateState.search,
					is_lite_view: seedprodTemplateData.isLiteView || false,
					free_subscribed: seedprodTemplateData.freeTemplatesSubscribed || false,
					_wpnonce: seedprodTemplateData.nonce
				},
				success: function(response) {
					hideLoadingSpinner($grid);
					if (response.success && response.data) {
						templateState.templates = response.data;
						renderTemplates(response.data, $grid);
					} else {
						showErrorFallback($grid, seedprod_admin.strings.template_load_error);
					}
				},
				error: function() {
					hideLoadingSpinner($grid);
					showErrorFallback($grid, seedprod_admin.strings.template_network_error);
				}
			});
		}
		
		/**
		 * Load favorite templates
		 */
		function loadFavoriteTemplates() {
			var $grid = $('#favorite-templates-grid');
			
			showLoadingSpinner($grid);
			
			$.ajax({
				url: seedprodTemplateData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_get_favorite_templates',
					_wpnonce: seedprodTemplateData.nonce
				},
				success: function(response) {
					hideLoadingSpinner($grid);
					if (response.success) {
						templateState.favorites = response.data;
						if (!response.data || response.data.length === 0) {
							showNoTemplatesMessage($grid, seedprod_admin.strings.template_no_favorites);
						} else {
							renderTemplates(response.data, $grid);
						}
					} else {
						showErrorFallback($grid, seedprod_admin.strings.template_load_error);
					}
				},
				error: function() {
					hideLoadingSpinner($grid);
					showErrorFallback($grid, seedprod_admin.strings.network_error || 'Network error. Please try again.');
				}
			});
		}
		
		/**
		 * Load saved templates
		 */
		function loadSavedTemplates() {
			var $grid = $('#saved-templates-grid');
			
			showLoadingSpinner($grid);
			
			$.ajax({
				url: seedprodTemplateData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_get_saved_templates',
					_wpnonce: seedprodTemplateData.nonce
				},
				success: function(response) {
					hideLoadingSpinner($grid);
					if (response.success) {
						templateState.saved = response.data;
						if (response.data.length === 0) {
							showNoTemplatesMessage($grid, seedprod_admin.strings.template_no_saved);
						} else {
							renderTemplates(response.data, $grid);
						}
					} else {
						showErrorFallback($grid, seedprod_admin.strings.saved_template_load_error || 'Could not load saved templates.');
					}
				},
				error: function() {
					hideLoadingSpinner($grid);
					showErrorFallback($grid, seedprod_admin.strings.network_error || 'Network error. Please try again.');
				}
			});
		}
		
		/**
		 * Render templates in grid
		 */
		function renderTemplates(templates, $container) {
			var html = '';
			
			// Render templates
			if (templates && templates.length > 0) {
				templates.forEach(function(template) {
					html += renderTemplateCard(template);
				});
			}
			
			// Clear existing templates and add new ones
			$container.find('.seedprod-template-card').remove();
			$container.find('.seedprod-templates-loading, .seedprod-error-message, .seedprod-no-templates').remove();
			
			// Add templates
			$container.append(html);
			
			// Initialize masonry layout after images load
			initializeMasonryLayout($container);
		}
		
		
		/**
		 * Render individual template card
		 */
		function renderTemplateCard(template) {
			// Debug: Log template data to see available fields
			if (window.seedprodDebugTemplates) {
				console.log('Template data:', template);
			}
			
			var templateId = template.id || template.ID;
			var templateName = template.name || template.post_title || (seedprod_admin.strings.untitled_template || 'Untitled Template');
			var thumbnailUrl = '';
			var isFavorite = template.is_favorite || false;
			var heartClass = isFavorite ? 'seedprod-favorited' : '';
			
			// Check if this is a Pro template for Lite users
			var isProTemplate = false;
			var cardClass = '';
			var proBadge = '';
			
			if (seedprodTemplateData.isLiteView) {
				// Check free_w_email FIRST since these are actually free templates
				if (template.free_w_email == 1 && !seedprodTemplateData.freeTemplatesSubscribed) {
					// Free template that requires email subscription
					cardClass = ' seedprod-email-template';
					proBadge = '<span class="seedprod-template-free-badge">FREE</span>';
				} else if (template.free == 0 && template.free_w_email != 1) {
					// Pro template (but not free with email)
					isProTemplate = true;
					cardClass = ' coming-soon-template';
					proBadge = '<span class="seedprod-template-pro-badge">PRO</span>';
				}
			}
			
			// Determine thumbnail URL
			if (templateState.currentTab === 'saved-templates') {
				thumbnailUrl = 'https://assets.seedprod.com/preview-saved.png';
			} else {
				thumbnailUrl = 'https://assets.seedprod.com/full_length_thumbnails/preview-' + templateId + '.jpeg';
			}
			
			// Determine action icon (heart for templates, trash for saved)
			var actionIcon = '';
			if (templateState.currentTab === 'saved-templates') {
				// Show trash icon for saved templates
				actionIcon = `<span class="seedprod-delete-saved" data-template-id="${templateId}" style="cursor: pointer; color: #8c8f94;">
					<span class="dashicons dashicons-trash"></span>
				</span>`;
			} else {
				// Show heart icon for regular/favorite templates
				actionIcon = `<span class="seedprod-favorite-toggle ${heartClass}" data-template-id="${templateId}">
					<span class="dashicons dashicons-heart"></span>
				</span>`;
			}
			
			return `
				<div class="seedprod-template-card${cardClass}" data-template-id="${templateId}">
					<div class="seedprod-template-thumbnail">
						${proBadge}
						<img src="${thumbnailUrl}" alt="${templateName}" class="seedprod-template-image" loading="lazy"
							 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
						<div class="seedprod-template-image-fallback" style="display: none; height: 200px; background: #f6f7f7; align-items: center; justify-content: center; color: #8c8f94; font-size: 14px;">
							<span>' + (seedprod_admin.strings.preview_not_available || 'Preview Not Available') + '</span>
						</div>
						<div class="seedprod-template-overlay" style="display: none;">
							<button class="seedprod-template-select" data-template-id="${templateId}">
								<span class="dashicons dashicons-${isProTemplate ? 'lock' : 'yes'}"></span>
							</button>
							<button class="seedprod-template-preview" data-template-id="${templateId}" data-template-name="${templateName}">
								<span class="dashicons dashicons-search"></span>
							</button>
						</div>
					</div>
					<div class="seedprod-template-name">
						<span class="template-name">${templateName}</span>
						${actionIcon}
					</div>
				</div>
			`;
		}
		
		/**
		 * Show loading spinner
		 */
		function showLoadingSpinner($container) {
			$container.find('.seedprod-template-card').remove();
			$container.find('.seedprod-error-message, .seedprod-no-templates').remove();
			
			if (!$container.find('.seedprod-templates-loading').length) {
				var loadingHtml = `
					<div class="seedprod-templates-loading">
						<span class="spinner is-active"></span>
						<p>${seedprod_admin.strings.loading_templates || 'Loading templates...'}</p>
					</div>
				`;
				$container.append(loadingHtml);
			}
			
			$container.find('.seedprod-templates-loading').show();
		}
		
		/**
		 * Hide loading spinner
		 */
		function hideLoadingSpinner($container) {
			$container.find('.seedprod-templates-loading').remove();
		}
		
		/**
		 * Show error fallback with blank template option
		 */
		function showErrorFallback($container, message) {
			$container.find('.seedprod-template-card').remove();
			$container.find('.seedprod-templates-loading').remove();
			
			// Check if we have page name and slug to proceed with blank template
			var canCreateBlank = seedprodTemplateData && seedprodTemplateData.pageName && seedprodTemplateData.pageSlug;
			
			var errorHtml = `
				<div class="seedprod-error-message">
					<div class="seedprod-error-icon">
						<span class="dashicons dashicons-warning"></span>
					</div>
					<h3>' + (seedprod_admin.strings.unable_load_templates || 'Unable to Load Templates') + '</h3>
					<p>${message}</p>
					${canCreateBlank ? `
					<div class="seedprod-error-actions">
						<p>' + (seedprod_admin.strings.create_blank_template || 'You can still create a page with a blank template:') + '</p>
						<button class="button button-primary seedprod-blank-template-fallback">
							<span class="dashicons dashicons-edit"></span>
							' + (seedprod_admin.strings.start_blank_template || 'Start with Blank Template') + '
						</button>
						<p class="seedprod-error-note">' + (seedprod_admin.strings.refresh_templates || 'or try refreshing the page to reload templates') + '</p>
					</div>
					` : `
					<div class="seedprod-error-actions">
						<button class="button button-secondary" onclick="location.reload()">
							<span class="dashicons dashicons-update"></span>
							' + (seedprod_admin.strings.refresh_page || 'Refresh Page') + '
						</button>
					</div>
					`}
				</div>
			`;
			$container.append(errorHtml);
		}
		
		/**
		 * Show no templates message
		 */
		function showNoTemplatesMessage($container, message) {
			$container.find('.seedprod-template-card').remove();
			$container.find('.seedprod-templates-loading').remove();
			
			var noTemplatesHtml = `
				<div class="seedprod-no-templates">
					<p>${message}</p>
				</div>
			`;
			$container.append(noTemplatesHtml);
		}
		
		/**
		 * Set template hover state
		 */
		function setTemplateHover(templateId, isHovering) {
			templateState.templateHover = isHovering ? templateId : null;
			
			// Show/hide overlays
			$('.seedprod-template-overlay').hide();
			if (isHovering && templateId) {
				$('.seedprod-template-card[data-template-id="' + templateId + '"] .seedprod-template-overlay').show();
			}
		}
		
		/**
		 * Select template and redirect to builder
		 */
		function selectTemplate(templateId) {
			// Check if this is a Pro template in Lite view FIRST (before checking page data)
			if (seedprodTemplateData && seedprodTemplateData.isLiteView && templateId !== '99999') {
				// Find the template in our state to check if it's Pro
				var template = null;
				if (templateState.templates) {
					template = templateState.templates.find(function(t) {
						return t.id == templateId;
					});
				}
				
				// Check if it requires email subscription FIRST (free_w_email == 1)
				// These templates have free: 0 but are actually free with email, so check them first
				if (template && template.free_w_email == 1 && !seedprodTemplateData.freeTemplatesSubscribed) {
					// Scroll to top to show subscription form
					window.scrollTo({ top: 0, behavior: 'smooth' });
					// Add highlight effect to the subscription banner
					$('.seedprod-free-templates-banner').addClass('seedprod-highlight');
					// Remove highlight after animation
					setTimeout(function() {
						$('.seedprod-free-templates-banner').removeClass('seedprod-highlight');
					}, 3000);
					alert(seedprod_admin.strings.email_subscribe_required || 'Please subscribe with your email above to unlock this FREE template.');
					return;
				}

				// Check if it's a Pro template (free == 0 but not free_w_email)
				if (template && template.free == 0 && template.free_w_email != 1) {
					// Redirect to upgrade page
					window.open(seedprod_admin.urls.upgrade_lite, '_blank');
					return;
				}
			}
			
			// Only check for page data if we're actually going to create a page
			if (typeof seedprodTemplateData === 'undefined' || !seedprodTemplateData.pageName) {
				// If we're just testing, show a different message
				if (window.location.href.indexOf('test_lite=1') > -1) {
					alert(seedprod_admin.strings.test_mode_notice);
				} else {
					alert(seedprod_admin.strings.page_data_missing);
				}
				return;
			}
			
		// Show loading state
		var $button = $('.seedprod-template-select[data-template-id="' + templateId + '"]');
		var $templateCard = $button.closest('.seedprod-template-card');
		var $overlay = $button.closest('.seedprod-template-overlay');

		// Keep overlay visible during processing
		$templateCard.addClass('coming-sooncessing');
		$overlay.addClass('coming-sooncessing-active');

		$button.prop('disabled', true);
		$button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-update');
		$button.find('.dashicons').addClass('seedprod-spin');
		
		// Blank template should also go through page creation process
		// (Removed direct redirect - blank template needs to create a page too)

		// Prepare data for request
		var requestData = {
			action: 'seedprod_lite_v2_create_page_from_template',
			template_id: templateId,
			page_name: seedprodTemplateData.pageName,
			page_slug: seedprodTemplateData.pageSlug,
			page_type: seedprodTemplateData.pageType,
			_wpnonce: seedprodTemplateData.nonce
		};

		// Include page_id if we're updating an existing page (edge case)
		if (seedprodTemplateData.pageId) {
			requestData.page_id = seedprodTemplateData.pageId;
		}

		// Create page from template
		$.ajax({
			url: seedprodTemplateData.ajaxUrl,
			type: 'POST',
			data: requestData,
			success: function(response) {
				if (response.success && response.data.builder_url) {
					window.location.href = response.data.builder_url;
				} else {
					alert(response.data || seedprod_admin.strings.template_create_error || 'Could not create page from template. Please try again.');
					resetSelectButton($button);
				}
			},
			error: function() {
				alert(seedprod_admin.strings.network_error || 'Network error. Please try again.');
				resetSelectButton($button);
			}
		});
	}
		
		/**
		 * Reset select button state
		 */
		function resetSelectButton($button) {
			var $templateCard = $button.closest('.seedprod-template-card');
			var $overlay = $button.closest('.seedprod-template-overlay');

			// Remove processing classes
			$templateCard.removeClass('coming-sooncessing');
			$overlay.removeClass('coming-sooncessing-active');

			$button.prop('disabled', false);
			$button.find('.dashicons').removeClass('dashicons-update seedprod-spin').addClass('dashicons-yes');
		}
		
		/**
		 * Preview template in modal
		 */
		function previewTemplate(templateId, templateName) {
			// Phase 1: Use static CDN previews from assets.seedprod.com (matching Vue.js implementation)
			var previewUrl = 'https://assets.seedprod.com/preview-' + templateId + '.html';
			
			// Phase 2 TODO: Implement device switcher functionality
			// - Add device parameter to URL
			// - Handle responsive iframe sizing
			// - Update active device button state
			
			$('#seedprod-preview-title').text(templateName);
			$('#seedprod-preview-iframe').attr('src', previewUrl);
			$('#seedprod-template-preview-modal').show();
		}
		
		/**
		 * Close preview modal
		 */
		function closePreviewModal() {
			$('#seedprod-template-preview-modal').hide();
			$('#seedprod-preview-iframe').attr('src', '');
		}
		
		/**
		 * Toggle favorite status
		 */
		function toggleFavorite(templateId) {
			var $favoriteButton = $('.seedprod-favorite-toggle[data-template-id="' + templateId + '"]');
			var isFavorited = $favoriteButton.hasClass('seedprod-favorited');
			var method = isFavorited ? 'detach' : 'attach';
			
			// Optimistically update UI
			if (isFavorited) {
				$favoriteButton.removeClass('seedprod-favorited');
			} else {
				$favoriteButton.addClass('seedprod-favorited');
			}
			
			$.ajax({
				url: seedprodTemplateData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_toggle_favorite_template',
					template_id: templateId,
					method: method,
					_wpnonce: seedprodTemplateData.nonce
				},
				success: function(response) {
					if (response.success) {
						var isFavorite = response.data.is_favorite;
						
						// Update heart icon state to match server response
						if (isFavorite) {
							$favoriteButton.addClass('seedprod-favorited');
						} else {
							$favoriteButton.removeClass('seedprod-favorited');
						}
						
						// Update template state
						for (var i in templateState.templates) {
							if (templateState.templates[i].id == templateId) {
								templateState.templates[i].is_favorite = isFavorite;
								break;
							}
						}
						
						// If we're on favorites tab and unfavoriting, remove from display
						if (templateState.currentTab === 'favorite-templates' && !isFavorite) {
							$('.seedprod-template-card[data-template-id="' + templateId + '"]').fadeOut(function() {
								$(this).remove();
								// Check if any favorites left
								if ($('#favorite-templates-grid .seedprod-template-card').length === 0) {
									showNoTemplatesMessage($('#favorite-templates-grid'), seedprod_admin.strings.no_favorites_found || 'No favorite templates found. Click the heart icon on any template to add it to your favorites.');
								}
							});
						}
					} else {
						// Revert optimistic update on failure
						if (method === 'attach') {
							$favoriteButton.removeClass('seedprod-favorited');
						} else {
							$favoriteButton.addClass('seedprod-favorited');
						}
						console.error(seedprod_admin.strings.favorite_toggle_error || 'Could not toggle favorite:', response.data);
					}
				},
				error: function() {
					// Revert optimistic update on error
					if (method === 'attach') {
						$favoriteButton.removeClass('seedprod-favorited');
					} else {
						$favoriteButton.addClass('seedprod-favorited');
					}
					console.error(seedprod_admin.strings.favorite_network_error || 'Network error when toggling favorite');
				}
			});
		}
		
		/**
		 * Initialize masonry layout
		 */
		function initializeMasonryLayout($container) {
			// Wait for images to load before calculating positions
			var $images = $container.find('.seedprod-template-image');
			var imagesToLoad = $images.length;
			var imagesLoaded = 0;
			
			// If no images, layout immediately
			if (imagesToLoad === 0) {
				applyMasonryLayout($container);
				return;
			}
			
			// Wait for each image to load
			$images.each(function() {
				var $img = $(this);
				var img = new Image();
				
				img.onload = img.onerror = function() {
					imagesLoaded++;
					if (imagesLoaded >= imagesToLoad) {
						// Small delay to ensure DOM is updated
						setTimeout(function() {
							applyMasonryLayout($container);
						}, 50);
					}
				};
				
				// Trigger load (in case image is cached)
				if ($img.get(0).complete) {
					img.onload();
				} else {
					img.src = $img.attr('src');
				}
			});
		}
		
		/**
		 * Apply masonry layout to container
		 */
		function applyMasonryLayout($container) {
			var $cards = $container.find('.seedprod-template-card');
			var containerWidth = $container.width();
			var cardMargin = 24;
			
			// Calculate number of columns based on container width
			var columns;
			if (containerWidth > 1500) columns = 5;
			else if (containerWidth > 1000) columns = 4;
			else if (containerWidth > 700) columns = 3;
			else if (containerWidth > 400) columns = 2;
			else columns = 1;
			
			var cardWidth = Math.floor((containerWidth - (cardMargin * (columns - 1))) / columns);
			var columnHeights = new Array(columns).fill(0);
			
			// Position each card
			$cards.each(function() {
				var $card = $(this);
				
				// Set card width
				$card.css('width', cardWidth + 'px');
				
				// Find shortest column
				var shortestColumn = 0;
				var shortestHeight = columnHeights[0];
				for (var i = 1; i < columns; i++) {
					if (columnHeights[i] < shortestHeight) {
						shortestHeight = columnHeights[i];
						shortestColumn = i;
					}
				}
				
				// Position card
				var left = shortestColumn * (cardWidth + cardMargin);
				var top = columnHeights[shortestColumn];
				
				$card.css({
					'position': 'absolute',
					'left': left + 'px',
					'top': top + 'px'
				});
				
				// Update column height
				columnHeights[shortestColumn] += $card.outerHeight() + cardMargin;
			});
			
			// Set container height
			var maxHeight = Math.max.apply(Math, columnHeights);
			$container.css({
				'position': 'relative',
				'height': maxHeight + 'px'
			});
		}
		
		// Recalculate masonry on window resize
		$(window).on('resize.seedprodMasonry', function() {
			if ($('.seedprod-templates-grid').length) {
				clearTimeout(templateState.resizeTimeout);
				templateState.resizeTimeout = setTimeout(function() {
					$('.seedprod-templates-grid').each(function() {
						var $container = $(this);
						if ($container.find('.seedprod-template-card').length > 0) {
							applyMasonryLayout($container);
						}
					});
				}, 250);
			}
		});
	}
	
	/**
	 * Website Builder Theme Toggle
	 */
	function initWebsiteBuilderThemeToggle() {
		var $toggle = $('#seedprod-theme-toggle');
		if (!$toggle.length) return;
		
		var isProcessing = false;
		
		$toggle.on('change', function() {
			if (isProcessing) return;
			
			var $this = $(this);
			var enabled = $this.is(':checked');
			var $label = $this.closest('.seedprod-theme-toggle-control').find('.seedprod-toggle-label');
			var $stats = $('.seedprod-theme-stats');
			
			// Show loading state
			isProcessing = true;
			$this.prop('disabled', true);
			$label.html('<span style="padding: 3px 8px; border-radius: 3px; background-color: #f0f0f0;"><span class="dashicons dashicons-update spin"></span> ' + seedprod_admin.strings.loading + '</span>');
			
			// Make AJAX request
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_update_theme_enabled',
					nonce: seedprod_admin.v2_nonce,
					enabled: enabled
				},
				success: function(response) {
					if (response.success) {
						// Update UI based on state
						if (response.data.enabled) {
							$label.html('<span class="active">' + seedprod_admin.strings.status_active + '</span>');
							$stats.fadeIn();
							
							// Check if we should prompt to create default pages
							checkDefaultPages();
						} else {
							$label.html('<span class="inactive">' + seedprod_admin.strings.status_inactive + '</span>');
							$stats.fadeOut();
						}
						
						// Show success message
						showNotice(response.data.message, 'success');
						
						// Scroll to top to show notice
						$('html, body').animate({ scrollTop: 0 }, 300);
					} else {
						// Revert toggle on error
						$this.prop('checked', !enabled);
						$label.html('<span class="' + (!enabled ? 'active' : 'inactive') + '">' + 
							(!enabled ? seedprod_admin.strings.status_active : seedprod_admin.strings.status_inactive) + '</span>');
						
						// Show error message
						showNotice(response.data || seedprod_admin.strings.settings_error, 'error');
						
						// Scroll to top to show error
						$('html, body').animate({ scrollTop: 0 }, 300);
					}
				},
				error: function() {
					// Revert toggle on error
					$this.prop('checked', !enabled);
					$label.html('<span class="' + (!enabled ? 'active' : 'inactive') + '">' + 
						(!enabled ? seedprod_admin.strings.status_active : seedprod_admin.strings.status_inactive) + '</span>');
					
					showNotice(seedprod_admin.strings.settings_error, 'error');
					
					// Scroll to top to show error
					$('html, body').animate({ scrollTop: 0 }, 300);
				},
				complete: function() {
					isProcessing = false;
					$this.prop('disabled', false);
				}
			});
		});
	}
	
	/**
	 * Check if we should prompt to create default pages
	 */
	function checkDefaultPages() {
		// Check if WordPress is using static pages
		$.ajax({
			url: seedprod_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'seedprod_lite_v2_check_default_pages',
				nonce: seedprod_admin.v2_nonce
			},
			success: function(response) {
				if (response.success && response.data.should_create) {
					// Show prompt to create default pages
					if (confirm(seedprod_admin.strings.create_default_pages_prompt)) {
						createDefaultPages();
					}
				}
			},
			error: function(xhr, status, error) {
				console.error('Error checking default pages:', error);
			}
		});
	}

	/**
	 * Create default home and blog pages
	 */
	function createDefaultPages() {
		$.ajax({
			url: seedprod_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'seedprod_lite_v2_create_default_pages',
				nonce: seedprod_admin.v2_nonce
			},
			success: function(response) {
				if (response.success) {
					showNotice(response.data.message, 'success');
					// Optionally refresh the page to show the new setup
					setTimeout(function() {
						window.location.reload();
					}, 2000);
				} else {
					showNotice(response.message || 'Failed to create default pages', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('Error creating default pages:', error);
				showNotice('An error occurred while creating default pages', 'error');
			}
		});
	}
	
	/**
	 * Show admin notice
	 */
	function showNotice(message, type) {
		var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
		var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible seedprod-admin-notice"><p>' + message + '</p></div>');
		
		// Insert at beginning of dashboard container for proper indentation
		$('.seedprod-dashboard-container').prepend($notice);
		
		// Auto-dismiss after 5 seconds
		setTimeout(function() {
			$notice.fadeOut(function() {
				$(this).remove();
			});
		}, 5000);
		
		// Make dismissible
		$notice.on('click', '.notice-dismiss', function() {
			$notice.fadeOut(function() {
				$(this).remove();
			});
		});
	}
	
	/**
	 * Theme Templates DataTable Functions
	 */
	function initThemeTemplatesTable() {
		// Toggle template publish status
		$(document).on('change', '.seedprod-template-toggle', function() {
			var $toggle = $(this);
			var templateId = $toggle.data('id');
			var nonce = $toggle.data('nonce');
			var $row = $toggle.closest('tr');
			
			// Disable toggle during request
			$toggle.prop('disabled', true);
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_toggle_template_status',
					template_id: templateId,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						// Update row styling if needed
						if (response.data.status === 'publish') {
							$row.removeClass('status-draft').addClass('status-publish');
						} else {
							$row.removeClass('status-publish').addClass('status-draft');
						}
						showNotice(response.data.message, 'success');
					} else {
						// Revert toggle
						$toggle.prop('checked', !$toggle.is(':checked'));
						showNotice(response.data || seedprod_admin.strings.error, 'error');
					}
				},
				error: function() {
					// Revert toggle
					$toggle.prop('checked', !$toggle.is(':checked'));
					showNotice(seedprod_admin.strings.error, 'error');
				},
				complete: function() {
					$toggle.prop('disabled', false);
				}
			});
		});
		
		// Duplicate template
		$(document).on('click', '.seedprod-duplicate-template', function(e) {
			e.preventDefault();

			var $link = $(this);
			var templateId = $link.data('id');
			var nonce = $link.data('nonce');

			// Prevent duplicate clicks
			if ($link.hasClass('duplicating')) {
				return;
			}

			if (!confirm(seedprod_admin.strings.confirm_duplicate)) {
				return;
			}

			$link.addClass('duplicating disabled');
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_duplicate_template',
					template_id: templateId,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						showNotice(response.data.message, 'success');
						// Always reload after a short delay for consistency
						setTimeout(function() {
							location.reload();
						}, 700);
					} else {
						showNotice(response.data || seedprod_admin.strings.error, 'error');
						$link.removeClass('duplicating disabled');
					}
				},
				error: function() {
					showNotice(seedprod_admin.strings.error, 'error');
					$link.removeClass('duplicating disabled');
				}
			});
		});
		
		// Trash template
		$(document).on('click', '.seedprod-trash-template', function(e) {
			e.preventDefault();

			var $link = $(this);
			var templateId = $link.data('id');
			var nonce = $link.data('nonce');

			// Prevent duplicate clicks
			if ($link.hasClass('trashing')) {
				return;
			}

			if (!confirm(seedprod_admin.strings.confirm_trash)) {
				return;
			}

			$link.addClass('trashing disabled');
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_trash_template',
					template_id: templateId,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						showNotice(response.data.message, 'success');
						// Always reload after a short delay for consistency
						setTimeout(function() {
							location.reload();
						}, 700);
					} else {
						showNotice(response.data || seedprod_admin.strings.error, 'error');
						$link.removeClass('trashing disabled');
					}
				},
				error: function() {
					showNotice(seedprod_admin.strings.error, 'error');
					$link.removeClass('trashing disabled');
				}
			});
		});
		
		// Restore template
		$(document).on('click', '.seedprod-restore-template', function(e) {
			e.preventDefault();

			var $link = $(this);
			var templateId = $link.data('id');
			var nonce = $link.data('nonce');

			// Prevent duplicate clicks
			if ($link.hasClass('restoring')) {
				return;
			}

			$link.addClass('restoring disabled');
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_restore_template',
					template_id: templateId,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						showNotice(response.data.message, 'success');
						// Always reload after a short delay for consistency
						setTimeout(function() {
							location.reload();
						}, 700);
					} else {
						showNotice(response.data || seedprod_admin.strings.error, 'error');
						$link.removeClass('restoring disabled');
					}
				},
				error: function() {
					showNotice(seedprod_admin.strings.error, 'error');
					$link.removeClass('restoring disabled');
				}
			});
		});
		
		// Delete template permanently
		$(document).on('click', '.seedprod-delete-template', function(e) {
			e.preventDefault();

			var $link = $(this);
			var templateId = $link.data('id');
			var nonce = $link.data('nonce');

			// Prevent duplicate clicks
			if ($link.hasClass('deleting')) {
				return;
			}

			if (!confirm(seedprod_admin.strings.confirm_delete)) {
				return;
			}

			$link.addClass('deleting disabled');
			
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_delete_template',
					template_id: templateId,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						showNotice(response.data.message, 'success');
						// Always reload after a short delay for consistency
						setTimeout(function() {
							location.reload();
						}, 700);
					} else {
						showNotice(response.data || seedprod_admin.strings.error, 'error');
						$link.removeClass('deleting disabled');
					}
				},
				error: function() {
					showNotice(seedprod_admin.strings.error, 'error');
					$link.removeClass('deleting disabled');
				}
			});
		});

		// Handle bulk actions for theme templates
		$('#doaction, #doaction2').on('click', function(e) {
			console.log('Theme bulk action button clicked');
			e.preventDefault(); // Stop any form submission first

			var action = $(this).prev('select').val();
			console.log('Theme action selected:', action);

			if (action === '-1' || action === '') {
				return;
			}

			// Only handle on website builder page
			var currentPage = new URLSearchParams(window.location.search).get('page');
			if (currentPage !== 'seedprod_lite_website_builder') {
				return;
			}

			// Get selected template IDs
			var templateIds = [];
			$('input[name="template_id[]"]:checked').each(function() {
				templateIds.push($(this).val());
			});

			console.log('Theme templates found:', templateIds.length, 'IDs:', templateIds);

			if (templateIds.length === 0) {
				alert(seedprod_admin.strings.no_items_selected || 'Please select at least one template.');
				return;
			}

			// Confirm action
			var confirmMessage = '';
			switch (action) {
				case 'trash':
					confirmMessage = seedprod_admin.strings.confirm_bulk_trash || 'Are you sure you want to trash the selected templates?';
					break;
				case 'restore':
					confirmMessage = seedprod_admin.strings.confirm_bulk_restore || 'Are you sure you want to restore the selected templates?';
					break;
				case 'delete':
					confirmMessage = seedprod_admin.strings.confirm_bulk_delete || 'Are you sure you want to permanently delete the selected templates?';
					break;
				default:
					return;
			}

			if (!confirm(confirmMessage)) {
				return;
			}

			// Prevent duplicate clicks
			var $button = $(this);
			if ($button.hasClass('bulk-processing')) {
				return;
			}

			$button.addClass('bulk-processing').prop('disabled', true);

			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_bulk_action_templates',
					bulk_action: action,
					template_ids: templateIds,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					console.log('Theme bulk action response:', response);
					if (response.success) {
						showNotice(response.data.message, 'success');
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						showNotice(response.data.message || seedprod_admin.strings.error, 'error');
						$button.removeClass('bulk-processing').prop('disabled', false);
					}
				},
				error: function(xhr, status, error) {
					console.log('Theme bulk action error:', xhr, status, error);
					showNotice(seedprod_admin.strings.error, 'error');
					$button.removeClass('bulk-processing').prop('disabled', false);
				}
			});
		});

	}
	
	/**
	 * Initialize Add New Template Modal
	 */
	function initAddNewTemplateModal() {
		var $modal = $('#seedprod-new-template-modal');
		if (!$modal.length) return;
		
		var $form = $('#seedprod-new-template-form');
		var $overlay = $modal.find('.seedprod-modal-overlay');
		var $closeBtn = $modal.find('.seedprod-modal-close');
		var $cancelBtn = $modal.find('.seedprod-modal-cancel');
		var $createBtn = $('#seedprod-create-template-btn');
		var $templateType = $('#template-type');
		var $templatePriority = $('#template-priority');
		var $conditionsSection = $('#template-conditions-section');
		var $conditionsList = $('#template-conditions-list');
		var $addConditionBtn = $('.seedprod-add-template-condition');
		
	var templateConditions = [];
	
	// Use PHP-provided conditions from localized script
	// This ensures conditions match the server-side logic and include WooCommerce + dynamic post types
	var conditionsMap = seedprod_admin.conditions || {
		'General': [
			{ value: '_entire_site', text: 'Entire Site' },
			{ value: 'is_front_page', text: 'Front Page' },
			{ value: 'is_home', text: 'Blog Page' },
			{ value: 'is_page(x)', text: 'Pages' },
			{ value: 'is_singular', text: 'Pages and Posts' },
			{ value: 'is_404', text: 'Is 404' },
			{ value: 'is_author(x)', text: 'Is Author' }
		],
		'Posts': [
			{ value: 'is_single(x)', text: 'Posts' },
			{ value: 'has_category(x)', text: 'Has Category' },
			{ value: 'has_tag(x)', text: 'Has Tag' }
		],
		'Archives': [
			{ value: 'is_archive', text: 'All Archives' },
			{ value: 'is_date', text: 'Date Archives' },
			{ value: 'is_search', text: 'Search Results' },
			{ value: 'is_category(x)', text: 'Post Category Archives' },
			{ value: 'is_tag(x)', text: 'Post Tag Archives' }
		]
	};
		
		// Open modal when Add New Template button is clicked
		$(document).on('click', '.seedprod-add-new-template-btn', function(e) {
			e.preventDefault();
			openModal();
		});
		
		// Close modal handlers
		$overlay.on('click', closeModal);
		$closeBtn.on('click', closeModal);
		$cancelBtn.on('click', closeModal);
		
		// Handle ESC key
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $modal.is(':visible')) {
				closeModal();
			}
		});
		
		// Handle template type change
		$templateType.on('change', function() {
			var type = $(this).val();
			
			if (!type) {
				// Hide conditions if no type selected
				$conditionsSection.hide();
				$conditionsList.empty();
				templateConditions = [];
				return;
			}
			
			// Template parts don't need conditions
			if (type === 'part') {
				$conditionsSection.hide();
				$conditionsList.empty();
				templateConditions = [];
				$templatePriority.val(20);
				return;
			}
			
			// Show conditions section for all other types
			$conditionsSection.show();
			
			// Clear existing conditions
			$conditionsList.empty();
			templateConditions = [];
			
			// Set default priority and conditions based on type
			switch(type) {
				case 'header':
				case 'footer':
					// Headers and footers default to entire site
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: '_entire_site', 
						value: '' 
					});
					break;
					
				case 'single_page':
					// Single page defaults to all pages
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: 'is_page(x)', 
						value: '' 
					});
					break;
					
				case 'single_post':
					// Single post defaults to all posts
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: 'is_single(x)', 
						value: '' 
					});
					break;
					
				case 'archive':
					// Archive defaults to all archives
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: 'is_archive', 
						value: '' 
					});
					break;
					
				case 'search':
					// Search results page
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: 'is_search', 
						value: '' 
					});
					break;
					
				case 'author':
					// Author page
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: 'is_author(x)', 
						value: '' 
					});
					break;
					
				case 'custom':
					// Custom - let user define everything
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: '', 
						value: '' 
					});
					break;
					
				case 'single_product':
					// WooCommerce single product
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: 'is_singular(product)', 
						value: '' 
					});
					break;
					
				case 'archive_product':
					// WooCommerce product archive
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: 'is_post_type_archive(product)', 
						value: '' 
					});
					break;
					
				default:
					// Default: entire site with lower priority
					$templatePriority.val(20);
					addTemplateConditionRow({ 
						condition: 'include', 
						type: '_entire_site', 
						value: '' 
					});
					break;
			}
		});
		
		// Add new condition button
		$addConditionBtn.on('click', function() {
			addTemplateConditionRow();
		});
		
		// Remove condition
		$(document).on('click', '#template-conditions-list .seedprod-remove-condition', function() {
			var $row = $(this).closest('.seedprod-condition-row');
			var index = $row.index();
			
			// Remove from array
			templateConditions.splice(index, 1);
			
			// Remove from DOM
			$row.remove();
			
			// Update UI
			updateTemplateConditionsUI();
		});
		
		// Handle condition type change
		$(document).on('change', '#template-conditions-list .seedprod-condition-type', function() {
			var $row = $(this).closest('.seedprod-condition-row');
			var selectedValue = $(this).val();
			var $valueInput = $row.find('.seedprod-condition-value');
			var index = $row.index();
			
			// Update conditions array
			if (templateConditions[index]) {
				templateConditions[index].type = selectedValue;
			}
			
			// Show/hide value input based on condition type
			if (selectedValue.includes('(x)')) {
				$valueInput.show().attr('placeholder', 'Enter value (e.g., page ID, category name)');
			} else {
				$valueInput.hide().val('');
				if (templateConditions[index]) {
					templateConditions[index].value = '';
				}
			}
		});
		
		// Handle condition value change
		$(document).on('change', '#template-conditions-list .seedprod-condition-value', function() {
			var $row = $(this).closest('.seedprod-condition-row');
			var index = $row.index();
			
			if (templateConditions[index]) {
				templateConditions[index].value = $(this).val();
			}
		});
		
		// Handle include/exclude toggle
		$(document).on('change', '#template-conditions-list .seedprod-condition-mode', function() {
			var $row = $(this).closest('.seedprod-condition-row');
			var index = $row.index();
			
			$row.toggleClass('is-excluded', $(this).val() === 'exclude');
			
			if (templateConditions[index]) {
				templateConditions[index].condition = $(this).val();
			}
		});
		
		// Handle form submission
		$createBtn.on('click', function(e) {
			e.preventDefault();
			
			// Validate form
			var templateName = $('#template-name').val().trim();
			var templateType = $('#template-type').val();
			var templatePriority = $('#template-priority').val() || 10;
			
			if (!templateName) {
				showFieldError('#template-name', seedprod_admin.strings.template_name_required || 'Template name is required');
				return;
			}
			
			if (!templateType) {
				showFieldError('#template-type', seedprod_admin.strings.template_type_required || 'Please select a template type');
				return;
			}
			
			// Clear any previous errors
			clearFieldErrors();
			
			// Show loading state
			var $buttonText = $createBtn.find('.button-text');
			var $spinner = $createBtn.find('.spinner');
			$createBtn.prop('disabled', true);
			$buttonText.hide();
			$spinner.show();
			
			// Prepare conditions for submission
			var finalConditions = [];
			templateConditions.forEach(function(cond) {
				if (cond.type) {
					var condition = {
						type: cond.type,
						condition: cond.condition || 'include',
						value: cond.value || ''
					};

					// Keep type as-is with (x) placeholder, don't embed value
					// Value stays separate in the value field

					finalConditions.push(condition);
				}
			});
			
			// Make AJAX request
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_create_template',
					template_name: templateName,
					template_type: templateType,
					template_priority: templatePriority,
					template_conditions: JSON.stringify(finalConditions),
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						// Close modal
						closeModal();
						
						// Show success message
						showNotice('success', response.data.message || 'Template created successfully');
						
						// Redirect to builder
						if (response.data.redirect_url) {
							window.location.href = response.data.redirect_url;
						} else if (response.data.template_id) {
							// Fallback: construct builder URL
							var builderUrl = seedprod_admin.admin_url + 'admin.php?page=seedprod_lite_builder&id=' + response.data.template_id + '#/';
							window.location.href = builderUrl;
						}
					} else {
						// Show error
						showModalError(response.data || 'Failed to create template');
					}
				},
				error: function() {
					showModalError(seedprod_admin.strings.template_error || 'An error occurred. Please try again.');
				},
				complete: function() {
					// Reset button state
					$createBtn.prop('disabled', false);
					$buttonText.show();
					$spinner.hide();
				}
			});
		});
		
		// Helper: Open modal
		function openModal() {
			// Reset form
			$form[0].reset();
			clearFieldErrors();
			clearModalError();
			
			// Reset conditions
			$conditionsSection.hide();
			$conditionsList.empty();
			templateConditions = [];
			
			// Reset priority
			$templatePriority.val(20);
			
			// Show modal
			$modal.fadeIn(200);
			$('body').addClass('seedprod-modal-open');
			
			// Focus first input
			setTimeout(function() {
				$('#template-name').focus();
			}, 250);
		}
		
		// Helper: Close modal
		function closeModal() {
			$modal.fadeOut(200);
			$('body').removeClass('seedprod-modal-open');
		}
		
		// Helper: Add template condition row
		function addTemplateConditionRow(condition) {
			condition = condition || { type: '', value: '', condition: 'include' };
			
			// Add to array
			templateConditions.push(condition);
			
			var rowHtml = '<div class="seedprod-condition-row' + (condition.condition === 'exclude' ? ' is-excluded' : '') + '">';
			rowHtml += '<select class="seedprod-condition-mode">';
			rowHtml += '<option value="include"' + (condition.condition === 'include' ? ' selected' : '') + '>Include</option>';
			rowHtml += '<option value="exclude"' + (condition.condition === 'exclude' ? ' selected' : '') + '>Exclude</option>';
			rowHtml += '</select>';
			
			rowHtml += '<select class="seedprod-condition-type">';
			rowHtml += '<option value="">' + (seedprod_admin.strings.condition_select_type || 'Select Type') + '</option>';

			for (var group in conditionsMap) {
				rowHtml += '<optgroup label="' + group + '">';
				conditionsMap[group].forEach(function(opt) {
					var selected = (opt.value === condition.type) ? ' selected' : '';
					rowHtml += '<option value="' + opt.value + '"' + selected + '>' + opt.text + '</option>';
				});
				rowHtml += '</optgroup>';
			}

			rowHtml += '</select>';

			var showValue = condition.type && condition.type.includes('(x)');
			var placeholderText = seedprod_admin.strings.condition_enter_value || 'Enter ids or slugs';
			rowHtml += '<input type="text" class="seedprod-condition-value" placeholder="' + placeholderText + '" value="' + (condition.value || '') + '"';
			if (!showValue) {
				rowHtml += ' style="display: none;"';
			}
			rowHtml += '>';
			
			rowHtml += '<button type="button" class="seedprod-remove-condition">';
			rowHtml += '<span class="dashicons dashicons-trash"></span>';
			rowHtml += '</button>';
			rowHtml += '</div>';
			
			$conditionsList.append(rowHtml);
			updateTemplateConditionsUI();
		}
		
		// Helper: Update conditions UI
		function updateTemplateConditionsUI() {
			// Show remove button only if more than one condition
			var $rows = $('#template-conditions-list .seedprod-condition-row');
			if ($rows.length <= 1) {
				$rows.find('.seedprod-remove-condition').hide();
			} else {
				$rows.find('.seedprod-remove-condition').show();
			}
		}
		
		// Helper: Show field error
		function showFieldError(fieldSelector, message) {
			var $field = $(fieldSelector);
			var $group = $field.closest('.seedprod-form-group');
			
			// Remove any existing error
			$group.find('.seedprod-field-error').remove();
			
			// Add error class and message
			$field.addClass('error');
			$('<span class="seedprod-field-error">' + message + '</span>').insertAfter($field);
		}
		
		// Helper: Clear field errors
		function clearFieldErrors() {
			$form.find('.error').removeClass('error');
			$form.find('.seedprod-field-error').remove();
		}
		
		// Helper: Show modal error
		function showModalError(message) {
			clearModalError();
			var $error = $('<div class="seedprod-modal-error"><span class="dashicons dashicons-warning"></span> ' + message + '</div>');
			$form.prepend($error);
		}
		
		// Helper: Clear modal error
		function clearModalError() {
			$form.find('.seedprod-modal-error').remove();
		}
		
		// Helper: Show notice
		function showNotice(type, message) {
			var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
			var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
			
			// Insert notice after page title
			$('.seedprod-dashboard-page .seedprod-header').after($notice);
			
			// Auto dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
			
			// Make dismissible
			$notice.on('click', '.notice-dismiss', function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			});
		}
	}
	
	/**
	 * Initialize Edit Conditions Modal
	 */
	function initEditConditionsModal() {
		var $modal = $('#seedprod-conditions-modal');
		if (!$modal.length) return;
		
		var $overlay = $modal.find('.seedprod-modal-overlay');
		var $closeBtn = $modal.find('.seedprod-modal-close');
		var $cancelBtn = $modal.find('.seedprod-modal-cancel');
		var $saveBtn = $('#seedprod-save-conditions-btn');
		var $conditionsList = $('#seedprod-conditions-list');
		var $addConditionBtn = $('.seedprod-add-condition');
		var $templateName = $('#seedprod-template-name');
		var $templateType = $('#seedprod-template-type-display');
		var $templatePriority = $('#seedprod-template-priority');
		
		var currentTemplateId = null;

		// Conditions options - use PHP-provided conditions from localized script
		// This ensures conditions match the server-side logic and include dynamic post types
		var conditionsMap = seedprod_admin.conditions || {};
		
		// Condition modes from PHP (Include, Exclude, Custom)
		var conditionModes = seedprod_admin.condition_modes || {
			'include': 'Include',
			'exclude': 'Exclude',
			'custom': 'Custom'
		};
		
		// Template type labels
		var typeLabels = {
			'header': 'Header',
			'footer': 'Footer',
			'part': 'Template Part',
			'page': 'Page',
			'post': 'Single Post',
			'archive': 'Archive',
			'search': 'Search',
			'author': 'Author',
			'single_page': 'Single Page',
			'single_post': 'Single Post',
			'single_product': 'Single Product',
			'archive_product': 'Product Archive',
			'woocommerce_single': 'Single Product',
			'woocommerce_archive': 'Product Archive'
		};
		
		// Open modal when Edit Conditions is clicked
		$(document).on('click', '.seedprod-edit-conditions', function(e) {
			e.preventDefault();
			currentTemplateId = $(this).data('id');
			openModal();
		});
		
		// Close modal handlers
		$overlay.on('click', closeModal);
		$closeBtn.on('click', closeModal);
		$cancelBtn.on('click', closeModal);
		
		// Handle ESC key
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $modal.is(':visible')) {
				closeModal();
			}
		});
		
		// Add new condition
		$addConditionBtn.on('click', function() {
			addConditionRow();
		});
		
		// Remove condition
		$(document).on('click', '.seedprod-remove-condition', function() {
			$(this).closest('.seedprod-condition-row').remove();
			updateConditionsUI();
		});
		
		// Handle condition type change
		$(document).on('change', '.seedprod-condition-type', function() {
			var $row = $(this).closest('.seedprod-condition-row');
			var selectedValue = $(this).val();
			var $valueInput = $row.find('.seedprod-condition-value');

			// Show/hide value input based on condition type
			if (selectedValue.includes('(x)')) {
				$valueInput.show().attr('placeholder', seedprod_admin.strings.condition_enter_value || 'Enter ids or slugs');
			} else {
				$valueInput.hide().val('');
			}
		});
		
		// Handle include/exclude/custom toggle
		$(document).on('change', '.seedprod-condition-mode', function() {
			var $row = $(this).closest('.seedprod-condition-row');
			var selectedMode = $(this).val();
			var $typeSelect = $row.find('.seedprod-condition-type');
			var $valueInput = $row.find('.seedprod-condition-value');
			
			// Update row classes
			$row.toggleClass('is-excluded', selectedMode === 'exclude');
			$row.toggleClass('is-custom', selectedMode === 'custom');
			
			// Handle custom mode
			var placeholderText = seedprod_admin.strings.condition_enter_value || 'Enter ids or slugs';
			if (selectedMode === 'custom') {
				// Hide type selector, show value input
				$typeSelect.hide();
				$valueInput.show().attr('placeholder', placeholderText);
			} else {
				// Show type selector
				$typeSelect.show();
				// Value input visibility depends on selected type
				var typeValue = $typeSelect.val();
				if (typeValue && typeValue.includes('(x)')) {
					$valueInput.show().attr('placeholder', placeholderText);
				} else {
					$valueInput.hide().val('');
				}
			}
		});
		
		// Save conditions
		$saveBtn.on('click', function() {
			saveConditions();
		});
		
		// Helper: Decode HTML entities
		function decodeHtmlEntities(text) {
			var textArea = document.createElement('textarea');
			textArea.innerHTML = text;
			return textArea.value;
		}
		
		// Helper: Open modal
		function openModal() {
			// Clear conditions list
			$conditionsList.empty();
			
			// Show loading
			$conditionsList.html('<div class="spinner is-active" style="float: none; margin: 20px auto;"></div>');
			
			// Fetch current template data
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_get_template_conditions',
					template_id: currentTemplateId,
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					$conditionsList.empty();
					
					if (response.success) {
						// Load template name (decode HTML entities)
						if (response.data.name) {
							$templateName.val(decodeHtmlEntities(response.data.name));
						}
						
						// Load template type
						if (response.data.type) {
							var typeLabel = typeLabels[response.data.type] || response.data.type;
							$templateType.text(typeLabel);
						}
						
						// Load template priority
						if (response.data.priority !== undefined) {
							$templatePriority.val(response.data.priority);
						} else {
							$templatePriority.val(20);
						}
						
						// Load existing conditions
						var conditions = response.data.conditions;
						if (conditions && conditions.length > 0) {
							conditions.forEach(function(condition) {
								addConditionRow(condition);
							});
						} else {
							// Add default empty row
							addConditionRow();
						}
					} else {
						// Add default empty row
						addConditionRow();
					}
				},
				error: function() {
					$conditionsList.empty();
					addConditionRow();
				}
			});
			
			// Show modal
			$modal.fadeIn(200);
			$('body').addClass('seedprod-modal-open');
		}
		
		// Helper: Close modal
		function closeModal() {
			$modal.fadeOut(200);
			$('body').removeClass('seedprod-modal-open');
		}
		
		// Helper: Parse condition to separate type and value
		function parseCondition(condition) {
			condition = condition || { type: '', value: '', condition: 'include' };
			
			// If the type contains a value in parentheses (e.g., "is_page(about)"),
			// extract the value and convert type to placeholder format
			var type = condition.type;
			var value = condition.value || '';
			
			if (type && type.includes('(') && type.includes(')') && !type.includes('(x)')) {
				// Extract value from type like "is_page(about)" -> value: "about", type: "is_page(x)"
				var match = type.match(/^([^\(]+)\(([^\)]+)\)$/);
				if (match) {
					type = match[1] + '(x)';
					value = match[2];
				}
			}
			
			return {
				type: type,
				value: value,
				condition: condition.condition || 'include'
			};
		}
		
		// Helper: Add condition row
		function addConditionRow(conditionData) {
			// Parse the condition to handle both formats
			var condition = parseCondition(conditionData);
			
			var isCustom = (condition.condition === 'custom');
			var rowHtml = '<div class="seedprod-condition-row' + 
						  (condition.condition === 'exclude' ? ' is-excluded' : '') + 
						  (isCustom ? ' is-custom' : '') + '">';
			rowHtml += '<select class="seedprod-condition-mode">';
			
			// Build mode options from PHP data
			for (var modeKey in conditionModes) {
				var selected = (condition.condition === modeKey) ? ' selected' : '';
				rowHtml += '<option value="' + modeKey + '"' + selected + '>' + conditionModes[modeKey] + '</option>';
			}
			
			rowHtml += '</select>';
			
			// Type selector (hidden for custom mode)
			rowHtml += '<select class="seedprod-condition-type"';
			if (isCustom) {
				rowHtml += ' style="display: none;"';
			}
			rowHtml += '>';
			rowHtml += '<option value="">' + (seedprod_admin.strings.condition_select_type || 'Select Type') + '</option>';

			for (var group in conditionsMap) {
				rowHtml += '<optgroup label="' + group + '">';
				conditionsMap[group].forEach(function(opt) {
					var selected = (opt.value === condition.type) ? ' selected' : '';
					rowHtml += '<option value="' + opt.value + '"' + selected + '>' + opt.text + '</option>';
				});
				rowHtml += '</optgroup>';
			}

			rowHtml += '</select>';

			// Value input (shown for custom mode or when type has (x))
			var showValue = isCustom || (condition.type && condition.type.includes('(x)'));
			var placeholderText = seedprod_admin.strings.condition_enter_value || 'Enter ids or slugs';
			rowHtml += '<input type="text" class="seedprod-condition-value" placeholder="' + placeholderText + '" value="' + (condition.value || '') + '"';
			if (!showValue) {
				rowHtml += ' style="display: none;"';
			}
			rowHtml += '>';
			
			rowHtml += '<button type="button" class="seedprod-remove-condition">';
			rowHtml += '<span class="dashicons dashicons-trash"></span>';
			rowHtml += '</button>';
			rowHtml += '</div>';
			
			$conditionsList.append(rowHtml);
			updateConditionsUI();
		}
		
		// Helper: Update UI
		function updateConditionsUI() {
			// Show remove button only if more than one condition
			var $rows = $('.seedprod-condition-row');
			if ($rows.length <= 1) {
				$rows.find('.seedprod-remove-condition').hide();
			} else {
				$rows.find('.seedprod-remove-condition').show();
			}
		}
		
		// Helper: Save conditions
		function saveConditions() {
			// Get template name and priority
			var templateName = $templateName.val().trim();
			var templatePriority = parseInt($templatePriority.val(), 10) || 20;
			
			// Validate template name
			if (!templateName) {
				alert(seedprod_admin.strings.template_name_required);
				$templateName.focus();
				return;
			}
			
			// Collect conditions data
			var conditions = [];
			$('.seedprod-condition-row').each(function() {
				var $row = $(this);
				var conditionMode = $row.find('.seedprod-condition-mode').val();
				var type = $row.find('.seedprod-condition-type').val();
				var value = $row.find('.seedprod-condition-value').val() || '';
				
				// For custom mode or if type is selected
				if (conditionMode === 'custom' || type) {
					var condition = {
						type: type || '',
						condition: conditionMode,
						value: value
					};
					
					conditions.push(condition);
				}
			});
			
			// Show loading
			var $buttonText = $saveBtn.find('.button-text');
			var $spinner = $saveBtn.find('.spinner');
			$saveBtn.prop('disabled', true);
			$buttonText.hide();
			$spinner.show();
			
			// Save via AJAX
			$.ajax({
				url: seedprod_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_save_template_conditions',
					template_id: currentTemplateId,
					template_name: templateName,
					priority: templatePriority,
					conditions: JSON.stringify(conditions),
					nonce: seedprod_admin.v2_nonce
				},
				success: function(response) {
					if (response.success) {
						// Close modal
						closeModal();
						
						// Refresh the table row to show new conditions
						location.reload();
					} else {
						alert(response.data || seedprod_admin.strings.save_conditions_error);
					}
				},
				error: function() {
					alert(seedprod_admin.strings.error_occurred);
				},
				complete: function() {
					// Reset button
					$saveBtn.prop('disabled', false);
					$buttonText.show();
					$spinner.hide();
				}
			});
		}
	}
	
	/**
	 * Initialize Import/Export Modal
	 */
	function initImportExportModal() {
		var $modal = $('#seedprod-import-export-modal');
		if (!$modal.length) return;
		
		// Open modal when button is clicked
		$('#seedprod-import-export-btn').on('click', function(e) {
			e.preventDefault();
			$modal.fadeIn(200);
		});
		
		// Close modal
		$modal.find('.seedprod-modal-close, .seedprod-modal-cancel').on('click', function() {
			$modal.fadeOut(200);
			resetImportForm();
		});
		
		// Close modal when clicking on overlay
		$modal.on('click', function(e) {
			if (e.target === this) {
				$modal.fadeOut(200);
				resetImportForm();
			}
		});
		
		// Tab switching
		$modal.find('.seedprod-tab-button').on('click', function() {
			var $this = $(this);
			var tab = $this.data('tab');
			
			// Update active tab button
			$modal.find('.seedprod-tab-button').removeClass('active');
			$this.addClass('active');
			
			// Show corresponding tab content
			$modal.find('.seedprod-tab-content').hide();
			$modal.find('#seedprod-' + tab + '-tab').show();
		});
		
		// Export functionality
		$('#seedprod-export-theme-btn').on('click', function() {
			var $btn = $(this);
			var $spinner = $btn.find('.spinner');
			var $text = $btn.find('.button-text');
			var $status = $('.seedprod-export-status');
			
			// Show loading state
			$btn.prop('disabled', true);
			$spinner.show();
			$text.text(seedprod_admin.strings.loading || 'Processing...');
			$status.show().find('.notice').removeClass('notice-error notice-success').addClass('notice-info').find('p').text('Preparing export file...');
			
			// Create a form to trigger file download
			var $form = $('<form/>', {
				action: ajaxurl,
				method: 'POST',
				style: 'display: none;'
			});
			
			$form.append($('<input/>', {
				type: 'hidden',
				name: 'action',
				value: 'seedprod_lite_v2_export_theme_files'
			}));
			
			$form.append($('<input/>', {
				type: 'hidden',
				name: 'nonce',
				value: seedprod_admin.v2_nonce
			}));
			
			// Append form to body and submit
			$('body').append($form);
			
			// Create an iframe to handle the download
			var $iframe = $('<iframe/>', {
				name: 'download-frame',
				style: 'display: none;'
			});
			$('body').append($iframe);
			
			$form.attr('target', 'download-frame');
			$form.submit();
			
			// Reset button after a delay (since we can't detect when download completes)
			setTimeout(function() {
				$btn.prop('disabled', false);
				$spinner.hide();
				$text.text('Export All Templates');
				$status.show().find('.notice').removeClass('notice-info').addClass('notice-success').find('p').text('Export completed successfully!');
				
				// Clean up
				$form.remove();
				$iframe.remove();
				
				// Hide success message after 3 seconds
				setTimeout(function() {
					$status.fadeOut();
				}, 3000);
			}, 2000);
		});
		
		// File selection
		$('#seedprod-select-file-btn').on('click', function() {
			$('#seedprod-import-file').click();
		});
		
		// Handle file selection
		$('#seedprod-import-file').on('change', function() {
			var file = this.files[0];
			if (file) {
				$('.seedprod-file-name').text(file.name);
				$('#seedprod-import-theme-btn').prop('disabled', false);
				$('#seedprod-import-url').val(''); // Clear URL field
			}
		});
		
		// Handle URL input
		$('#seedprod-import-url').on('input', function() {
			var url = $(this).val().trim();
			if (url) {
				$('#seedprod-import-theme-btn').prop('disabled', false);
				// Clear file selection
				$('#seedprod-import-file').val('');
				$('.seedprod-file-name').text('');
			} else if (!$('#seedprod-import-file').val()) {
				$('#seedprod-import-theme-btn').prop('disabled', true);
			}
		});
		
		// Helper function to perform the import
		function performThemeImport(file, url, deleteExisting) {
			var $btn = $('#seedprod-import-theme-btn');
			var $spinner = $btn.find('.spinner');
			var $text = $btn.find('.button-text');
			var $status = $('.seedprod-import-status');

			// Show loading state
			$btn.prop('disabled', true);
			$spinner.show();
			$text.text(seedprod_admin.strings.loading || 'Importing...');
			$status.show().find('.notice').removeClass('notice-error notice-success notice-warning').addClass('notice-info').find('p').text('Importing templates...');

			if (file) {
				// File upload
				var formData = new FormData();
				formData.append('action', 'seedprod_lite_v2_import_theme_files');
				formData.append('nonce', seedprod_admin.v2_nonce);
				formData.append('seedprod_theme_files', file);
				formData.append('delete_existing', deleteExisting ? '1' : '0');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						$btn.prop('disabled', false);
						$spinner.hide();
						$text.text('Import Templates');
						
						// The import function returns just true/false, not {success: true}
						if (response === true || response.success) {
							$status.find('.notice').removeClass('notice-info').addClass('notice-success').find('p').text('Templates imported successfully!');
							
							// Reload page after 2 seconds
							setTimeout(function() {
								window.location.reload();
							}, 2000);
						} else if (response === false) {
							$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text(seedprod_admin.strings.error_occurred || 'An error occurred. Please try again.');
						} else {
							var errorMessage = response.data || 'Import failed. Please try again.';
							if (typeof response.data === 'object' && response.data.message) {
								errorMessage = response.data.message;
							}
							$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text(errorMessage);
						}
					},
					error: function(xhr, status, error) {
						$btn.prop('disabled', false);
						$spinner.hide();
						$text.text('Import Templates');
						var errorMessage = 'Import failed: ' + (error || status || 'Unknown error');
						if (xhr.responseJSON && xhr.responseJSON.data) {
							errorMessage = xhr.responseJSON.data;
						}
						$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text(errorMessage);
					}
				});
			} else {
				// URL import
				$.post(ajaxurl, {
					action: 'seedprod_lite_v2_import_theme_by_url',
					nonce: seedprod_admin.v2_nonce,
					seedprod_theme_url: url,
					delete_existing: deleteExisting ? '1' : '0'
				}, function(response) {
					$btn.prop('disabled', false);
					$spinner.hide();
					$text.text('Import Templates');
					
					// The import function returns just true/false, not {success: true}
					if (response === true || response.success) {
						$status.find('.notice').removeClass('notice-info').addClass('notice-success').find('p').text('Templates imported successfully!');
						
						// Reload page after 2 seconds
						setTimeout(function() {
							window.location.reload();
						}, 2000);
					} else if (response === false) {
						$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text('Import failed. Please check the URL and try again.');
					} else {
						$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text(response.data || 'Import failed. Please try again.');
					}
				}).fail(function() {
					$btn.prop('disabled', false);
					$spinner.hide();
					$text.text('Import Templates');
					$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text('Import failed. Please try again.');
				});
			}
		}

		// Import functionality with confirmation
		$('#seedprod-import-theme-btn').on('click', function() {
			var file = $('#seedprod-import-file')[0].files[0];
			var url = $('#seedprod-import-url').val().trim();

			if (!file && !url) {
				alert(seedprod_admin.strings.select_file_or_url);
				return;
			}

			// Check if theme templates already exist
			$.post(ajaxurl, {
				action: 'seedprod_lite_v2_check_existing_theme',
				nonce: seedprod_admin.v2_nonce
			}, function(response) {
				if (response.success && response.data.has_templates) {
					// Show confirmation dialog
					if (confirm(seedprod_admin.strings.theme_file_import_confirm)) {
						// User chose to delete existing and import
						performThemeImport(file, url, true);
					} else {
						// User cancelled - do nothing
						return;
					}
				} else {
					// No existing templates, proceed normally
					performThemeImport(file, url, false);
				}
			});
		});
		
		// Reset import form
		function resetImportForm() {
			$('#seedprod-import-file').val('');
			$('.seedprod-file-name').text('');
			$('#seedprod-import-url').val('');
			$('#seedprod-import-theme-btn').prop('disabled', true);
			$('.seedprod-import-status, .seedprod-export-status').hide();
		}
	}
	
	/**
	 * Initialize Landing Pages Import/Export Modal
	 */
	function initLandingImportExportModal() {
		var $modal = $('#seedprod-landing-import-export-modal');
		if (!$modal.length) return;
		
		// Open modal when button is clicked
		$('#seedprod-landing-import-export-btn').on('click', function(e) {
			e.preventDefault();
			$modal.fadeIn(200);
		});
		
		// Close modal
		$modal.find('.seedprod-modal-close, .seedprod-modal-cancel').on('click', function() {
			$modal.fadeOut(200);
			resetLandingImportForm();
		});
		
		// Close modal when clicking on overlay
		$modal.on('click', function(e) {
			if (e.target === this) {
				$modal.fadeOut(200);
				resetLandingImportForm();
			}
		});
		
		// Tab switching
		$modal.find('.seedprod-tab-button').on('click', function() {
			var $this = $(this);
			var tab = $this.data('tab');
			
			// Update active tab button
			$modal.find('.seedprod-tab-button').removeClass('active');
			$this.addClass('active');
			
			// Show corresponding tab content
			$modal.find('.seedprod-tab-content').hide();
			$modal.find('#seedprod-landing-' + tab + '-tab').show();
		});
		
		// Handle page selection change
		$('#seedprod-export-page-select').on('change', function() {
			var selectedValue = $(this).val();
			var $btnText = $('#seedprod-export-landing-btn .button-text');

			if (selectedValue === 'all') {
				$btnText.text(seedprod_admin.strings.export_all_landing_pages || 'Export All Landing Pages');
			} else {
				$btnText.text(seedprod_admin.strings.export_selected_page || 'Export Selected Page');
			}
		});

		// Export functionality
		$('#seedprod-export-landing-btn').off('click.seedprod-export').on('click.seedprod-export', function() {
			var $btn = $(this);

			// Prevent multiple simultaneous exports
			if ($btn.prop('disabled')) {
				return false;
			}

			var $spinner = $btn.find('.spinner');
			var $text = $btn.find('.button-text');
			var $status = $('.seedprod-export-status');
			var $select = $('#seedprod-export-page-select');

		var selectedPageId = $select.val();
		var selectedOption = $select.find('option:selected');
		var pageType = selectedOption.data('ptype') || '';

			// Show loading state
			$btn.prop('disabled', true);
			$spinner.show();
			var originalText = $text.text();
			$text.text(seedprod_admin.strings.loading || 'Processing...');
			$status.show().find('.notice').removeClass('notice-error notice-success').addClass('notice-info').find('p').text(seedprod_admin.strings.preparing_export || 'Preparing export file...');

			// Prepare AJAX data
			var ajaxData = {
				action: 'seedprod_lite_v2_export_landing_pages',
				nonce: seedprod_admin.v2_nonce
			};

			// Add page_id parameter if a specific page is selected
			if (selectedPageId !== 'all') {
				ajaxData.page_id = selectedPageId;

				// Add ptype parameter for special pages
				if (pageType) {
					ajaxData.ptype = pageType;
				}
			}

			// Make AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: ajaxData,
				success: function(response) {
					$btn.prop('disabled', false);
					$spinner.hide();
					$text.text(originalText);

					if (response.success && response.data && response.data.download_url) {
						// Show success message
						$status.show().find('.notice').removeClass('notice-info notice-error').addClass('notice-success').find('p').text(seedprod_admin.strings.export_completed || 'Export completed successfully! Downloading...');

						// Trigger download
						var link = document.createElement('a');
						link.href = response.data.download_url;
						link.download = response.data.filename || 'seedprod-export.zip';
						document.body.appendChild(link);
						link.click();
						document.body.removeChild(link);

						// Hide success message after 3 seconds
						setTimeout(function() {
							$status.fadeOut();
						}, 3000);
					} else {
						// Show error message
						var errorMsg = (response.data && response.data.message) ? response.data.message : (seedprod_admin.strings.export_failed || 'Export failed. Please try again.');
						$status.show().find('.notice').removeClass('notice-info notice-success').addClass('notice-error').find('p').text(errorMsg);
					}
				},
				error: function(xhr, status, error) {
					$btn.prop('disabled', false);
					$spinner.hide();
					$text.text(originalText);

					var errorMsg = seedprod_admin.strings.export_failed || 'Export failed. Please try again.';
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						errorMsg = xhr.responseJSON.data.message;
					}
					$status.show().find('.notice').removeClass('notice-info notice-success').addClass('notice-error').find('p').text(errorMsg);
				}
			});
		});
		
		// File selection
		$('#seedprod-landing-select-file-btn').off('click.seedprod-landing').on('click.seedprod-landing', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $fileInput = $('#seedprod-landing-import-file');
			$fileInput.click();
		});

		// Handle file selection
		var fileChangeTimer;
		$('#seedprod-landing-import-file').off('change.seedprod-landing').on('change.seedprod-landing', function() {
			// Debounce the change event to prevent multiple rapid fires
			clearTimeout(fileChangeTimer);

			var $this = $(this);
			fileChangeTimer = setTimeout(function() {
				var file = $this[0].files[0];

				if (file) {
					// Validate file type
					if (!file.name.toLowerCase().endsWith('.zip')) {
						alert(seedprod_admin.strings.invalid_file_type || 'Please select a valid ZIP file.');
						$this.val(''); // Clear the invalid selection
						$('.seedprod-file-name').text('');
						$('#seedprod-import-landing-btn').prop('disabled', true);
						return;
					}

					$('.seedprod-file-name').text(file.name);
					$('#seedprod-import-landing-btn').prop('disabled', false);
					$('#seedprod-landing-import-url').val(''); // Clear URL field

					// Hide any previous error messages
					$('.seedprod-import-status').hide();
				} else {
					// File selection was cancelled or cleared
					$('.seedprod-file-name').text('');
					if (!$('#seedprod-landing-import-url').val().trim()) {
						$('#seedprod-import-landing-btn').prop('disabled', true);
					}
				}
			}, 100); // 100ms debounce
		});
		
		// Handle URL input
		$('#seedprod-landing-import-url').on('input', function() {
			var url = $(this).val().trim();
			if (url) {
				$('#seedprod-import-landing-btn').prop('disabled', false);
				// Clear file selection
				$('#seedprod-landing-import-file').val('');
				$('.seedprod-file-name').text('');
			} else if (!$('#seedprod-landing-import-file').val()) {
				$('#seedprod-import-landing-btn').prop('disabled', true);
			}
		});
		
		// Import functionality
		$('#seedprod-import-landing-btn').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $btn = $(this);

			// Prevent duplicate clicks
			if ($btn.hasClass('importing')) {
				return;
			}

			var $spinner = $btn.find('.spinner');
			var $text = $btn.find('.button-text');
			var $status = $('.seedprod-import-status');
			var file = $('#seedprod-landing-import-file')[0].files[0];
			var url = $('#seedprod-landing-import-url').val().trim();

			if (!file && !url) {
				alert(seedprod_admin.strings.select_file_or_url);
				return;
			}

			// Show loading state and mark as importing
			$btn.addClass('importing').prop('disabled', true);
			$spinner.show();
			$text.text(seedprod_admin.strings.loading || 'Importing...');
			$status.show().find('.notice').removeClass('notice-error notice-success notice-warning').addClass('notice-info').find('p').text(seedprod_admin.strings.processing_import || 'Processing import...');
			
			if (file) {
				// File upload
				var formData = new FormData();
				formData.append('action', 'seedprod_lite_v2_import_landing_pages');
				formData.append('nonce', seedprod_admin.v2_nonce);
				formData.append('seedprod_landing_files', file);
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						$btn.removeClass('importing').prop('disabled', false);
						$spinner.hide();
						$text.text('Import Landing Pages');
						console.log('Import response:', response);
						
						// The import function returns just true/false, not {success: true}
						if (response === true || response.success) {
							$status.find('.notice').removeClass('notice-info').addClass('notice-success').find('p').text(seedprod_admin.strings.import_success || 'Import completed successfully!');
							
							// Reload page after 2 seconds
							setTimeout(function() {
								window.location.reload();
							}, 2000);
						} else if (response === false) {
							$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text(seedprod_admin.strings.error_occurred || 'An error occurred. Please try again.');
						} else {
							var errorMessage = response.data || 'Import failed. Please try again.';
							if (typeof response.data === 'object' && response.data.message) {
								errorMessage = response.data.message;
							}
							$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text(errorMessage);
						}
					},
					error: function(xhr, status, error) {
						$btn.removeClass('importing').prop('disabled', false);
						$spinner.hide();
						$text.text('Import Landing Pages');
						console.log('Import error:', error);
						var errorMessage = 'Import failed: ' + (error || status || 'Unknown error');
						if (xhr.responseJSON && xhr.responseJSON.data) {
							errorMessage = xhr.responseJSON.data;
						}
						$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text(errorMessage);
					}
				});
			} else {
				// URL import is not supported for landing pages (UI should be hidden)
				// This shouldn't be reached as the URL import section is hidden
				$btn.removeClass('importing').prop('disabled', false);
				$spinner.hide();
				$text.text('Import Landing Pages');
				$status.find('.notice').removeClass('notice-info').addClass('notice-error').find('p').text('Please select a file to import.');
			}
		});
		
		// Reset import form
		function resetLandingImportForm() {
			$('#seedprod-landing-import-file').val('');
			$('.seedprod-file-name').text('');
			$('#seedprod-landing-import-url').val('');
			$('#seedprod-import-landing-btn').prop('disabled', true);
			$('.seedprod-import-status, .seedprod-export-status').hide();
		}
	}
	
	// Initialize Website Builder features
	if ($('.seedprod-website-builder-page').length) {
		initWebsiteBuilderThemeToggle();
		initThemeTemplatesTable();
		initAddNewTemplateModal();
		initEditConditionsModal();
		initImportExportModal();
	} else {
		// Also try to initialize on document ready as a fallback
		$(document).ready(function() {
			if ($('.seedprod-website-builder-page').length) {
				initWebsiteBuilderThemeToggle();
				initThemeTemplatesTable();
				initAddNewTemplateModal();
				initEditConditionsModal();
				initImportExportModal();
			}
		});
	}
	
	// Initialize Landing Pages features
	if ($('.seedprod-landing-pages-page').length) {
		initLandingImportExportModal();
	} else {
		// Also try to initialize on document ready as a fallback
		$(document).ready(function() {
			if ($('.seedprod-landing-pages-page').length) {
				initLandingImportExportModal();
			}
		});
	}

	/**
	 * Review Notice Handlers (V2 Admin)
	 * Handles the 3-step review request flow
	 */
	$(document).on('click', '.seedprod-v2-review-notice .seedprod-dismiss-review-notice, .seedprod-v2-review-notice button', function(event) {
		if (!$(this).hasClass('seedprod-review-out')) {
			event.preventDefault();
		}

		// Check if this is a permanent dismissal (already reviewed)
		var isPermanent = $(this).hasClass('seedprod-dismiss-review-notice-permanent');

		$.post(ajaxurl, {
			action: 'seedprod_v2_review_dismiss',
			permanent: isPermanent ? 'true' : 'false'
		});

		$('.seedprod-v2-review-notice').fadeOut();
	});

	// Handle step switching in review notice
	$(document).on('click', '.seedprod-v2-review-notice .seedprod-review-switch-step', function(e) {
		e.preventDefault();
		var targetStep = $(this).attr('data-step');

		if (targetStep) {
			var $notice = $(this).closest('.seedprod-v2-review-notice');
			var $targetStepDiv = $notice.find('.seedprod-review-step-' + targetStep);

			if ($targetStepDiv.length > 0) {
				$notice.find('.seedprod-review-step:visible').fadeOut(function() {
					$targetStepDiv.fadeIn();
				});
			}
		}
	});

	// Handle "Ask me later" link
	$(document).on('click', '.seedprod-v2-review-notice .seedprod-review-dismiss-link', function(e) {
		e.preventDefault();

		$.post(ajaxurl, {
			action: 'seedprod_v2_review_dismiss',
			permanent: 'false'
		});

		$('.seedprod-v2-review-notice').fadeOut();
	});

})( jQuery );