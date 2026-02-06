<?php
/**
 * Theme Templates List Table
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
 * Theme Templates List Table Class
 */
class SeedProd_Theme_Templates_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Theme Template', 'coming-soon' ),
				'plural'   => __( 'Theme Templates', 'coming-soon' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get table columns
	 */
	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'title'      => __( 'Name', 'coming-soon' ),
			'type'       => __( 'Type', 'coming-soon' ),
			'conditions' => __( 'Conditions', 'coming-soon' ),
			'published'  => __( 'Published', 'coming-soon' ),
			'priority'   => __( 'Priority', 'coming-soon' ),
			'date'       => __( 'Date', 'coming-soon' ),
		);
		return $columns;
	}

	/**
	 * Get sortable columns
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title'    => array( 'title', false ),
			'type'     => array( 'type', false ),
			'priority' => array( 'priority', false ),
			'date'     => array( 'date', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Get default column value
	 *
	 * @param array  $item        The item data.
	 * @param string $column_name The column name.
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'type':
				return $this->format_type( $item['type'] );
			case 'conditions':
				return $this->format_conditions( $item['conditions_display'] );
			case 'priority':
				return esc_html( $item['priority'] );
			case 'date':
				return esc_html( $item['date'] );
			default:
				return '';
		}
	}

	/**
	 * Format template type
	 *
	 * @param string $type The template type.
	 */
	private function format_type( $type ) {
		$type_labels = array(
			'header'          => __( 'Header', 'coming-soon' ),
			'footer'          => __( 'Footer', 'coming-soon' ),
			'part'            => __( 'Part', 'coming-soon' ),
			'page'            => __( 'Page', 'coming-soon' ),
			'single_post'     => __( 'Single Post', 'coming-soon' ),
			'single_page'     => __( 'Single Page', 'coming-soon' ),
			'archive'         => __( 'Archive', 'coming-soon' ),
			'search'          => __( 'Search', 'coming-soon' ),
			'author'          => __( 'Author', 'coming-soon' ),
			'404'             => __( '404', 'coming-soon' ),
			'single_product'  => __( 'Single Product', 'coming-soon' ),
			'archive_product' => __( 'Product Archive', 'coming-soon' ),
		);

		$label = isset( $type_labels[ $type ] ) ? $type_labels[ $type ] : ucfirst( str_replace( '_', ' ', $type ) );

		// Add icon based on type.
		$icon = '';
		switch ( $type ) {
			case 'header':
				$icon = '<span class="dashicons dashicons-arrow-up-alt" style="font-size: 16px; margin-right: 4px;"></span>';
				break;
			case 'footer':
				$icon = '<span class="dashicons dashicons-arrow-down-alt" style="font-size: 16px; margin-right: 4px;"></span>';
				break;
			case 'part':
				$icon = '<span class="dashicons dashicons-layout" style="font-size: 16px; margin-right: 4px;"></span>';
				break;
			default:
				$icon = '<span class="dashicons dashicons-admin-page" style="font-size: 16px; margin-right: 4px;"></span>';
		}

		return $icon . esc_html( $label );
	}

	/**
	 * Format conditions display
	 *
	 * @param string $conditions The conditions HTML.
	 */
	private function format_conditions( $conditions ) {
		if ( empty( $conditions ) ) {
			return '<span style="color: #999;">' . __( 'No conditions set', 'coming-soon' ) . '</span>';
		}
		return wp_kses_post( $conditions );
	}

	/**
	 * Checkbox column
	 *
	 * @param array $item The item data.
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="template_id[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Title column with row actions
	 *
	 * @param array $item The item data.
	 */
	protected function column_title( $item ) {
		// Build row actions.
		$actions = array();

		// Edit Design action.
		// Check if this is a CSS template and use the appropriate route.
		if ( 'css' === $item['type'] ) {
			$edit_url = admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $item['id'] . '#/setup/' . $item['id'] . '/globalcss' );
		} else {
			$edit_url = admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $item['id'] );
		}
		$actions['edit'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $edit_url ),
			__( 'Edit Design', 'coming-soon' )
		);

		// Edit Conditions action (not for CSS or Part types).
		if ( 'css' !== $item['type'] && 'part' !== $item['type'] ) {
			$actions['conditions'] = sprintf(
				'<a href="#" class="seedprod-edit-conditions" data-id="%s">%s</a>',
				$item['id'],
				__( 'Edit Conditions', 'coming-soon' )
			);
		}

		// Duplicate action.
		$actions['duplicate'] = sprintf(
			'<a href="#" class="seedprod-duplicate-template" data-id="%s" data-nonce="%s">%s</a>',
			$item['id'],
			wp_create_nonce( 'seedprod_duplicate_template_' . $item['id'] ),
			__( 'Duplicate', 'coming-soon' )
		);

		// Trash/Delete action.
		if ( 'trash' === $item['status'] ) {
			$actions['restore'] = sprintf(
				'<a href="#" class="seedprod-restore-template" data-id="%s" data-nonce="%s">%s</a>',
				$item['id'],
				wp_create_nonce( 'seedprod_restore_template_' . $item['id'] ),
				__( 'Restore', 'coming-soon' )
			);
			$actions['delete']  = sprintf(
				'<a href="#" class="seedprod-delete-template" data-id="%s" data-nonce="%s" style="color: #a00;">%s</a>',
				$item['id'],
				wp_create_nonce( 'seedprod_delete_template_' . $item['id'] ),
				__( 'Delete Permanently', 'coming-soon' )
			);
		} else {
			$actions['trash'] = sprintf(
				'<a href="#" class="seedprod-trash-template" data-id="%s" data-nonce="%s" style="color: #a00;">%s</a>',
				$item['id'],
				wp_create_nonce( 'seedprod_trash_template_' . $item['id'] ),
				__( 'Trash', 'coming-soon' )
			);
		}

		// Create title with edit link.
		$title = sprintf(
			'<strong><a href="%s">%s</a></strong>',
			esc_url( $edit_url ),
			esc_html( $item['title'] )
		);

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Published column with toggle switch
	 *
	 * @param array $item The item data.
	 */
	protected function column_published( $item ) {
		$checked = ( true === $item['published'] ) ? 'checked' : '';
		$nonce   = wp_create_nonce( 'seedprod_toggle_template_' . $item['id'] );

		return sprintf(
			'<label class="seedprod-switch seedprod-switch-sm">
				<input type="checkbox" class="seedprod-template-toggle" data-id="%s" data-nonce="%s" %s>
				<span class="seedprod-slider"></span>
			</label>',
			$item['id'],
			$nonce,
			$checked
		);
	}

	/**
	 * Get bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for display.
		$filter = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : 'all';

		if ( 'trash' === $filter ) {
			$actions['restore'] = __( 'Restore', 'coming-soon' );
			$actions['delete']  = __( 'Delete Permanently', 'coming-soon' );
		} else {
			$actions['trash'] = __( 'Move to Trash', 'coming-soon' );
		}

		return $actions;
	}

	/**
	 * Prepare items for display
	 */
	public function prepare_items() {
		// Set column headers.
		$this->_column_headers = array(
			$this->get_columns(),
			array(), // Hidden columns.
			$this->get_sortable_columns(),
		);

		// Get data.
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Get filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for display.
		$filter = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : 'all';

		// Get search term.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for display.
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		// Query arguments.
		$args = array(
			'post_type'      => 'seedprod',
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for theme template filtering.
				array(
					'key'   => '_seedprod_is_theme_template',
					'value' => true,
				),
			),
		);

		// Apply filter.
		if ( 'published' === $filter ) {
			$args['post_status'] = 'publish';
		} elseif ( 'drafts' === $filter ) {
			$args['post_status'] = 'draft';
		} elseif ( 'trash' === $filter ) {
			$args['post_status'] = 'trash';
		} else {
			$args['post_status'] = array( 'publish', 'draft', 'future' );
		}

		// Apply search.
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Apply sorting.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for display.
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'date';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for display.
		$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc';

		switch ( $orderby ) {
			case 'title':
				$args['orderby'] = 'title';
				break;
			case 'priority':
				$args['orderby'] = 'menu_order';
				break;
			case 'date':
			default:
				$args['orderby'] = 'modified';
				break;
		}
		$args['order'] = strtoupper( $order );

		// Get posts.
		$query     = new WP_Query( $args );
		$templates = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				// Get template data.
				$type               = get_post_meta( $post_id, '_seedprod_page_template_type', true );
				$conditions         = get_post_meta( $post_id, '_seedprod_theme_template_condition', true );
				$conditions_display = $this->get_conditions_display( $conditions );
				$priority           = get_post_field( 'menu_order', $post_id );

				$templates[] = array(
					'id'                 => $post_id,
					'title'              => get_the_title(),
					'type'               => $type,
					'conditions'         => $conditions,
					'conditions_display' => $conditions_display,
					'published'          => ( get_post_status() === 'publish' ),
					'priority'           => $priority,
					'status'             => get_post_status(),
					'date'               => get_the_modified_date( 'Y/m/d' ),
				);
			}
			wp_reset_postdata();
		}

		// Set items.
		$this->items = $templates;

		// Get total items for pagination.
		$total_args = $args;
		unset( $total_args['posts_per_page'] );
		unset( $total_args['offset'] );
		$total_query = new WP_Query( $total_args );
		$total_items = $total_query->found_posts;

		// Set pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Get conditions display string
	 *
	 * @param string $conditions_json The conditions JSON string.
	 */
	private function get_conditions_display( $conditions_json ) {
		if ( empty( $conditions_json ) ) {
			return '';
		}

		$conditions = json_decode( $conditions_json );
		if ( empty( $conditions ) || ! is_array( $conditions ) ) {
			return '';
		}

		$conditions_map = seedprod_lite_conditions_map();
		$display_parts  = array();

		foreach ( $conditions as $condition ) {
			$text = '';

			// Check if excluded.
			$is_excluded = ( isset( $condition->condition ) && 'exclude' === $condition->condition );

			// Get base condition text.
			if ( isset( $conditions_map[ $condition->type ] ) ) {
				$text = $conditions_map[ $condition->type ];
			} else {
				$text = $condition->type;
			}

			// Add value if present.
			if ( ! empty( $condition->value ) ) {
				if ( 'custom' === $condition->condition ) {
					$text = 'Custom : ' . $condition->value;
				} else {
					$text .= ' : ' . $condition->value;
				}
			}

			// Apply exclusion styling.
			if ( $is_excluded ) {
				$text = '<span style="text-decoration: line-through;">' . $text . '</span>';
			}

			$display_parts[] = $text;
		}

		return implode( ', ', $display_parts );
	}

	/**
	 * Message for no items
	 */
	public function no_items() {
		esc_html_e( 'No theme templates found.', 'coming-soon' );
	}

	/**
	 * Display the table
	 */
	public function display() {
		$this->display_tablenav( 'top' );
		?>
		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
			<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
			</thead>
			<tbody id="the-list">
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
			<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
			</tfoot>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which The location (top or bottom).
	 */
	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( $this->has_items() ) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php
			endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}
}
