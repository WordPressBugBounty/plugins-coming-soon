<?php
/**
 * Theme Kits Selection Page
 *
 * Native WordPress implementation of the theme kits selector
 * Based on the landing page template selector but for complete theme kits
 *
 * @package SeedProd_Lite
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get active tab.
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'all-templates'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for tab display.
?>

<div class="seedprod-dashboard-page seedprod-theme-kits-selection-page">
	<?php
	// Include header with page title.
	$page_title = __( 'Choose a Website Theme Kit', 'coming-soon' );
	require_once plugin_dir_path( __FILE__ ) . 'seedprod-admin-header.php';
	?>

	<div class="seedprod-dashboard-container">
		<!-- WordPress Native Tabs -->
		<nav class="nav-tab-wrapper">
			<a href="?page=seedprod_lite_theme_kits_selection&tab=all-templates"
				class="nav-tab <?php echo 'all-templates' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'All Themes', 'coming-soon' ); ?>
			</a>
			<a href="?page=seedprod_lite_theme_kits_selection&tab=favorite-templates"
				class="nav-tab <?php echo 'favorite-templates' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Favorite Themes', 'coming-soon' ); ?>
			</a>
		</nav>
		
		<div class="seedprod-template-content">
			<?php if ( 'all-templates' === $active_tab ) : ?>
				<!-- All Templates Tab -->
				<div class="seedprod-template-filters">
					<div class="seedprod-template-search">
						<input type="text" 
								id="seedprod-template-search" 
								placeholder="<?php esc_attr_e( 'Search theme kits...', 'coming-soon' ); ?>" 
								class="seedprod-search-input" />
						<span class="dashicons dashicons-search"></span>
					</div>
					
					<div class="seedprod-filters-section">
						<span class="seedprod-filters-label"><?php esc_html_e( 'Filters:', 'coming-soon' ); ?></span>
						<div class="seedprod-filter-pills">
							<button class="seedprod-filter-pill active" data-filter="all">
								<?php esc_html_e( 'All', 'coming-soon' ); ?>
							</button>
							<button class="seedprod-filter-pill" data-filter="woocommerce">
								<?php esc_html_e( 'WooCommerce', 'coming-soon' ); ?>
							</button>
						</div>
						
						<span class="seedprod-sort-label"><?php esc_html_e( 'Sort:', 'coming-soon' ); ?></span>
						<select id="seedprod-theme-sort" class="seedprod-sort-select">
							<option value=""><?php esc_html_e( 'Default', 'coming-soon' ); ?></option>
							<option value="popular"><?php esc_html_e( 'Popular', 'coming-soon' ); ?></option>
							<option value="new"><?php esc_html_e( 'Newest to Oldest', 'coming-soon' ); ?></option>
							<option value="old"><?php esc_html_e( 'Oldest to Newest', 'coming-soon' ); ?></option>
						</select>
					</div>
				</div>
				
				<div class="seedprod-theme-kits-grid" id="all-themes-grid">
					<!-- Theme kits will be loaded here via AJAX -->
					<div class="seedprod-templates-loading">
						<span class="spinner is-active"></span>
						<p><?php esc_html_e( 'Loading theme kits...', 'coming-soon' ); ?></p>
					</div>
				</div>
				
				<!-- Pagination -->
				<div class="seedprod-themes-pagination" style="display: none;">
					<button class="button seedprod-pagination-btn" id="seedprod-first-page" disabled>
						<span class="dashicons dashicons-controls-skipback"></span>
						<?php esc_html_e( 'First Page', 'coming-soon' ); ?>
					</button>
					<button class="button seedprod-pagination-btn" id="seedprod-prev-page" disabled>
						<span class="dashicons dashicons-arrow-left-alt2"></span>
						<?php esc_html_e( 'Prev', 'coming-soon' ); ?>
					</button>
					<span class="seedprod-page-info">
						<?php esc_html_e( 'Page', 'coming-soon' ); ?> 
						<span id="seedprod-current-page">1</span> 
						<?php esc_html_e( 'of', 'coming-soon' ); ?> 
						<span id="seedprod-total-pages">1</span>
					</span>
					<button class="button seedprod-pagination-btn" id="seedprod-next-page">
						<?php esc_html_e( 'Next', 'coming-soon' ); ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</button>
					<button class="button seedprod-pagination-btn" id="seedprod-last-page">
						<?php esc_html_e( 'Last Page', 'coming-soon' ); ?>
						<span class="dashicons dashicons-controls-skipforward"></span>
					</button>
				</div>
				
			<?php elseif ( 'favorite-templates' === $active_tab ) : ?>
				<!-- Favorite Templates Tab -->
				<div class="seedprod-template-filters">
					<div class="seedprod-template-search">
						<input type="text" 
								id="seedprod-template-search" 
								placeholder="<?php esc_attr_e( 'Search favorite theme kits...', 'coming-soon' ); ?>" 
								class="seedprod-search-input" />
						<span class="dashicons dashicons-search"></span>
					</div>
				</div>
				
				<div class="seedprod-theme-kits-grid" id="favorite-themes-grid">
					<div class="seedprod-templates-loading">
						<span class="spinner is-active"></span>
						<p><?php esc_html_e( 'Loading favorite theme kits...', 'coming-soon' ); ?></p>
					</div>
				</div>
				
				<!-- Pagination for favorites -->
				<div class="seedprod-themes-pagination" style="display: none;">
					<button class="button seedprod-pagination-btn" id="seedprod-first-page" disabled>
						<span class="dashicons dashicons-controls-skipback"></span>
						<?php esc_html_e( 'First Page', 'coming-soon' ); ?>
					</button>
					<button class="button seedprod-pagination-btn" id="seedprod-prev-page" disabled>
						<span class="dashicons dashicons-arrow-left-alt2"></span>
						<?php esc_html_e( 'Prev', 'coming-soon' ); ?>
					</button>
					<span class="seedprod-page-info">
						<?php esc_html_e( 'Page', 'coming-soon' ); ?> 
						<span id="seedprod-current-page">1</span> 
						<?php esc_html_e( 'of', 'coming-soon' ); ?> 
						<span id="seedprod-total-pages">1</span>
					</span>
					<button class="button seedprod-pagination-btn" id="seedprod-next-page">
						<?php esc_html_e( 'Next', 'coming-soon' ); ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</button>
					<button class="button seedprod-pagination-btn" id="seedprod-last-page">
						<?php esc_html_e( 'Last Page', 'coming-soon' ); ?>
						<span class="dashicons dashicons-controls-skipforward"></span>
					</button>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Theme Kit Preview Modal -->
<div id="seedprod-template-preview-modal" style="display: none;">
	<div class="seedprod-modal-overlay"></div>
	<div class="seedprod-modal-content">
		<div class="seedprod-modal-header">
			<div class="seedprod-modal-header-left">
				<h2 id="seedprod-preview-title"><?php esc_html_e( 'Theme Kit Preview', 'coming-soon' ); ?></h2>
			</div>
			<div class="seedprod-modal-header-right">
				<button class="seedprod-modal-close">
					<span class="dashicons dashicons-no"></span>
				</button>
			</div>
		</div>
		<div class="seedprod-modal-body">
			<iframe id="seedprod-preview-iframe" src="" frameborder="0"></iframe>
		</div>
	</div>
</div>

<!-- Theme Import Confirmation Modal -->
<div id="seedprod-theme-import-modal" style="display: none;">
	<div class="seedprod-modal-overlay"></div>
	<div class="seedprod-modal-content seedprod-modal-small">
		<div class="seedprod-modal-header">
			<h2><?php esc_html_e( 'Import Theme Kit', 'coming-soon' ); ?></h2>
			<button class="seedprod-modal-close">
				<span class="dashicons dashicons-no"></span>
			</button>
		</div>
		<div class="seedprod-modal-body">
			<p class="seedprod-import-warning">
				<?php esc_html_e( 'This will import all templates from this theme kit. Any existing theme templates with the same names will be replaced.', 'coming-soon' ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Theme Kit:', 'coming-soon' ); ?></strong> 
				<span id="seedprod-import-theme-name"></span>
			</p>
			<p><?php esc_html_e( 'Would you like to continue?', 'coming-soon' ); ?></p>
		</div>
		<div class="seedprod-modal-footer">
			<button class="button seedprod-modal-cancel">
				<?php esc_html_e( 'Cancel', 'coming-soon' ); ?>
			</button>
			<button class="button button-primary" id="seedprod-confirm-import-btn">
				<span class="button-text"><?php esc_html_e( 'Import Theme Kit', 'coming-soon' ); ?></span>
				<span class="spinner" style="display: none;"></span>
			</button>
		</div>
	</div>
</div>

<script>
// Store theme kits data
var seedprodThemeKitsData = {
	activeTab: <?php echo wp_json_encode( $active_tab ); ?>,
	ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
	nonce: <?php echo wp_json_encode( wp_create_nonce( 'seedprod_v2_nonce' ) ); ?>,
	apiUrl: <?php echo wp_json_encode( SEEDPROD_API_URL ); ?>,
	currentPage: 1,
	totalPages: 1,
	currentFilter: 'all',
	currentSort: '',
	searchTerm: ''
};

// Add missing strings for JavaScript
var seedprod_admin = window.seedprod_admin || {};
seedprod_admin.strings = seedprod_admin.strings || {};
seedprod_admin.strings.loading_templates = <?php echo wp_json_encode( __( 'Loading theme kits...', 'coming-soon' ) ); ?>;
seedprod_admin.strings.no_templates_found = <?php echo wp_json_encode( __( 'No theme kits found.', 'coming-soon' ) ); ?>;
seedprod_admin.strings.error_loading_templates = <?php echo wp_json_encode( __( 'Error loading theme kits. Please try again.', 'coming-soon' ) ); ?>;
</script>
