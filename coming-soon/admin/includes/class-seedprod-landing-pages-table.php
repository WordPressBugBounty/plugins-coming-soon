<?php
/**
 * Landing Pages List Table
 *
 * @package SeedProd
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Landing Pages List Table Class
 */
class SeedProd_Landing_Pages_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Landing Page', 'coming-soon' ),
				'plural'   => __( 'Landing Pages', 'coming-soon' ),
				'ajax'     => false, // We'll implement AJAX manually for better control.
			)
		);
	}

	/**
	 * Get table columns
	 */
	public function get_columns() {
		$columns = array(
			'cb'     => '<input type="checkbox" />',
			'title'  => __( 'Name', 'coming-soon' ),
			'url'    => __( 'URL', 'coming-soon' ),
			'status' => __( 'Status', 'coming-soon' ),
			'date'   => __( 'Date', 'coming-soon' ),
		);
		return $columns;
	}

	/**
	 * Get sortable columns
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title'  => array( 'title', false ),
			'date'   => array( 'date', false ),
			'status' => array( 'status', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Get default column value
	 *
	 * @param array  $item        The current item.
	 * @param string $column_name The column name.
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'url':
				return esc_html( $item['url'] );
			case 'status':
				return esc_html( $item['status'] );
			case 'date':
				return esc_html( $item['date'] );
			default:
				return '';
		}
	}

	/**
	 * Checkbox column
	 *
	 * @param array $item The current item.
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="page_id[]" value="%s" />',
			$item['ID']
		);
	}

	/**
	 * Title column with row actions
	 *
	 * @param array $item The current item.
	 */
	protected function column_title( $item ) {
		// Build row actions.
		$actions = array();

		// Edit action.
		$edit_url        = admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $item['ID'] );
		$actions['edit'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $edit_url ),
			__( 'Edit', 'coming-soon' )
		);

		// Preview action.
		$preview_url        = get_preview_post_link( $item['ID'] );
		$actions['preview'] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $preview_url ),
			__( 'Preview', 'coming-soon' )
		);

		// Duplicate action.
		$actions['duplicate'] = sprintf(
			'<a href="#" class="seedprod-duplicate-page" data-id="%s">%s</a>',
			$item['ID'],
			__( 'Duplicate', 'coming-soon' )
		);

		// Trash action.
		if ( 'trash' !== $item['post_status'] ) {
			$actions['trash'] = sprintf(
				'<a href="#" class="seedprod-trash-page" data-id="%s">%s</a>',
				$item['ID'],
				__( 'Trash', 'coming-soon' )
			);
		} else {
			// Restore action for trashed items.
			$actions['restore'] = sprintf(
				'<a href="#" class="seedprod-restore-page" data-id="%s">%s</a>',
				$item['ID'],
				__( 'Restore', 'coming-soon' )
			);

			// Delete permanently.
			$actions['delete'] = sprintf(
				'<a href="#" class="seedprod-delete-page" data-id="%s" style="color:#a00;">%s</a>',
				$item['ID'],
				__( 'Delete Permanently', 'coming-soon' )
			);
		}

		// Return title with actions.
		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $item['title'] ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * URL column
	 *
	 * @param array $item The current item.
	 */
	protected function column_url( $item ) {
		// Use get_permalink() to respect permalink settings.
		// This will show pretty URLs when permalinks are enabled, or ?page_id= when they're not.
		$url = get_permalink( $item['ID'] );

		return sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $url ),
			esc_html( $url )
		);
	}

	/**
	 * Status column
	 *
	 * @param array $item The current item.
	 */
	protected function column_status( $item ) {
		$status        = $item['post_status'];
		$status_labels = array(
			'publish' => __( 'Published', 'coming-soon' ),
			'draft'   => __( 'Draft', 'coming-soon' ),
			'trash'   => __( 'Trash', 'coming-soon' ),
			'private' => __( 'Private', 'coming-soon' ),
		);

		$label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( $status );
		$class = 'seedprod-status-' . $status;

		return sprintf(
			'<span class="%s">%s</span>',
			esc_attr( $class ),
			esc_html( $label )
		);
	}

	/**
	 * Date column
	 *
	 * @param array $item The current item.
	 */
	protected function column_date( $item ) {
		$modified = $item['post_modified'];

		if ( '0000-00-00 00:00:00' === $modified ) {
			$h_time    = __( 'Unpublished', 'coming-soon' );
			$full_date = '';
		} else {
			$time      = get_post_modified_time( 'G', true, $item['ID'] );
			$time_diff = time() - $time;

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				/* translators: %s: Human-readable time difference */
				$h_time = sprintf( __( '%s ago', 'coming-soon' ), human_time_diff( $time ) );
			} else {
				$h_time = mysql2date( __( 'F j, Y', 'coming-soon' ), $modified );
			}
			$full_date = mysql2date( __( 'F j, Y \a\t g:i a', 'coming-soon' ), $modified );
		}

		return sprintf(
			'%s<br /><small>%s</small>',
			esc_html( __( 'Last Modified', 'coming-soon' ) ),
			$full_date ? sprintf( '<abbr title="%s">%s</abbr>', esc_attr( $full_date ), esc_html( $h_time ) ) : esc_html( $h_time )
		);
	}

	/**
	 * Get bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array(
			'trash' => __( 'Move to Trash', 'coming-soon' ),
		);

		// If viewing trash, show different actions.
		if ( isset( $_REQUEST['post_status'] ) && 'trash' === $_REQUEST['post_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$actions = array(
				'restore' => __( 'Restore', 'coming-soon' ),
				'delete'  => __( 'Delete Permanently', 'coming-soon' ),
			);
		}

		return $actions;
	}

	/**
	 * Get views (All, Published, Draft, Trash)
	 */
	protected function get_views() {
		$views   = array();
		$current = ( ! empty( $_REQUEST['post_status'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['post_status'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Get counts.
		$counts = $this->get_post_counts();

		// All link.
		$class        = ( 'all' === $current ) ? ' class="current"' : '';
		$views['all'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( remove_query_arg( 'post_status' ) ),
			$class,
			__( 'All', 'coming-soon' ),
			$counts['all']
		);

		// Published.
		if ( $counts['publish'] > 0 ) {
			$class            = ( 'publish' === $current ) ? ' class="current"' : '';
			$views['publish'] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'post_status', 'publish' ) ),
				$class,
				__( 'Published', 'coming-soon' ),
				$counts['publish']
			);
		}

		// Draft.
		if ( $counts['draft'] > 0 ) {
			$class          = ( 'draft' === $current ) ? ' class="current"' : '';
			$views['draft'] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'post_status', 'draft' ) ),
				$class,
				__( 'Drafts', 'coming-soon' ),
				$counts['draft']
			);
		}

		// Trash.
		if ( $counts['trash'] > 0 ) {
			$class          = ( 'trash' === $current ) ? ' class="current"' : '';
			$views['trash'] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'post_status', 'trash' ) ),
				$class,
				__( 'Trash', 'coming-soon' ),
				$counts['trash']
			);
		}

		return $views;
	}

	/**
	 * Get post counts by status
	 */
	private function get_post_counts() {
		global $wpdb;

		$cache_key = 'seedprod_landing_pages_counts';
		$counts    = wp_cache_get( $cache_key );

		if ( false === $counts ) {
			$counts = array(
				'all'     => 0,
				'publish' => 0,
				'draft'   => 0,
				'trash'   => 0,
			);

			// Get special page IDs to exclude (Coming Soon, Maintenance, 404, Login).
			$csp_id    = get_option( 'seedprod_coming_soon_page_id' );
			$mmp_id    = get_option( 'seedprod_maintenance_mode_page_id' );
			$p404_id   = get_option( 'seedprod_404_page_id' );
			$loginp_id = get_option( 'seedprod_login_page_id' );

			// Build exclusion list (only include non-empty IDs).
			$exclude_ids = array_filter( array( $csp_id, $mmp_id, $p404_id, $loginp_id ) );

			// Build query with dynamic exclusions.
			$exclude_clause = '';
			if ( ! empty( $exclude_ids ) ) {
				$exclude_ids_string = implode( ',', array_map( 'absint', $exclude_ids ) );
				$exclude_clause     = "AND p.ID NOT IN ($exclude_ids_string)";
			}

			// Query to get counts - All pages with _seedprod_page (landing pages only, excludes theme pages)
			$results = $wpdb->get_results(
				"SELECT p.post_status, COUNT(*) as count
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_seedprod_page')
				WHERE p.post_type = 'page'
				$exclude_clause
				GROUP BY p.post_status",
				ARRAY_A
			);

			foreach ( $results as $row ) {
				$status = $row['post_status'];
				$count  = (int) $row['count'];

				if ( isset( $counts[ $status ] ) ) {
					$counts[ $status ] = $count;
				}

				// Add to all count (except trash).
				if ( 'trash' !== $status ) {
					$counts['all'] += $count;
				}
			}

			wp_cache_set( $cache_key, $counts, '', 300 ); // Cache for 5 minutes.
		}

		return $counts;
	}

	/**
	 * Prepare items for display
	 */
	public function prepare_items() {
		// Set column headers.
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Get data.
		$per_page     = $this->get_items_per_page( 'seedprod_landing_pages_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Get special page IDs to exclude (Coming Soon, Maintenance, 404, Login).
		$csp_id    = get_option( 'seedprod_coming_soon_page_id' );
		$mmp_id    = get_option( 'seedprod_maintenance_mode_page_id' );
		$p404_id   = get_option( 'seedprod_404_page_id' );
		$loginp_id = get_option( 'seedprod_login_page_id' );

		// Build exclusion list (only include non-empty IDs).
		$exclude_ids = array_filter( array( $csp_id, $mmp_id, $p404_id, $loginp_id ) );

		// Build query args - All pages with _seedprod_page (landing pages only, excludes theme pages).
		$args = array(
			'post_type'      => 'page',
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'post_status'    => 'any',
			'post__not_in'   => $exclude_ids,
			'meta_query'     => array(
				array(
					'key'     => '_seedprod_page',
					'compare' => 'EXISTS',
				),
			),
		);

		// Filter by status.
		if ( ! empty( $_REQUEST['post_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status = sanitize_text_field( wp_unslash( $_REQUEST['post_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'all' !== $status ) {
				$args['post_status'] = $status;
			}
		} else {
			// Default: exclude trash.
			$args['post_status'] = array( 'publish', 'draft', 'private', 'pending' );
		}

		// Handle search.
		if ( ! empty( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Handle sorting.
		if ( ! empty( $_REQUEST['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$orderby = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order   = ! empty( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			switch ( $orderby ) {
				case 'title':
					$args['orderby'] = 'title';
					break;
				case 'date':
					$args['orderby'] = 'date';
					break;
				case 'status':
					$args['orderby'] = 'post_status';
					break;
				default:
					$args['orderby'] = 'date';
			}

			$args['order'] = strtoupper( $order );
		} else {
			// Default sorting.
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}

		// Get posts.
		$query = new WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = array(
				'ID'            => $post->ID,
				'title'         => get_the_title( $post->ID ),
				'post_status'   => $post->post_status,
				'post_date'     => $post->post_date,
				'post_modified' => $post->post_modified,
				'url'           => get_permalink( $post->ID ),
			);
		}

		$this->items = $items;

		// Set pagination.
		$total_items = $query->found_posts;
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Message to display when no items found
	 */
	public function no_items() {
		if ( ! empty( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			esc_html_e( 'No landing pages found for your search.', 'coming-soon' );
		} else {
			esc_html_e( 'No landing pages found.', 'coming-soon' );
		}
	}
}
