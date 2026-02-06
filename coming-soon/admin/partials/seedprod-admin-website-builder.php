<?php
/**
 * Website Builder Management Page
 *
 * @package    SeedProd_Lite
 * @subpackage SeedProd_Lite/admin/partials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load admin functions if not already loaded.
if ( ! function_exists( 'seedprod_lite_v2_is_lite_view' ) ) {
	require_once plugin_dir_path( __DIR__ ) . 'admin-functions.php';
}

// Check if we're in Lite view.
$is_lite_view = seedprod_lite_v2_is_lite_view();

// If Lite view, show product education page instead.
if ( $is_lite_view ) {
	// Include the admin class if not already loaded.
	if ( ! class_exists( 'SeedProd_Lite_Admin' ) ) {
		require_once plugin_dir_path( __DIR__ ) . 'class-seedprod-admin.php';
	}
	$admin = new SeedProd_Lite_Admin( 'coming-soon', SEEDPROD_VERSION );
	$admin->render_website_builder_education();
	return;
}

// Check if themebuilder feature is available in the license.
if ( ! function_exists( 'seedprod_lite_cu' ) || ! seedprod_lite_cu( 'themebuilder' ) ) {
	// Themebuilder not available - show product education page (same as lite version).
	// Include the admin class if not already loaded.
	if ( ! class_exists( 'SeedProd_Lite_Admin' ) ) {
		require_once plugin_dir_path( __DIR__ ) . 'class-seedprod-admin.php';
	}
	$admin = new SeedProd_Lite_Admin( 'coming-soon', SEEDPROD_VERSION );
	$admin->render_website_builder_education();
	return;
}

// Get current settings (stored as JSON string).
$settings_json = get_option( 'seedprod_settings' );
if ( ! empty( $settings_json ) ) {
	$settings = json_decode( $settings_json, true );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
} else {
	$settings = array();
}

// Check if theme is enabled (checks both old and new format).
$theme_enabled = seedprod_lite_v2_is_theme_enabled();

// Ensure theme-templates.php is loaded (contains required functions).
if ( ! function_exists( 'seedprod_lite_conditions_map' ) ) {
	require_once SEEDPROD_PLUGIN_PATH . 'app/theme-templates.php';
}

// Get actual theme template counts.
$template_counts = seedprod_lite_v2_get_theme_template_counts();
$header_count    = $template_counts['headers'];
$footer_count    = $template_counts['footers'];
$template_count  = $template_counts['pages'];

// Load the Theme Templates table class.
require_once plugin_dir_path( __DIR__ ) . 'includes/class-seedprod-theme-templates-table.php';

// Create an instance of our table class.
$templates_table = new SeedProd_Theme_Templates_Table();

// Prepare table items.
$templates_table->prepare_items();
?>

<div class="seedprod-dashboard-page seedprod-website-builder-page <?php echo $is_lite_view ? 'seedprod-lite' : ''; ?>">
	<?php
	// Include header with page title.
	$page_title = __( 'Website Builder', 'coming-soon' );
	require_once plugin_dir_path( __FILE__ ) . 'seedprod-admin-header.php';
	?>
	
	<div class="seedprod-dashboard-container">
		<!-- Theme Header Section -->
		<h2 class="seedprod-section-title"><?php esc_html_e( 'SeedProd Theme', 'coming-soon' ); ?></h2>
		<p class="seedprod-section-description"><?php esc_html_e( 'Replace your WordPress theme with a custom SeedProd theme built with our drag & drop builder.', 'coming-soon' ); ?></p>
		
		<!-- Theme Control Card -->
		<div class="postbox seedprod-card seedprod-theme-control-card">
			<div class="inside">
				<!-- Toggle Section -->
				<div class="seedprod-theme-toggle-section">
					<div class="seedprod-theme-toggle-header">
						<h3><?php esc_html_e( 'Enable SeedProd Theme', 'coming-soon' ); ?></h3>
						<div class="seedprod-theme-toggle-control">
							<label class="seedprod-switch">
								<input type="checkbox" id="seedprod-theme-toggle" <?php checked( $theme_enabled ); ?>>
								<span class="seedprod-slider"></span>
							</label>
							<span class="seedprod-toggle-label">
								<?php if ( $theme_enabled ) : ?>
									<span class="active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
								<?php else : ?>
									<span class="inactive"><?php esc_html_e( 'INACTIVE', 'coming-soon' ); ?></span>
								<?php endif; ?>
							</span>
						</div>
					</div>
					<p><?php esc_html_e( 'Activate this to replace your current WordPress theme with your custom SeedProd designs.', 'coming-soon' ); ?></p>
					<?php if ( $theme_enabled && ( $header_count > 0 || $footer_count > 0 || $template_count > 0 ) ) : ?>
					<div class="seedprod-theme-stats">
						<span class="seedprod-stat"><?php printf( /* translators: %d: Number of headers */ esc_html__( '%d Headers', 'coming-soon' ), esc_html( $header_count ) ); ?></span>
						<span class="seedprod-stat-separator">•</span>
						<span class="seedprod-stat"><?php printf( /* translators: %d: Number of footers */ esc_html__( '%d Footers', 'coming-soon' ), esc_html( $footer_count ) ); ?></span>
						<span class="seedprod-stat-separator">•</span>
						<span class="seedprod-stat"><?php printf( /* translators: %d: Number of page templates */ esc_html__( '%d Page Templates', 'coming-soon' ), esc_html( $template_count ) ); ?></span>
					</div>
					<?php endif; ?>
				</div>
				
				<hr class="seedprod-divider" />
				
				<!-- Quick Actions Section -->
				<div class="seedprod-quick-actions-section">
					<h4><?php esc_html_e( 'Quick Actions', 'coming-soon' ); ?></h4>
					<div class="seedprod-quick-actions-buttons">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_theme_kits_selection' ) ); ?>" class="button button-primary seedprod-button-primary">
							<span class="dashicons dashicons-admin-appearance"></span>
							<?php esc_html_e( 'Browse Template Kits', 'coming-soon' ); ?>
						</a>
						<a href="#" class="button seedprod-button-secondary seedprod-add-new-template-btn">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php esc_html_e( 'Add New Template', 'coming-soon' ); ?>
						</a>
						<a href="#" class="button seedprod-button-secondary" id="seedprod-import-export-btn">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Import/Export', 'coming-soon' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Theme Templates Section -->
		<div class="seedprod-theme-templates-section">
			<div class="seedprod-section-header">
				<h2 class="seedprod-section-title"><?php esc_html_e( 'Theme Templates', 'coming-soon' ); ?></h2>
				<p class="seedprod-section-description"><?php esc_html_e( 'Create and manage custom headers, footers, pages, and other theme parts for your website.', 'coming-soon' ); ?></p>
			</div>
			
			<form id="seedprod-templates-form" method="get">
				<input type="hidden" name="page" value="seedprod_lite_website_builder" />
				
				<?php
				// Get filter counts.
				$all_count       = 0;
				$published_count = 0;
				$draft_count     = 0;
				$trash_count     = 0;

				// Query counts.
				$count_args = array(
					'post_type'      => 'seedprod',
					'post_status'    => array( 'publish', 'draft', 'future', 'trash' ),
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'   => '_seedprod_is_theme_template',
							'value' => true,
						),
					),
					'fields'         => 'ids',
				);

				$all_templates = new WP_Query( $count_args );
				$all_count     = $all_templates->found_posts;

				// Published count.
				$count_args['post_status'] = 'publish';
				$published_templates       = new WP_Query( $count_args );
				$published_count           = $published_templates->found_posts;

				// Draft count.
				$count_args['post_status'] = 'draft';
				$draft_templates           = new WP_Query( $count_args );
				$draft_count               = $draft_templates->found_posts;

				// Trash count.
				$count_args['post_status'] = 'trash';
				$trash_templates           = new WP_Query( $count_args );
				$trash_count               = $trash_templates->found_posts;

				// Calculate non-trash count.
				$active_count = $all_count - $trash_count;

				// Get current filter.
				$current_filter = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : 'all';
				?>
				
				<!-- Filter Tabs -->
				<ul class="subsubsub">
					<li class="all">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_website_builder' ) ); ?>" 
							class="<?php echo ( 'all' === $current_filter ) ? 'current' : ''; ?>">
							<?php esc_html_e( 'All', 'coming-soon' ); ?> 
							<span class="count">(<?php echo esc_html( $active_count ); ?>)</span>
						</a> |
					</li>
					<li class="published">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_website_builder&filter=published' ) ); ?>"
							class="<?php echo ( 'published' === $current_filter ) ? 'current' : ''; ?>">
							<?php esc_html_e( 'Published', 'coming-soon' ); ?> 
							<span class="count">(<?php echo esc_html( $published_count ); ?>)</span>
						</a> |
					</li>
					<li class="drafts">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_website_builder&filter=drafts' ) ); ?>"
							class="<?php echo ( 'drafts' === $current_filter ) ? 'current' : ''; ?>">
							<?php esc_html_e( 'Drafts', 'coming-soon' ); ?> 
							<span class="count">(<?php echo esc_html( $draft_count ); ?>)</span>
						</a> |
					</li>
					<li class="trash">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_website_builder&filter=trash' ) ); ?>"
							class="<?php echo ( 'trash' === $current_filter ) ? 'current' : ''; ?>">
							<?php esc_html_e( 'Trash', 'coming-soon' ); ?> 
							<span class="count">(<?php echo esc_html( $trash_count ); ?>)</span>
						</a>
					</li>
				</ul>
				
				<!-- Search Box -->
				<p class="search-box">
					<label class="screen-reader-text" for="seedprod-search-input">
						<?php esc_html_e( 'Search Theme Templates', 'coming-soon' ); ?>
					</label>
					<input type="search" id="seedprod-search-input" name="s" 
							value="<?php echo isset( $_GET['s'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : ''; ?>" />
					<input type="submit" id="search-submit" class="button" 
							value="<?php esc_attr_e( 'Search Theme Templates', 'coming-soon' ); ?>" />
				</p>
				
				<div class="clear"></div>
				
				<!-- DataTable -->
				<?php $templates_table->display(); ?>
			</form>
		</div>
	</div>
	
	<!-- Edit Conditions Modal -->
	<div id="seedprod-conditions-modal" class="seedprod-modal" style="display: none;">
		<div class="seedprod-modal-overlay"></div>
		<div class="seedprod-modal-content seedprod-modal-content-large">
			<div class="seedprod-modal-header">
				<h2><?php esc_html_e( 'Edit Conditions', 'coming-soon' ); ?></h2>
				<button type="button" class="seedprod-modal-close" aria-label="<?php esc_attr_e( 'Close', 'coming-soon' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="seedprod-modal-body">
				<!-- Template Name Field -->
				<div class="seedprod-form-group">
					<label for="seedprod-template-name">
						<?php esc_html_e( 'Template Name', 'coming-soon' ); ?>
						<span class="required">*</span>
					</label>
					<input type="text" id="seedprod-template-name" class="seedprod-form-control" placeholder="<?php esc_attr_e( 'Example: Main Header, Blog Page, Search Results', 'coming-soon' ); ?>" />
				</div>

				<!-- Template Type and Priority -->
				<div class="seedprod-form-row">
					<div class="seedprod-form-group seedprod-form-group-half">
						<label><?php esc_html_e( 'Template Type', 'coming-soon' ); ?></label>
						<div id="seedprod-template-type-display" class="seedprod-template-type-display"></div>
					</div>
					<div class="seedprod-form-group seedprod-form-group-half">
						<label for="seedprod-template-priority">
							<?php esc_html_e( 'Priority', 'coming-soon' ); ?>
						</label>
						<input type="number" id="seedprod-template-priority" class="seedprod-form-control" value="20" min="0" max="999" />
						<p class="description" style="margin-top: 5px;">
							<?php esc_html_e( 'Higher priority templates will override lower priority ones.', 'coming-soon' ); ?>
						</p>
					</div>
				</div>

				<!-- Conditions Section -->
				<div class="seedprod-form-group">
					<label><?php esc_html_e( 'Display Conditions', 'coming-soon' ); ?></label>
					<div class="seedprod-conditions-info">
						<p><?php esc_html_e( 'Choose where this template should be displayed on your site.', 'coming-soon' ); ?></p>
					</div>
					
					<div id="seedprod-conditions-list" class="seedprod-conditions-list">
						<!-- Conditions will be added dynamically -->
					</div>
					
					<button type="button" class="button seedprod-add-condition">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e( 'Add Condition', 'coming-soon' ); ?>
					</button>
				</div>
			</div>
			<div class="seedprod-modal-footer">
				<button type="button" class="button button-secondary seedprod-modal-cancel">
					<?php esc_html_e( 'Cancel', 'coming-soon' ); ?>
				</button>
				<button type="button" class="button button-primary seedprod-button-primary" id="seedprod-save-conditions-btn">
					<span class="button-text"><?php esc_html_e( 'Save Conditions', 'coming-soon' ); ?></span>
					<span class="spinner" style="display: none;"></span>
				</button>
			</div>
		</div>
	</div>
	
	<!-- Add New Template Modal -->
	<div id="seedprod-new-template-modal" class="seedprod-modal" style="display: none;">
		<div class="seedprod-modal-overlay"></div>
		<div class="seedprod-modal-content seedprod-modal-content-large">
			<div class="seedprod-modal-header">
				<h2><?php esc_html_e( 'New Theme Template', 'coming-soon' ); ?></h2>
				<button type="button" class="seedprod-modal-close" aria-label="<?php esc_attr_e( 'Close', 'coming-soon' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="seedprod-modal-body">
				<form id="seedprod-new-template-form">
					<div class="seedprod-form-group">
						<label for="template-name">
							<?php esc_html_e( 'Template Name', 'coming-soon' ); ?>
							<span class="required">*</span>
						</label>
						<input type="text" 
								id="template-name" 
								name="template_name" 
								class="seedprod-form-control" 
								placeholder="<?php esc_attr_e( 'Example: Main Header, Blog Page, Search Results', 'coming-soon' ); ?>"
								required />
					</div>
					
					<div class="seedprod-form-group">
						<label for="template-type">
							<?php esc_html_e( 'Template Type', 'coming-soon' ); ?>
							<span class="required">*</span>
						</label>
						<select id="template-type" name="template_type" class="seedprod-form-control" required>
							<option value=""><?php esc_html_e( 'Select Type', 'coming-soon' ); ?></option>
							<optgroup label="<?php esc_attr_e( 'Site Parts', 'coming-soon' ); ?>">
								<option value="header"><?php esc_html_e( 'Header', 'coming-soon' ); ?></option>
								<option value="footer"><?php esc_html_e( 'Footer', 'coming-soon' ); ?></option>
								<option value="part"><?php esc_html_e( 'Template Part', 'coming-soon' ); ?></option>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'Pages', 'coming-soon' ); ?>">
								<option value="single_page"><?php esc_html_e( 'Single Page', 'coming-soon' ); ?></option>
								<option value="single_post"><?php esc_html_e( 'Single Post', 'coming-soon' ); ?></option>
								<option value="archive"><?php esc_html_e( 'Archive', 'coming-soon' ); ?></option>
								<option value="search"><?php esc_html_e( 'Search Results', 'coming-soon' ); ?></option>
								<option value="author"><?php esc_html_e( 'Author Page', 'coming-soon' ); ?></option>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'Advanced', 'coming-soon' ); ?>">
								<option value="custom"><?php esc_html_e( 'Custom', 'coming-soon' ); ?></option>
							</optgroup>
							<?php if ( class_exists( 'WooCommerce' ) ) : ?>
							<optgroup label="<?php esc_attr_e( 'WooCommerce', 'coming-soon' ); ?>">
								<option value="single_product"><?php esc_html_e( 'Single Product', 'coming-soon' ); ?></option>
								<option value="archive_product"><?php esc_html_e( 'Product Archive', 'coming-soon' ); ?></option>
							</optgroup>
							<?php endif; ?>
						</select>
					</div>
					
					<div class="seedprod-form-group">
						<label for="template-priority">
							<?php esc_html_e( 'Priority', 'coming-soon' ); ?>
						</label>
						<input type="number" 
								id="template-priority" 
								name="template_priority" 
								class="seedprod-form-control seedprod-form-control-small" 
								value="20"
								min="0"
								max="999" />
						<p class="description" style="margin-top: 5px;">
							<?php esc_html_e( 'Higher priority templates will override lower priority ones.', 'coming-soon' ); ?>
						</p>
					</div>
					
					<div class="seedprod-form-group" id="template-conditions-section" style="display: none;">
						<label>
							<?php esc_html_e( 'Display Conditions', 'coming-soon' ); ?>
						</label>
						<div id="template-conditions-list" class="seedprod-conditions-list seedprod-conditions-list-compact">
							<!-- Conditions will be added dynamically -->
						</div>
						<button type="button" class="button seedprod-add-template-condition" style="margin-top: 10px;">
							<span class="dashicons dashicons-plus-alt" style="font-size: 16px; line-height: 28px;"></span>
							<?php esc_html_e( 'Add Condition', 'coming-soon' ); ?>
						</button>
					</div>
				</form>
			</div>
			<div class="seedprod-modal-footer">
				<button type="button" class="button button-secondary seedprod-modal-cancel">
					<?php esc_html_e( 'Cancel', 'coming-soon' ); ?>
				</button>
				<button type="button" class="button button-primary seedprod-button-primary" id="seedprod-create-template-btn">
					<span class="button-text"><?php esc_html_e( 'Create Template', 'coming-soon' ); ?></span>
					<span class="spinner" style="display: none;"></span>
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Import/Export Modal -->
<div id="seedprod-import-export-modal" class="seedprod-modal" style="display: none;">
	<div class="seedprod-modal-content">
		<div class="seedprod-modal-header">
			<h2><?php esc_html_e( 'Import/Export Templates', 'coming-soon' ); ?></h2>
			<button type="button" class="seedprod-modal-close" aria-label="<?php esc_attr_e( 'Close', 'coming-soon' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="seedprod-modal-body">
			<!-- Tab Navigation -->
			<div class="seedprod-import-export-tabs">
				<button type="button" class="seedprod-tab-button active" data-tab="export">
					<?php esc_html_e( 'Export', 'coming-soon' ); ?>
				</button>
				<button type="button" class="seedprod-tab-button" data-tab="import">
					<?php esc_html_e( 'Import', 'coming-soon' ); ?>
				</button>
			</div>
			
			<!-- Export Tab Content -->
			<div class="seedprod-tab-content" id="seedprod-export-tab" style="display: block;">
				<div class="seedprod-export-section">
					<h3><?php esc_html_e( 'Export Theme Templates', 'coming-soon' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Export all your theme templates including headers, footers, pages, and template parts. The export will include all template settings, conditions, and associated images.', 'coming-soon' ); ?></p>
					
					<div class="seedprod-export-actions">
						<button type="button" class="button button-primary seedprod-button-primary" id="seedprod-export-theme-btn">
							<span class="dashicons dashicons-download"></span>
							<span class="button-text"><?php esc_html_e( 'Export All Templates', 'coming-soon' ); ?></span>
							<span class="spinner" style="display: none;"></span>
						</button>
					</div>
					
					<div class="seedprod-export-status" style="display: none;">
						<div class="notice notice-info">
							<p><?php esc_html_e( 'Preparing export file...', 'coming-soon' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Import Tab Content -->
			<div class="seedprod-tab-content" id="seedprod-import-tab" style="display: none;">
				<div class="seedprod-import-section">
					<h3><?php esc_html_e( 'Import Theme Templates', 'coming-soon' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Import theme templates from a SeedProd export file (.zip). This will add the templates to your existing theme.', 'coming-soon' ); ?></p>
					
					<div class="seedprod-import-options">
						<div class="seedprod-import-file-section">
							<h4><?php esc_html_e( 'Upload File', 'coming-soon' ); ?></h4>
							<div class="seedprod-file-upload">
								<input type="file" id="seedprod-import-file" accept=".zip" style="display: none;">
								<button type="button" class="button" id="seedprod-select-file-btn">
									<span class="dashicons dashicons-upload"></span>
									<?php esc_html_e( 'Select File', 'coming-soon' ); ?>
								</button>
								<span class="seedprod-file-name"></span>
							</div>
						</div>
						
						<div class="seedprod-import-url-section">
							<h4><?php esc_html_e( 'Or Import from URL', 'coming-soon' ); ?></h4>
							<input type="url" id="seedprod-import-url" class="regular-text" placeholder="https://example.com/theme-export.zip">
						</div>
					</div>
					
					<div class="seedprod-import-warning">
						<div class="notice notice-warning inline">
							<p>
								<strong><?php esc_html_e( 'Important:', 'coming-soon' ); ?></strong>
								<?php esc_html_e( 'Importing will create new templates. If you have existing templates with the same names, duplicates will be created. The Global CSS template will be replaced if it exists. We recommend backing up your site before importing.', 'coming-soon' ); ?>
							</p>
						</div>
					</div>
					
					<div class="seedprod-import-actions">
						<button type="button" class="button button-primary seedprod-button-primary" id="seedprod-import-theme-btn" disabled>
							<span class="dashicons dashicons-upload"></span>
							<span class="button-text"><?php esc_html_e( 'Import Templates', 'coming-soon' ); ?></span>
							<span class="spinner" style="display: none;"></span>
						</button>
					</div>
					
					<div class="seedprod-import-status" style="display: none;">
						<div class="notice">
							<p></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="seedprod-modal-footer">
			<button type="button" class="button button-secondary seedprod-modal-cancel">
				<?php esc_html_e( 'Close', 'coming-soon' ); ?>
			</button>
		</div>
	</div>
</div>
