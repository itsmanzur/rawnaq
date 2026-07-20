<?php
/**
 * Smart Form admin: list columns, unread badge, CSV export.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Smart_Form_Admin {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_export_csv' ] );
		add_filter( 'manage_rawnaq_sf_entry_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_rawnaq_sf_entry_posts_custom_column', [ $this, 'column_content' ], 10, 2 );
		add_action( 'add_meta_boxes', [ $this, 'meta_box' ] );
		add_action( 'load-post.php', [ $this, 'mark_read_on_edit' ] );
		add_action( 'admin_menu', [ $this, 'menu_badge' ], 999 );
		add_filter( 'bulk_actions-edit-rawnaq_sf_entry', [ $this, 'bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-rawnaq_sf_entry', [ $this, 'handle_bulk' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'export_link_notice' ] );
	}

	public function columns( $cols ) {
		$new = [];
		$new['cb']          = $cols['cb'] ?? '';
		$new['title']       = __( 'Submission', 'rawnaq' );
		$new['sf_fields']   = __( 'Fields', 'rawnaq' );
		$new['sf_channels'] = __( 'Channels', 'rawnaq' );
		$new['sf_unread']   = __( 'Status', 'rawnaq' );
		$new['date']        = __( 'Date', 'rawnaq' );
		return $new;
	}

	public function column_content( $col, $post_id ) {
		if ( 'sf_fields' === $col ) {
			$values = get_post_meta( $post_id, '_rawnaq_sf_values', true );
			if ( ! is_array( $values ) || ! $values ) {
				echo '—';
				return;
			}
			echo '<table class="rawnaq-sf-admin-table" style="border-collapse:collapse;width:100%;font-size:12px;">';
			$i = 0;
			foreach ( $values as $k => $v ) {
				if ( $i++ > 5 ) {
					echo '<tr><td colspan="2">…</td></tr>';
					break;
				}
				echo '<tr><th style="text-align:left;padding:2px 8px 2px 0;color:#646970;">' . esc_html( $k ) . '</th>';
				echo '<td style="padding:2px 0;">' . esc_html( wp_html_excerpt( (string) $v, 80, '…' ) ) . '</td></tr>';
			}
			echo '</table>';
			return;
		}
		if ( 'sf_channels' === $col ) {
			$ch = get_post_meta( $post_id, '_rawnaq_sf_channels', true );
			$bits = [];
			if ( ! empty( $ch['email'] ) ) {
				$bits[] = 'Email';
			}
			if ( ! empty( $ch['whatsapp'] ) ) {
				$bits[] = 'WhatsApp';
			}
			echo $bits ? esc_html( implode( ', ', $bits ) ) : '—';
			return;
		}
		if ( 'sf_unread' === $col ) {
			$unread = get_post_meta( $post_id, '_rawnaq_sf_unread', true );
			if ( '1' === (string) $unread ) {
				echo '<span style="background:#fbbf24;color:#92400e;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;">' . esc_html__( 'Unread', 'rawnaq' ) . '</span>';
			} else {
				echo '<span style="color:#646970;">' . esc_html__( 'Read', 'rawnaq' ) . '</span>';
			}
		}
	}

	public function meta_box() {
		add_meta_box(
			'rawnaq_sf_values',
			__( 'Submission fields', 'rawnaq' ),
			[ $this, 'render_meta_box' ],
			'rawnaq_sf_entry',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		$values = get_post_meta( $post->ID, '_rawnaq_sf_values', true );
		if ( ! is_array( $values ) || ! $values ) {
			echo '<p>' . esc_html__( 'No structured fields stored.', 'rawnaq' ) . '</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Field', 'rawnaq' ) . '</th><th>' . esc_html__( 'Value', 'rawnaq' ) . '</th></tr></thead><tbody>';
		foreach ( $values as $k => $v ) {
			$v = (string) $v;
			if ( filter_var( $v, FILTER_VALIDATE_URL ) ) {
				$cell = '<a href="' . esc_url( $v ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $v ) . '</a>';
			} else {
				$cell = esc_html( $v );
			}
			echo '<tr><th style="width:180px;">' . esc_html( $k ) . '</th><td>' . $cell . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</tbody></table>';
		$form_id = get_post_meta( $post->ID, '_rawnaq_sf_form_id', true );
		if ( $form_id ) {
			echo '<p style="margin-top:12px;"><strong>' . esc_html__( 'Form ID:', 'rawnaq' ) . '</strong> ' . esc_html( $form_id ) . '</p>';
		}
	}

	public function mark_read_on_edit() {
		if ( empty( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $post_id || 'rawnaq_sf_entry' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_rawnaq_sf_unread', '0' );
	}

	public function menu_badge() {
		global $menu;
		if ( ! function_exists( 'rawnaq_smart_form_unread_count' ) ) {
			return;
		}
		$count = rawnaq_smart_form_unread_count();
		if ( $count < 1 || ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=rawnaq_sf_entry' === $item[2] ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- standard unread-count bubble on our own admin menu item (same pattern as Comments/Updates).
				$menu[ $i ][0] .= ' <span class="awaiting-mod">' . esc_html( (string) $count ) . '</span>';
				break;
			}
		}
	}

	public function bulk_actions( $actions ) {
		$actions['rawnaq_sf_mark_read']   = __( 'Mark as read', 'rawnaq' );
		$actions['rawnaq_sf_mark_unread'] = __( 'Mark as unread', 'rawnaq' );
		$actions['rawnaq_sf_export_csv']  = __( 'Export selected CSV', 'rawnaq' );
		return $actions;
	}

	public function handle_bulk( $redirect, $action, $post_ids ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $redirect;
		}
		if ( 'rawnaq_sf_mark_read' === $action ) {
			foreach ( $post_ids as $id ) {
				if ( current_user_can( 'edit_post', $id ) ) {
					update_post_meta( $id, '_rawnaq_sf_unread', '0' );
				}
			}
		}
		if ( 'rawnaq_sf_mark_unread' === $action ) {
			foreach ( $post_ids as $id ) {
				if ( current_user_can( 'edit_post', $id ) ) {
					update_post_meta( $id, '_rawnaq_sf_unread', '1' );
				}
			}
		}
		if ( 'rawnaq_sf_export_csv' === $action && $post_ids ) {
			$allowed = [];
			foreach ( $post_ids as $id ) {
				if ( current_user_can( 'edit_post', $id ) ) {
					$allowed[] = $id;
				}
			}
			if ( $allowed ) {
				$this->stream_csv( $allowed );
				exit;
			}
		}
		return $redirect;
	}

	public function export_link_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-rawnaq_sf_entry' !== $screen->id ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'edit.php?post_type=rawnaq_sf_entry&rawnaq_sf_export=all' ), 'rawnaq_sf_export' );
		echo '<div class="notice notice-info"><p><a class="button button-secondary" href="' . esc_url( $url ) . '">' . esc_html__( 'Export all submissions (CSV)', 'rawnaq' ) . '</a></p></div>';
	}

	public function maybe_export_csv() {
		if ( empty( $_GET['rawnaq_sf_export'] ) || 'all' !== $_GET['rawnaq_sf_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		check_admin_referer( 'rawnaq_sf_export' );
		$ids = get_posts(
			[
				'post_type'      => 'rawnaq_sf_entry',
				'post_status'    => 'private',
				'posts_per_page' => 500,
				'fields'         => 'ids',
			]
		);
		$this->stream_csv( $ids );
		exit;
	}

	/**
	 * @param int[] $post_ids IDs.
	 */
	private function stream_csv( $post_ids ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=rawnaq-form-submissions-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		if ( ! $out ) {
			return;
		}
		$keys = [];
		$rows = [];
		foreach ( $post_ids as $id ) {
			$values = get_post_meta( $id, '_rawnaq_sf_values', true );
			if ( ! is_array( $values ) ) {
				$values = [];
			}
			foreach ( array_keys( $values ) as $k ) {
				$keys[ $k ] = true;
			}
			$rows[] = [
				'id'      => $id,
				'title'   => get_the_title( $id ),
				'date'    => get_the_date( 'c', $id ),
				'form_id' => get_post_meta( $id, '_rawnaq_sf_form_id', true ),
				'unread'  => get_post_meta( $id, '_rawnaq_sf_unread', true ),
				'values'  => $values,
			];
		}
		$key_list = array_keys( $keys );
		$header   = array_merge( [ 'id', 'title', 'date', 'form_id', 'unread' ], $key_list );
		fputcsv( $out, $header );
		foreach ( $rows as $row ) {
			$line = [ $row['id'], $row['title'], $row['date'], $row['form_id'], $row['unread'] ];
			foreach ( $key_list as $k ) {
				$line[] = $row['values'][ $k ] ?? '';
			}
			fputcsv( $out, $line );
		}
		// php://output stream — WP_Filesystem has no equivalent for CSV download streaming.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $out );
	}
}
