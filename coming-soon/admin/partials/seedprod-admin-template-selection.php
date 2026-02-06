<?php
/**
 * Template Selection Page
 *
 * Native WordPress implementation of the template selector
 *
 * @package SeedProd_Lite
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check if this is the Lite view.
$is_lite_view = seedprod_lite_v2_is_lite_view();

// Check if user has subscribed to free templates (Lite only).
$free_templates_subscribed = false;
if ( $is_lite_view ) {
	$free_templates_subscribed = get_option( 'seedprod_free_templates_subscribed', false );
}

// Get current user email for subscription.
$current_user_obj   = wp_get_current_user();
$current_user_email = $current_user_obj->user_email;

// Check if we have an existing page ID (edge case: page without template).
$page_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

// Get page name and slug from URL parameters or from existing page.
$page_name = isset( $_GET['name'] ) ? sanitize_text_field( wp_unslash( $_GET['name'] ) ) : '';
$page_slug = isset( $_GET['slug'] ) ? sanitize_text_field( wp_unslash( $_GET['slug'] ) ) : '';

// If we have a page ID but no name/slug, fetch from the database.
if ( $page_id && ( empty( $page_name ) || empty( $page_slug ) ) ) {
	$existing_page = get_post( $page_id );
	if ( $existing_page ) {
		$page_name = empty( $page_name ) ? $existing_page->post_title : $page_name;
		$page_slug = empty( $page_slug ) ? $existing_page->post_name : $page_slug;

		// Also get the page type from meta if not provided.
		if ( empty( $page_type ) ) {
			$page_type_meta = get_post_meta( $page_id, '_seedprod_page_template_type', true );
			if ( $page_type_meta ) {
				$page_type = $page_type_meta;
			}
		}
	}
}

// Get free templates URL with UTM tracking.
$free_templates_url = seedprod_lite_get_external_link(
	'https://www.seedprod.com/free-templates',
	'template-selection-subscribe',
	$is_lite_view ? 'liteplugin' : 'proplugin'
);

// Get active tab.
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'all-templates';

// Get page type for pre-filtering templates.
$page_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

// Map page types to filter values.
$type_to_filter_map = array(
	'cs'     => 'coming-soon',
	'mm'     => 'maintenance',
	'loginp' => 'login',
	'p404'   => '404',
);

// Determine default active filter based on page type.
$default_filter = 'all';
if ( ! empty( $page_type ) && isset( $type_to_filter_map[ $page_type ] ) ) {
	$default_filter = $type_to_filter_map[ $page_type ];
}
?>

<div class="seedprod-dashboard-page seedprod-template-selection-page">
	<?php
	// Include header with page title.
	$page_title = __( 'Choose a New Page Template', 'coming-soon' );
	require_once plugin_dir_path( __FILE__ ) . 'seedprod-admin-header.php';
	?>
	
	<div class="seedprod-dashboard-container">
		<?php if ( $is_lite_view && ! $free_templates_subscribed ) : ?>
		<!-- Free Templates Subscription Banner (Lite Only) -->
		<div class="seedprod-free-templates-banner">
			<div class="seedprod-banner-content">
				<strong><?php esc_html_e( 'Get 10 FREE Templates - Instant Access, No Credit Card Required', 'coming-soon' ); ?></strong>
				<div class="seedprod-subscribe-form">
					<input type="email" 
							id="seedprod-subscribe-email" 
							value="<?php echo esc_attr( $current_user_email ); ?>" 
							placeholder="<?php esc_attr_e( 'Enter your email', 'coming-soon' ); ?>" 
							class="seedprod-subscribe-input" />
					<button id="seedprod-subscribe-button" class="button button-primary">
						<?php esc_html_e( 'Subscribe', 'coming-soon' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<!-- WordPress Native Tabs -->
		<nav class="nav-tab-wrapper">
			<a href="?page=seedprod_lite_template_selection&tab=all-templates<?php echo $page_name ? '&name=' . rawurlencode( $page_name ) . '&slug=' . rawurlencode( $page_slug ) : ''; ?>" 
				class="nav-tab <?php echo 'all-templates' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'All Templates', 'coming-soon' ); ?>
			</a>
			<a href="?page=seedprod_lite_template_selection&tab=favorite-templates<?php echo $page_name ? '&name=' . rawurlencode( $page_name ) . '&slug=' . rawurlencode( $page_slug ) : ''; ?>" 
				class="nav-tab <?php echo 'favorite-templates' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Favorite Templates', 'coming-soon' ); ?>
			</a>
			<a href="?page=seedprod_lite_template_selection&tab=saved-templates<?php echo $page_name ? '&name=' . rawurlencode( $page_name ) . '&slug=' . rawurlencode( $page_slug ) : ''; ?>" 
				class="nav-tab <?php echo 'saved-templates' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Saved Templates', 'coming-soon' ); ?>
			</a>
		</nav>
		
		<div class="seedprod-template-content">
			<?php if ( 'all-templates' === $active_tab ) : ?>
				<!-- All Templates Tab -->
				<div class="seedprod-template-filters">
					<div class="seedprod-template-search">
						<input type="text" 
								id="seedprod-template-search" 
								placeholder="<?php esc_attr_e( 'Search templates...', 'coming-soon' ); ?>" 
								class="seedprod-search-input" />
						<span class="dashicons dashicons-search"></span>
					</div>
					
					<div class="seedprod-filters-section">
						<span class="seedprod-filters-label"><?php esc_html_e( 'Filters:', 'coming-soon' ); ?></span>
						<div class="seedprod-filter-pills">
						<button class="seedprod-filter-pill <?php echo 'all' === $default_filter ? 'active' : ''; ?>" data-filter="all">
							<?php esc_html_e( 'All', 'coming-soon' ); ?>
						</button>
						<button class="seedprod-filter-pill <?php echo 'coming-soon' === $default_filter ? 'active' : ''; ?>" data-filter="coming-soon">
							<?php esc_html_e( 'Coming Soon', 'coming-soon' ); ?>
						</button>
						<button class="seedprod-filter-pill <?php echo 'maintenance' === $default_filter ? 'active' : ''; ?>" data-filter="maintenance">
							<?php esc_html_e( 'Maintenance Mode', 'coming-soon' ); ?>
						</button>
						<button class="seedprod-filter-pill <?php echo '404' === $default_filter ? 'active' : ''; ?>" data-filter="404">
							<?php esc_html_e( '404 Page', 'coming-soon' ); ?>
						</button>
						<button class="seedprod-filter-pill <?php echo 'sales' === $default_filter ? 'active' : ''; ?>" data-filter="sales">
							<?php esc_html_e( 'Sales', 'coming-soon' ); ?>
						</button>
						<button class="seedprod-filter-pill <?php echo 'webinar' === $default_filter ? 'active' : ''; ?>" data-filter="webinar">
							<?php esc_html_e( 'Webinar', 'coming-soon' ); ?>
						</button>
						<button class="seedprod-filter-pill <?php echo 'lead-squeeze' === $default_filter ? 'active' : ''; ?>" data-filter="lead-squeeze">
							<?php esc_html_e( 'Lead Squeeze', 'coming-soon' ); ?>
						</button>
						<button class="seedprod-filter-pill <?php echo 'thank-you' === $default_filter ? 'active' : ''; ?>" data-filter="thank-you">
							<?php esc_html_e( 'Thank You', 'coming-soon' ); ?>
						</button>
						<button class="seedprod-filter-pill <?php echo 'login' === $default_filter ? 'active' : ''; ?>" data-filter="login">
							<?php esc_html_e( 'Login', 'coming-soon' ); ?>
						</button>
						</div>
					</div>
				</div>
				
				<div class="seedprod-templates-grid" id="all-templates-grid">
					<!-- Templates will be loaded here via AJAX -->
					<div class="seedprod-templates-loading">
						<span class="spinner is-active"></span>
						<p><?php esc_html_e( 'Loading templates...', 'coming-soon' ); ?></p>
					</div>
				</div>
				
			<?php elseif ( 'favorite-templates' === $active_tab ) : ?>
				<!-- Favorite Templates Tab -->
				<div class="seedprod-templates-grid" id="favorite-templates-grid">
					<div class="seedprod-templates-loading">
						<span class="spinner is-active"></span>
						<p><?php esc_html_e( 'Loading favorite templates...', 'coming-soon' ); ?></p>
					</div>
				</div>
				
			<?php elseif ( 'saved-templates' === $active_tab ) : ?>
				<!-- Saved Templates Tab -->
				<div class="seedprod-templates-grid" id="saved-templates-grid">
					<div class="seedprod-templates-loading">
						<span class="spinner is-active"></span>
						<p><?php esc_html_e( 'Loading saved templates...', 'coming-soon' ); ?></p>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<script type="text/javascript">
	// Pass default filter to JavaScript
	window.seedprodDefaultFilter = '<?php echo esc_js( $default_filter ); ?>';
	window.seedprodPageType = '<?php echo esc_js( $page_type ); ?>';
</script>

<!-- Template Preview Modal -->
<div id="seedprod-template-preview-modal" style="display: none;">
	<div class="seedprod-modal-overlay"></div>
	<div class="seedprod-modal-content">
		<div class="seedprod-modal-header">
			<div class="seedprod-modal-header-left">
				<h2 id="seedprod-preview-title"><?php esc_html_e( 'Template Preview', 'coming-soon' ); ?></h2>
			</div>
			<div class="seedprod-modal-header-center">
				<!-- Device switcher placeholder - Will be implemented in Phase 2 -->
				<div class="seedprod-device-switcher" style="display: none;">
					<button class="seedprod-device-btn seedprod-device-desktop active" data-device="desktop" title="<?php esc_attr_e( 'Desktop', 'coming-soon' ); ?>">
						<span class="dashicons dashicons-desktop"></span>
					</button>
					<button class="seedprod-device-btn seedprod-device-tablet" data-device="tablet" title="<?php esc_attr_e( 'Tablet', 'coming-soon' ); ?>">
						<span class="dashicons dashicons-tablet"></span>
					</button>
					<button class="seedprod-device-btn seedprod-device-mobile" data-device="mobile" title="<?php esc_attr_e( 'Mobile', 'coming-soon' ); ?>">
						<span class="dashicons dashicons-smartphone"></span>
					</button>
				</div>
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

<script>
// Store page creation data
var seedprodTemplateData = {
	pageId: <?php echo wp_json_encode( $page_id ); ?>,
	pageName: <?php echo wp_json_encode( $page_name ); ?>,
	pageSlug: <?php echo wp_json_encode( $page_slug ); ?>,
	pageType: <?php echo wp_json_encode( $page_type ); ?>,
	activeTab: <?php echo wp_json_encode( $active_tab ); ?>,
	ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
	nonce: <?php echo wp_json_encode( wp_create_nonce( 'seedprod_v2_nonce' ) ); ?>,
	isLiteView: <?php echo wp_json_encode( $is_lite_view ); ?>,
	freeTemplatesSubscribed: <?php echo wp_json_encode( $free_templates_subscribed ); ?>
};

// Add subscription handler for Lite users
jQuery(document).ready(function($) {
	$('#seedprod-subscribe-button').on('click', function(e) {
		e.preventDefault();
		
		var email = $('#seedprod-subscribe-email').val();
		var button = $(this);
		
		if (!email) {
			alert('Please enter your email address');
			return;
		}
		
		// Disable button and show loading
		button.prop('disabled', true).text('Subscribing...');
		
		$.ajax({
			url: seedprodTemplateData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'seedprod_lite_v2_subscribe_free_templates',
				email: email,
				_ajax_nonce: seedprodTemplateData.nonce
			},
			success: function(response) {
				if (response.success) {
					// Hide the banner
					$('.seedprod-free-templates-banner').slideUp();
					
					// Update the subscribed status
					seedprodTemplateData.freeTemplatesSubscribed = true;
					
					// Show success message
					alert(response.data.message);

					// Removed: window.open to free templates page

					// Reload templates
					if (typeof loadTemplates === 'function') {
						loadTemplates();
					}
				} else {
					alert(response.data || 'Subscription failed. Please try again.');
					button.prop('disabled', false).text('Subscribe');
				}
			},
			error: function() {
				alert('An error occurred. Please try again.');
				button.prop('disabled', false).text('Subscribe');
			}
		});
	});
});
</script>
