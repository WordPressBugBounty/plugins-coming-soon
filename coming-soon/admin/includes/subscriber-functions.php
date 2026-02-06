<?php
/**
 * V2 Subscriber Functions for SeedProd Lite
 *
 * @package SeedProd
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get subscribers datatable for V2 admin
 *
 * @return void
 */
function seedprod_lite_v2_get_subscribers_datatable() {
	if ( check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		if ( ! current_user_can( apply_filters( 'seedprod_subscriber_capability', 'list_users' ) ) ) {
			wp_send_json_error( __( 'You do not have permission to view subscribers.', 'coming-soon' ) );
		}

		global $wpdb;
		$tablename = $wpdb->prefix . 'csp3_subscribers';

		// Get parameters.
		$page        = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page    = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 25;
		$search      = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$page_filter = isset( $_POST['page_filter'] ) ? sanitize_text_field( wp_unslash( $_POST['page_filter'] ) ) : 'all';
		$sort_by     = isset( $_POST['sort_by'] ) ? sanitize_text_field( wp_unslash( $_POST['sort_by'] ) ) : 'created';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated against allowed values (ASC/DESC) immediately after.
		$sort_order = isset( $_POST['sort_order'] ) && in_array( strtoupper( wp_unslash( $_POST['sort_order'] ) ), array( 'ASC', 'DESC' ), true ) ? strtoupper( wp_unslash( $_POST['sort_order'] ) ) : 'DESC';

		// Build query.
		$where_clauses = array( '1=1' );

		if ( ! empty( $search ) ) {
			$where_clauses[] = $wpdb->prepare( '(email LIKE %s OR fname LIKE %s OR lname LIKE %s)', '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
		}

		if ( 'all' !== $page_filter && ! empty( $page_filter ) ) {
			$where_clauses[] = $wpdb->prepare( 'page_uuid = %s', $page_filter );
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// Get total count.
		$total_sql = "SELECT COUNT(*) FROM $tablename WHERE $where_sql";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- $where_sql built with wpdb->prepare() calls above, custom table query.
		$total_items = $wpdb->get_var( $total_sql );

		// Get subscribers.
		$offset = ( $page - 1 ) * $per_page;

		// Validate sort column.
		$allowed_sort_columns = array( 'email', 'fname', 'lname', 'created', 'page_id' );
		if ( ! in_array( $sort_by, $allowed_sort_columns, true ) ) {
			$sort_by = 'created';
		}

		$sql = "SELECT *, UNIX_TIMESTAMP(created) as created_timestamp
				FROM $tablename
				WHERE $where_sql
				ORDER BY $sort_by $sort_order
				LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $where_sql built with wpdb->prepare() calls above, $sort_by validated against allowed columns.
		$safe_sql = $wpdb->prepare( $sql, $per_page, $offset );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query prepared above, custom table, transient data.
		$results = $wpdb->get_results( $safe_sql );

		// Format results.
		$subscribers = array();
		foreach ( $results as $row ) {
			$subscribers[] = array(
				'id'        => $row->id,
				'email'     => $row->email,
				'name'      => trim( $row->fname . ' ' . $row->lname ),
				'fname'     => $row->fname,
				'lname'     => $row->lname,
				'created'   => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $row->created_timestamp ), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				'page_id'   => $row->page_id,
				'page_uuid' => $row->page_uuid,
			);
		}

		// Get pages for filter dropdown.
		$pages = seedprod_lite_v2_get_subscriber_pages();

		// Get chart data for last 7 days.
		$chart_data = seedprod_lite_v2_get_subscriber_chart_data( $page_filter );

		wp_send_json_success(
			array(
				'subscribers'  => $subscribers,
				'total'        => $total_items,
				'pages'        => ceil( $total_items / $per_page ),
				'current_page' => $page,
				'per_page'     => $per_page,
				'chart_data'   => $chart_data,
				'page_list'    => $pages,
			)
		);
	}
	wp_send_json_error();
}

/**
 * Get list of pages with subscribers
 *
 * @return array
 */
function seedprod_lite_v2_get_subscriber_pages() {
	global $wpdb;

	$pages = array();

	// Get Coming Soon page.
	$csp_id = get_option( 'seedprod_coming_soon_page_id' );
	if ( ! empty( $csp_id ) ) {
		$post = get_post( $csp_id );
		if ( $post ) {
			$uuid = get_post_meta( $csp_id, '_seedprod_page_uuid', true );
			if ( $uuid ) {
				$pages[] = array(
					'id'   => $csp_id,
					'uuid' => $uuid,
					'name' => __( 'Coming Soon Page', 'coming-soon' ),
					'type' => 'csp',
				);
			}
		}
	}

	// Get Maintenance Mode page.
	$mmp_id = get_option( 'seedprod_maintenance_mode_page_id' );
	if ( ! empty( $mmp_id ) ) {
		$post = get_post( $mmp_id );
		if ( $post ) {
			$uuid = get_post_meta( $mmp_id, '_seedprod_page_uuid', true );
			if ( $uuid ) {
				$pages[] = array(
					'id'   => $mmp_id,
					'uuid' => $uuid,
					'name' => __( 'Maintenance Mode Page', 'coming-soon' ),
					'type' => 'mmp',
				);
			}
		}
	}

	// Get Login page.
	$loginp_id = get_option( 'seedprod_login_page_id' );
	if ( ! empty( $loginp_id ) ) {
		$post = get_post( $loginp_id );
		if ( $post ) {
			$uuid = get_post_meta( $loginp_id, '_seedprod_page_uuid', true );
			if ( $uuid ) {
				$pages[] = array(
					'id'   => $loginp_id,
					'uuid' => $uuid,
					'name' => __( 'Login Page', 'coming-soon' ),
					'type' => 'login',
				);
			}
		}
	}

	// Get 404 page.
	$p404_id = get_option( 'seedprod_404_page_id' );
	if ( ! empty( $p404_id ) ) {
		$post = get_post( $p404_id );
		if ( $post ) {
			$uuid = get_post_meta( $p404_id, '_seedprod_page_uuid', true );
			if ( $uuid ) {
				$pages[] = array(
					'id'   => $p404_id,
					'uuid' => $uuid,
					'name' => __( '404 Page', 'coming-soon' ),
					'type' => '404',
				);
			}
		}
	}

	// Get landing pages.
	$args = array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'   => '_seedprod_page',
				'value' => true,
			),
		),
	);

	$query = new WP_Query( $args );
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$id   = get_the_ID();
			$uuid = get_post_meta( $id, '_seedprod_page_uuid', true );
			if ( $uuid ) {
				$pages[] = array(
					'id'   => $id,
					'uuid' => $uuid,
					'name' => get_the_title(),
					'type' => 'landing',
				);
			}
		}
		wp_reset_postdata();
	}

	return $pages;
}

/**
 * Get subscriber chart data
 *
 * @param string $page_filter Page UUID filter.
 * @return array
 */
function seedprod_lite_v2_get_subscriber_chart_data( $page_filter = 'all' ) {
	global $wpdb;
	$tablename = $wpdb->prefix . 'csp3_subscribers';

	$where = '';
	if ( 'all' !== $page_filter && ! empty( $page_filter ) ) {
		$where = $wpdb->prepare( ' WHERE page_uuid = %s AND', $page_filter );
	} else {
		$where = ' WHERE';
	}

	// Get last 7 days of data.
	$sql = "SELECT DATE(created) as date, COUNT(*) as count
			FROM $tablename
			$where created >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
			GROUP BY DATE(created)
			ORDER BY date ASC";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built with wpdb->prepare() above, chart data not cached.
	$results = $wpdb->get_results( $sql );

	// Fill in missing days with 0.
	$chart_data = array();
	$today      = new DateTime();
	for ( $i = 6; $i >= 0; $i-- ) {
		$date = clone $today;
		$date->sub( new DateInterval( 'P' . $i . 'D' ) );
		$date_str     = $date->format( 'Y-m-d' );
		$display_date = $date->format( 'M j' );

		$count = 0;
		foreach ( $results as $row ) {
			if ( $date_str === $row->date ) {
				$count = intval( $row->count );
				break;
			}
		}

		$chart_data[] = array(
			'date'  => $display_date,
			'count' => $count,
		);
	}

	return $chart_data;
}

/**
 * Delete subscribers V2
 *
 * @return void
 */
function seedprod_lite_v2_delete_subscribers() {
	if ( check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		if ( ! current_user_can( apply_filters( 'seedprod_delete_subscriber_capability', 'list_users' ) ) ) {
			wp_send_json_error( __( 'You do not have permission to delete subscribers.', 'coming-soon' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as integers with array_map('intval') below.
		$ids = isset( $_POST['ids'] ) ? wp_unslash( $_POST['ids'] ) : array();

		if ( empty( $ids ) ) {
			wp_send_json_error( __( 'No subscribers selected.', 'coming-soon' ) );
		}

		// Ensure all IDs are integers.
		$ids = array_map( 'intval', $ids );

		global $wpdb;
		$tablename = $wpdb->prefix . 'csp3_subscribers';

		// Build placeholders.
		$placeholders = array_fill( 0, count( $ids ), '%d' );
		$format       = implode( ', ', $placeholders );

		// Delete subscribers.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- $format is placeholders string, custom table delete operation.
		$sql    = $wpdb->prepare( "DELETE FROM $tablename WHERE id IN ($format)", $ids );
		$result = $wpdb->query( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false !== $result ) {
			wp_send_json_success(
				array(
					'deleted' => $result,
					/* translators: %d: number of subscribers deleted */
					'message' => sprintf( _n( '%d subscriber deleted.', '%d subscribers deleted.', $result, 'coming-soon' ), $result ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to delete subscribers.', 'coming-soon' ) );
		}
	}
	wp_send_json_error();
}

/**
 * Export subscribers to CSV V2
 *
 * @return void
 */
function seedprod_lite_v2_export_subscribers() {
	if ( check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		if ( ! current_user_can( 'export' ) ) {
			wp_send_json_error( __( 'You do not have permission to export subscribers.', 'coming-soon' ) );
		}

		$page_filter = isset( $_POST['page_filter'] ) ? sanitize_text_field( wp_unslash( $_POST['page_filter'] ) ) : 'all';

		global $wpdb;
		$tablename = $wpdb->prefix . 'csp3_subscribers';

		// Build query.
		$where_sql = '1=1';
		if ( 'all' !== $page_filter && ! empty( $page_filter ) ) {
			$where_sql = $wpdb->prepare( 'page_uuid = %s', $page_filter );
		}

		// Get subscribers.
		$sql = "SELECT email, fname, lname, created, page_id, page_uuid FROM $tablename WHERE $where_sql ORDER BY created DESC";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- $where_sql built with wpdb->prepare() above, custom table export.
		$results = $wpdb->get_results( $sql );

		// Generate CSV content.
		$csv_data   = array();
		$csv_data[] = array( 'Email', 'First Name', 'Last Name', 'Date', 'Page ID' );

		foreach ( $results as $row ) {
			$csv_data[] = array(
				$row->email,
				$row->fname,
				$row->lname,
				$row->created,
				$row->page_id,
			);
		}

		// Convert to CSV string.
		$output = '';
		foreach ( $csv_data as $row ) {
			$output .= '"' . implode( '","', array_map( 'esc_attr', $row ) ) . '"' . "\n";
		}

		$filename = 'subscribers-' . gmdate( 'Y-m-d-His' ) . '.csv';

		wp_send_json_success(
			array(
				'csv'      => $output,
				'filename' => $filename,
				/* translators: %d: number of subscribers exported */
				'message'  => sprintf( _n( '%d subscriber exported.', '%d subscribers exported.', count( $results ), 'coming-soon' ), count( $results ) ),
			)
		);
	}
	wp_send_json_error();
}

/**
 * Get subscriber stats for dashboard
 *
 * @return array
 */
function seedprod_lite_v2_get_subscriber_stats() {
	global $wpdb;
	$tablename = $wpdb->prefix . 'csp3_subscribers';

	// Get total subscribers.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query, stats not cached.
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $tablename" );

	// Get subscribers from last 7 days.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query, stats not cached.
	$recent = $wpdb->get_var( "SELECT COUNT(*) FROM $tablename WHERE created >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)" );

	// Get subscribers from last 30 days.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query, stats not cached.
	$monthly = $wpdb->get_var( "SELECT COUNT(*) FROM $tablename WHERE created >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)" );

	return array(
		'total'   => intval( $total ),
		'recent'  => intval( $recent ),
		'monthly' => intval( $monthly ),
	);
}
