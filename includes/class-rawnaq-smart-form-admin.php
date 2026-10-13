<?php
/**
 * Smart Form admin: list columns, filters, detail view, unread badge, CSV export.
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Smart_Form_Admin {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_export_csv' ] );
		add_action( 'admin_init', [ $this, 'migrate_legacy_uploads' ] );
		add_action( 'rawnaq_sf_migrate_legacy_uploads_batch', [ $this, 'migrate_legacy_uploads' ] );
		add_filter( 'manage_rawnaq_sf_entry_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_rawnaq_sf_entry_posts_custom_column', [ $this, 'column_content' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'filter_dropdowns' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_query' ] );
		add_filter( 'posts_search', [ $this, 'search_across_values' ], 10, 2 );
		add_action( 'add_meta_boxes', [ $this, 'meta_box' ] );
		add_action( 'save_post_rawnaq_sf_entry', [ $this, 'save_entry_note' ] );
		add_action( 'load-post.php', [ $this, 'mark_read_on_edit' ] );
		add_action( 'wp_ajax_rawnaq_sf_mark_read', [ $this, 'ajax_mark_read' ] );
		add_action( 'admin_post_rawnaq_sf_toggle_read', [ $this, 'handle_toggle_read' ] );
		add_action( 'admin_menu', [ $this, 'menu_badge' ], 999 );
		add_filter( 'bulk_actions-edit-rawnaq_sf_entry', [ $this, 'bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-rawnaq_sf_entry', [ $this, 'handle_bulk' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'export_link_notice' ] );
		add_action( 'admin_notices', [ $this, 'fallback_lead_notice' ] );
		add_action( 'admin_notices', [ $this, 'legacy_uploads_unmovable_notice' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_entries_scripts' ] );
		add_action( 'wp_ajax_rawnaq_dismiss_legacy_uploads_notice', [ $this, 'dismiss_legacy_uploads_notice' ] );
		add_filter( 'post_row_actions', [ $this, 'row_actions' ], 10, 2 );
		add_action( 'admin_post_rawnaq_sf_retry_single', [ $this, 'handle_single_retry' ] );
		add_action( 'admin_post_rawnaq_sf_download', [ $this, 'handle_download' ] );
	}

	public function columns( $cols ) {
		$new = [];
		$new['cb']          = $cols['cb'] ?? '';
		$new['title']       = __( 'Submission', 'rawnaq' );
		$new['sf_form']     = __( 'Form', 'rawnaq' );
		$new['sf_fields']   = __( 'Fields', 'rawnaq' );
		$new['sf_channels'] = __( 'Channels', 'rawnaq' );
		$new['sf_delivery'] = __( 'Delivery', 'rawnaq' );
		$new['sf_unread']   = __( 'Status', 'rawnaq' );
		$new['date']        = __( 'Date', 'rawnaq' );
		return $new;
	}

	public function column_content( $col, $post_id ) {
		if ( 'sf_form' === $col ) {
			$form_label = get_post_meta( $post_id, '_rawnaq_sf_form_label', true );
			$form_id    = get_post_meta( $post_id, '_rawnaq_sf_form_id', true );
			echo '<strong>' . esc_html( $form_label ?: ( $form_id ?: '—' ) ) . '</strong>';
			if ( $form_label && $form_id && $form_label !== $form_id ) {
				echo '<div style="font-size:11px;color:#646970;">' . esc_html( $form_id ) . '</div>';
			}
			return;
		}

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
				$v_str = is_array( $v ) ? implode( ', ', array_map( 'strval', $v ) ) : (string) $v;
				echo '<tr><th style="text-align:left;padding:2px 8px 2px 0;color:#646970;">' . esc_html( $k ) . '</th>';
				echo '<td style="padding:2px 0;">' . esc_html( wp_html_excerpt( $v_str, 80, '…' ) ) . '</td></tr>';
			}
			echo '</table>';
			return;
		}

		if ( 'sf_channels' === $col ) {
			$ch   = get_post_meta( $post_id, '_rawnaq_sf_channels', true );
			$bits = [];
			if ( ! empty( $ch['email'] ) ) {
				$bits[] = __( 'Email', 'rawnaq' );
			}
			if ( ! empty( $ch['whatsapp'] ) ) {
				$bits[] = __( 'WhatsApp', 'rawnaq' );
			}
			echo $bits ? esc_html( implode( ', ', $bits ) ) : '—';
			return;
		}

		if ( 'sf_delivery' === $col ) {
			$delivery = get_post_meta( $post_id, '_rawnaq_sf_delivery', true );
			if ( ! is_array( $delivery ) ) {
				echo '<span style="color:#646970;">—</span>';
				return;
			}
			$items = [];
			$map   = [
				'email'         => __( 'Email', 'rawnaq' ),
				'autoresponder' => __( 'Auto-reply', 'rawnaq' ),
				'webhook'       => __( 'Webhook', 'rawnaq' ),
				'crm'           => __( 'CRM', 'rawnaq' ),
			];
			foreach ( $map as $channel => $name ) {
				$st = $delivery[ $channel ] ?? 'off';
				if ( 'ok' === $st ) {
					$items[] = '<span style="color:#15803d;font-weight:600;">' . esc_html( $name ) . ': ✓</span>';
				} elseif ( 'fail' === $st ) {
					$items[] = '<span style="color:#b91c1c;font-weight:600;">' . esc_html( $name ) . ': ✗</span>';
				} elseif ( 0 === strpos( (string) $st, 'skipped' ) ) {
					$items[] = '<span style="color:#646970;font-weight:600;" title="' . esc_attr( $st ) . '">' . esc_html( $name ) . ': ⊘</span>';
				}
			}
			if ( ( $delivery['whatsapp'] ?? '' ) === 'link' ) {
				$items[] = '<span style="color:#15803d;font-weight:600;">' . esc_html__( 'WhatsApp', 'rawnaq' ) . ': ✓</span>';
			}
			if ( empty( $items ) ) {
				echo '<span style="color:#646970;">' . esc_html__( 'Off', 'rawnaq' ) . '</span>';
			} else {
				echo implode( ' &bull; ', $items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				if ( ! empty( $delivery['errors'] ) && is_array( $delivery['errors'] ) ) {
					$err_summary = implode( ' | ', array_map( 'sanitize_text_field', $delivery['errors'] ) );
					echo '<div style="font-size:11px;color:#b91c1c;margin-top:3px;" title="' . esc_attr( $err_summary ) . '">' . esc_html( wp_html_excerpt( $err_summary, 60, '…' ) ) . '</div>';
				}
			}
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

	/**
	 * Filter dropdowns for form, status, and date range in entry list table.
	 *
	 * @param string $post_type Current post type.
	 */
	public function filter_dropdowns( $post_type ) {
		if ( 'rawnaq_sf_entry' !== $post_type ) {
			return;
		}

		// 1. Filter by Form
		$forms        = get_option( 'rawnaq_sf_registered_forms', [] );
		$current_form = isset( $_GET['sf_form'] ) ? sanitize_text_field( wp_unslash( $_GET['sf_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<select name="sf_form" id="sf_form">
			<option value=""><?php esc_html_e( 'All Forms', 'rawnaq' ); ?></option>
			<?php foreach ( (array) $forms as $f_id => $f_label ) : ?>
				<option value="<?php echo esc_attr( $f_id ); ?>" <?php selected( $current_form, $f_id ); ?>>
					<?php echo esc_html( $f_label ?: $f_id ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php

		// 2. Filter by Status (read/unread)
		$current_status = isset( $_GET['sf_status'] ) ? sanitize_key( wp_unslash( $_GET['sf_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<select name="sf_status" id="sf_status">
			<option value=""><?php esc_html_e( 'All Statuses', 'rawnaq' ); ?></option>
			<option value="unread" <?php selected( $current_status, 'unread' ); ?>><?php esc_html_e( 'Unread only', 'rawnaq' ); ?></option>
			<option value="read" <?php selected( $current_status, 'read' ); ?>><?php esc_html_e( 'Read only', 'rawnaq' ); ?></option>
		</select>
		<?php

		// 3. Filter by Date range
		$date_from = isset( $_GET['sf_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['sf_date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_to   = isset( $_GET['sf_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['sf_date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<input type="date" name="sf_date_from" value="<?php echo esc_attr( $date_from ); ?>" style="line-height:28px;" title="<?php esc_attr_e( 'From date', 'rawnaq' ); ?>" />
		<input type="date" name="sf_date_to" value="<?php echo esc_attr( $date_to ); ?>" style="line-height:28px;" title="<?php esc_attr_e( 'To date', 'rawnaq' ); ?>" />
		<?php
	}

	/**
	 * Apply query filters (form, status, date range) to WP_Query.
	 *
	 * @param WP_Query $query The current query.
	 */
	public function filter_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'rawnaq_sf_entry' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = (array) ( $query->get( 'meta_query' ) ?: [] );

		// Filter by form ID
		if ( ! empty( $_GET['sf_form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$meta_query[] = [
				'key'     => '_rawnaq_sf_form_id',
				'value'   => sanitize_text_field( wp_unslash( $_GET['sf_form'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'compare' => '=',
			];
		}

		// Filter by read/unread status
		if ( ! empty( $_GET['sf_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$st = sanitize_key( wp_unslash( $_GET['sf_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'unread' === $st ) {
				$meta_query[] = [
					'key'     => '_rawnaq_sf_unread',
					'value'   => '1',
					'compare' => '=',
				];
			} elseif ( 'read' === $st ) {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key'     => '_rawnaq_sf_unread',
						'value'   => '0',
						'compare' => '=',
					],
					[
						'key'     => '_rawnaq_sf_unread',
						'compare' => 'NOT EXISTS',
					],
				];
			}
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}

		// Filter by date range
		$date_query = [];
		if ( ! empty( $_GET['sf_date_from'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$date_query['after']     = sanitize_text_field( wp_unslash( $_GET['sf_date_from'] ) ) . ' 00:00:00'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$date_query['inclusive'] = true;
		}
		if ( ! empty( $_GET['sf_date_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$date_query['before']    = sanitize_text_field( wp_unslash( $_GET['sf_date_to'] ) ) . ' 23:59:59'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$date_query['inclusive'] = true;
		}
		if ( ! empty( $date_query ) ) {
			$query->set( 'date_query', [ $date_query ] );
		}
	}

	/**
	 * Search across submitted field values as well as title and content.
	 *
	 * @param string   $search   Search SQL.
	 * @param WP_Query $wp_query Query object.
	 * @return string
	 */
	public function search_across_values( $search, $wp_query ) {
		if ( ! is_admin() || ! $wp_query->is_main_query() ) {
			return $search;
		}
		if ( 'rawnaq_sf_entry' !== $wp_query->get( 'post_type' ) ) {
			return $search;
		}
		$s = $wp_query->get( 's' );
		if ( empty( $s ) ) {
			return $search;
		}

		global $wpdb;
		$term          = '%' . $wpdb->esc_like( $s ) . '%';
		$meta_subquery = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_rawnaq_sf_values' AND meta_value LIKE %s",
			$term
		);

		return " AND ({$wpdb->posts}.post_title LIKE '{$term}' OR {$wpdb->posts}.post_content LIKE '{$term}' OR {$wpdb->posts}.ID IN ({$meta_subquery})) ";
	}

	public function meta_box() {
		add_meta_box(
			'rawnaq_sf_values',
			__( 'Submission Details', 'rawnaq' ),
			[ $this, 'render_meta_box' ],
			'rawnaq_sf_entry',
			'normal',
			'high'
		);
		add_meta_box(
			'rawnaq_sf_note_box',
			__( 'Private Note', 'rawnaq' ),
			[ $this, 'render_note_meta_box' ],
			'rawnaq_sf_entry',
			'side',
			'default'
		);
	}

	public function render_note_meta_box( $post ) {
		wp_nonce_field( 'rawnaq_sf_save_note', 'rawnaq_sf_note_nonce' );
		$note = get_post_meta( $post->ID, '_rawnaq_sf_note', true );
		?>
		<p class="description" style="margin-top:0;">
			<?php esc_html_e( 'Private notes for internal team reference. Never shared with submitter.', 'rawnaq' ); ?>
		</p>
		<textarea name="rawnaq_sf_note" id="rawnaq_sf_note" rows="5" style="width:100%;margin-top:4px;" placeholder="<?php esc_attr_e( 'Add internal notes about this submission…', 'rawnaq' ); ?>"><?php echo esc_textarea( $note ); ?></textarea>
		<p style="margin-bottom:0;">
			<button type="submit" class="button button-secondary button-small" style="margin-top:6px;"><?php esc_html_e( 'Save Note', 'rawnaq' ); ?></button>
		</p>
		<?php
	}

	public function save_entry_note( $post_id ) {
		if ( ! isset( $_POST['rawnaq_sf_note_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rawnaq_sf_note_nonce'] ) ), 'rawnaq_sf_save_note' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( isset( $_POST['rawnaq_sf_note'] ) ) {
			$note = sanitize_textarea_field( wp_unslash( $_POST['rawnaq_sf_note'] ) );
			update_post_meta( $post_id, '_rawnaq_sf_note', $note );
		}
	}

	/**
	 * Render structured submission detail screen:
	 * Sections: Fields, Files, Delivery log, Attribution/UTM, Consent, plus Mark Read/Unread.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_meta_box( $post ) {
		$values = get_post_meta( $post->ID, '_rawnaq_sf_values', true );
		if ( ! is_array( $values ) || ! $values ) {
			echo '<p>' . esc_html__( 'No structured fields stored.', 'rawnaq' ) . '</p>';
			return;
		}

		$form_id    = get_post_meta( $post->ID, '_rawnaq_sf_form_id', true );
		$form_label = get_post_meta( $post->ID, '_rawnaq_sf_form_label', true );
		$unread     = '1' === (string) get_post_meta( $post->ID, '_rawnaq_sf_unread', true );
		$toggle_url = wp_nonce_url( admin_url( 'admin-post.php?action=rawnaq_sf_toggle_read&post_id=' . $post->ID ), 'rawnaq_sf_toggle_read_' . $post->ID );

		$files_meta       = get_post_meta( $post->ID, '_rawnaq_sf_files', true ) ?: [];
		$delivery         = get_post_meta( $post->ID, '_rawnaq_sf_delivery', true ) ?: [];
		$consent          = get_post_meta( $post->ID, '_rawnaq_sf_consent', true ) ?: [];
		$utm_keys         = [ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'referrer_url', 'landing_page' ];
		$standard_fields  = [];
		$marketing_fields = [];

		foreach ( $values as $k => $v ) {
			if ( 'consent' === $k ) {
				continue;
			}
			if ( in_array( $k, $utm_keys, true ) ) {
				if ( ! empty( $v ) ) {
					$marketing_fields[ $k ] = $v;
				}
			} elseif ( ! isset( $files_meta[ $k ] ) && 0 !== strpos( (string) $v, 'data:image/' ) && 'signature' !== $k ) {
				$standard_fields[ $k ] = $v;
			}
		}

		// Top Header Toolbar: Read/Unread Status & Action + Form Meta
		?>
		<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:20px;">
			<div style="display:flex;align-items:center;gap:10px;">
				<span style="font-weight:600;color:#334155;"><?php esc_html_e( 'Status:', 'rawnaq' ); ?></span>
				<?php if ( $unread ) : ?>
					<span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;"><?php esc_html_e( 'Unread', 'rawnaq' ); ?></span>
					<a href="<?php echo esc_url( $toggle_url ); ?>" class="button button-small button-secondary"><?php esc_html_e( 'Mark as read', 'rawnaq' ); ?></a>
				<?php else : ?>
					<span style="background:#e2e8f0;color:#475569;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;"><?php esc_html_e( 'Read', 'rawnaq' ); ?></span>
					<a href="<?php echo esc_url( $toggle_url ); ?>" class="button button-small button-secondary"><?php esc_html_e( 'Mark as unread', 'rawnaq' ); ?></a>
				<?php endif; ?>
			</div>
			<div style="font-size:12px;color:#64748b;">
				<strong><?php esc_html_e( 'Form:', 'rawnaq' ); ?></strong>
				<span><?php echo esc_html( $form_label ?: ( $form_id ?: '—' ) ); ?></span>
				<?php if ( $form_id && $form_label !== $form_id ) : ?>
					<code>(<?php echo esc_html( $form_id ); ?>)</code>
				<?php endif; ?>
				&bull;
				<strong><?php esc_html_e( 'Date:', 'rawnaq' ); ?></strong>
				<span><?php echo esc_html( get_the_date( 'Y-m-d H:i:s', $post->ID ) ); ?></span>
			</div>
		</div>

		<!-- SECTION 1: FIELDS -->
		<div class="rawnaq-sf-section" style="margin-bottom:24px;">
			<h3 style="margin:0 0 10px 0;font-size:15px;color:#1e293b;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-editor-ul" style="color:#0f766e;"></span>
				<?php esc_html_e( '1. Submitted Fields', 'rawnaq' ); ?>
			</h3>
			<?php if ( ! empty( $standard_fields ) ) : ?>
				<table class="widefat striped" style="border:1px solid #e2e8f0;box-shadow:none;">
					<thead>
						<tr>
							<th style="width:220px;background:#f8fafc;color:#475569;"><?php esc_html_e( 'Field', 'rawnaq' ); ?></th>
							<th style="background:#f8fafc;color:#475569;"><?php esc_html_e( 'Value', 'rawnaq' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $standard_fields as $k => $v ) :
							$v_str = is_array( $v ) ? implode( ', ', array_map( 'strval', $v ) ) : (string) $v;
							$label = ucwords( str_replace( [ '_', '-' ], ' ', esc_html( $k ) ) );
						?>
							<tr>
								<th style="color:#334155;font-weight:600;"><?php echo esc_html( $label ); ?></th>
								<td>
									<?php if ( filter_var( $v_str, FILTER_VALIDATE_URL ) ) : ?>
										<a href="<?php echo esc_url( $v_str ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $v_str ); ?></a>
									<?php else : ?>
										<?php echo nl2br( esc_html( $v_str ) ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p style="color:#64748b;"><?php esc_html_e( 'No standard text fields recorded.', 'rawnaq' ); ?></p>
			<?php endif; ?>
		</div>

		<!-- SECTION 2: FILES & SIGNATURES -->
		<div class="rawnaq-sf-section" style="margin-bottom:24px;">
			<h3 style="margin:0 0 10px 0;font-size:15px;color:#1e293b;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-paperclip" style="color:#0f766e;"></span>
				<?php esc_html_e( '2. Files & Signatures', 'rawnaq' ); ?>
			</h3>
			<?php
			$has_files = false;
			if ( ! empty( $files_meta ) ) :
				$has_files = true;
			?>
				<table class="widefat striped" style="border:1px solid #e2e8f0;box-shadow:none;">
					<thead>
						<tr>
							<th style="width:220px;background:#f8fafc;color:#475569;"><?php esc_html_e( 'Attachment', 'rawnaq' ); ?></th>
							<th style="background:#f8fafc;color:#475569;"><?php esc_html_e( 'Details & Download', 'rawnaq' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $files_meta as $fk => $file_info ) :
							$orig_name   = esc_html( $file_info['name'] ?? $file_info['original_name'] ?? 'file' );
							$dl_url      = function_exists( 'rawnaq_smart_form_get_download_url' )
								? rawnaq_smart_form_get_download_url( $post->ID, $fk )
								: wp_nonce_url( admin_url( 'admin-post.php?action=rawnaq_sf_download&entry=' . $post->ID . '&field=' . $fk ), 'rawnaq_sf_download_' . $post->ID . '_' . $fk );
							$is_image    = 0 === strpos( $file_info['mime'] ?? '', 'image/' );
							$size_str    = ! empty( $file_info['size'] ) ? size_format( (int) $file_info['size'] ) : '';
						?>
							<tr>
								<th style="color:#334155;font-weight:600;"><?php echo esc_html( ucwords( str_replace( [ '_', '-' ], ' ', $fk ) ) ); ?></th>
								<td>
									<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
										<?php if ( $is_image ) : ?>
											<img src="<?php echo esc_url( add_query_arg( 'inline', '1', $dl_url ) ); ?>" style="max-height:80px;max-width:240px;border:1px solid #cbd5e1;border-radius:6px;padding:4px;background:#fff;" alt="<?php echo esc_attr( $orig_name ); ?>" />
										<?php endif; ?>
										<div>
											<a class="button button-secondary button-small" href="<?php echo esc_url( $dl_url ); ?>">
												<span class="dashicons dashicons-download" style="vertical-align:middle;font-size:16px;"></span>
												<?php esc_html_e( 'Download', 'rawnaq' ); ?> (<?php echo esc_html( $orig_name ); ?>)
											</a>
											<?php if ( $size_str ) : ?>
												<span style="font-size:12px;color:#64748b;margin-left:6px;"><?php echo esc_html( $size_str ); ?></span>
											<?php endif; ?>
										</div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php
			// Fallback: legacy signatures stored in values
			foreach ( $values as $vk => $vv ) {
				$vv_str = (string) $vv;
				if ( ( 0 === strpos( $vv_str, 'data:image/' ) ) || ( 'signature' === $vk && 0 === strpos( $vv_str, 'data:image/' ) ) ) {
					$has_files = true;
					echo '<div style="margin-top:10px;padding:10px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;">';
					echo '<strong>' . esc_html( ucwords( str_replace( [ '_', '-' ], ' ', $vk ) ) ) . ':</strong><br />';
					echo '<img src="' . esc_attr( $vv_str ) . '" style="max-height:90px;max-width:320px;border:1px solid #cbd5e1;border-radius:6px;padding:4px;margin-top:6px;background:#fff;" alt="' . esc_attr__( 'Signature', 'rawnaq' ) . '" />';
					echo '</div>';
				}
			}
			if ( ! $has_files ) {
				echo '<p style="color:#64748b;font-style:italic;">' . esc_html__( 'No files or signatures submitted.', 'rawnaq' ) . '</p>';
			}
			?>
		</div>

		<!-- SECTION 3: DELIVERY LOG -->
		<div class="rawnaq-sf-section" style="margin-bottom:24px;">
			<h3 style="margin:0 0 10px 0;font-size:15px;color:#1e293b;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-share" style="color:#0f766e;"></span>
				<?php esc_html_e( '3. Delivery Log', 'rawnaq' ); ?>
			</h3>
			<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px;">
				<div style="display:flex;flex-wrap:wrap;gap:18px;">
					<?php
					$channels = [
						'email'         => [ 'label' => __( 'Admin Email', 'rawnaq' ), 'val' => $delivery['email'] ?? 'off' ],
						'autoresponder' => [ 'label' => __( 'Auto-responder', 'rawnaq' ), 'val' => $delivery['autoresponder'] ?? 'off' ],
						'webhook'       => [ 'label' => __( 'Webhook', 'rawnaq' ), 'val' => $delivery['webhook'] ?? 'off' ],
						'crm'           => [ 'label' => __( 'CRM / ESP', 'rawnaq' ), 'val' => $delivery['crm'] ?? 'off' ],
						'whatsapp'      => [ 'label' => __( 'WhatsApp Redirect', 'rawnaq' ), 'val' => ( ( $delivery['whatsapp'] ?? '' ) === 'link' ) ? 'link' : 'off' ],
					];
					foreach ( $channels as $ch_key => $ch_data ) :
						$st = $ch_data['val'];
					?>
						<div style="display:flex;align-items:center;gap:6px;">
							<strong style="color:#334155;font-size:13px;"><?php echo esc_html( $ch_data['label'] ); ?>:</strong>
							<?php if ( 'ok' === $st || 'link' === $st ) : ?>
								<span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;">✓ <?php esc_html_e( 'Sent', 'rawnaq' ); ?></span>
							<?php elseif ( 'fail' === $st ) : ?>
								<span style="background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;">✗ <?php esc_html_e( 'Failed', 'rawnaq' ); ?></span>
							<?php elseif ( 0 === strpos( (string) $st, 'skipped' ) ) : ?>
								<span style="background:#e2e8f0;color:#64748b;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;" title="<?php echo esc_attr( $st ); ?>">⊘ <?php echo esc_html( $st ); ?></span>
							<?php else : ?>
								<span style="color:#94a3b8;font-size:12px;"><?php esc_html_e( 'Disabled', 'rawnaq' ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( ! empty( $delivery['errors'] ) && is_array( $delivery['errors'] ) ) : ?>
					<div style="margin-top:12px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;font-size:12px;">
						<strong><?php esc_html_e( 'Delivery Errors:', 'rawnaq' ); ?></strong>
						<ul style="margin:4px 0 0 16px;padding:0;">
							<?php foreach ( $delivery['errors'] as $err ) : ?>
								<li><?php echo esc_html( $err ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- SECTION 4: ATTRIBUTION / UTM -->
		<div class="rawnaq-sf-section" style="margin-bottom:24px;">
			<h3 style="margin:0 0 10px 0;font-size:15px;color:#1e293b;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-chart-line" style="color:#0f766e;"></span>
				<?php esc_html_e( '4. Campaign & Lead Attribution (UTM)', 'rawnaq' ); ?>
			</h3>
			<?php if ( ! empty( $marketing_fields ) ) : ?>
				<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;">
					<table class="widefat" style="background:transparent;border:0;box-shadow:none;">
						<tbody>
							<?php foreach ( $marketing_fields as $mk => $mv ) :
								$mv_str = (string) $mv;
								$cell   = filter_var( $mv_str, FILTER_VALIDATE_URL )
									? '<a href="' . esc_url( $mv_str ) . '" target="_blank" rel="noopener noreferrer" style="color:#15803d;">' . esc_html( $mv_str ) . '</a>'
									: '<strong style="color:#15803d;">' . esc_html( $mv_str ) . '</strong>';
							?>
								<tr>
									<th style="width:200px;padding:4px 8px;border:0;color:#14532d;"><?php echo esc_html( $mk ); ?></th>
									<td style="padding:4px 8px;border:0;"><?php echo $cell; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<p style="color:#64748b;font-style:italic;"><?php esc_html_e( 'Direct submission (no campaign or UTM attribution parameters).', 'rawnaq' ); ?></p>
			<?php endif; ?>
		</div>

		<!-- SECTION 5: CONSENT -->
		<div class="rawnaq-sf-section" style="margin-bottom:12px;">
			<h3 style="margin:0 0 10px 0;font-size:15px;color:#1e293b;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-yes-alt" style="color:#0f766e;"></span>
				<?php esc_html_e( '5. Consent & Privacy Record', 'rawnaq' ); ?>
			</h3>
			<?php if ( is_array( $consent ) && ! empty( $consent['text'] ) ) : ?>
				<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;">
					<p style="margin:0 0 6px 0;color:#334155;font-weight:500;">
						<span class="dashicons dashicons-yes" style="color:#16a34a;vertical-align:text-bottom;"></span>
						<?php echo esc_html( $consent['text'] ); ?>
					</p>
					<?php if ( ! empty( $consent['timestamp'] ) ) : ?>
						<div style="font-size:11px;color:#94a3b8;">
							<?php echo esc_html( sprintf( /* translators: %s: formatted date */ __( 'Consent timestamp: %s UTC', 'rawnaq' ), wp_date( 'Y-m-d H:i:s', absint( $consent['timestamp'] ) ) ) ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<p style="color:#64748b;font-style:italic;"><?php esc_html_e( 'Consent checkbox was not required for this form submission.', 'rawnaq' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
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
		if ( '1' !== get_post_meta( $post_id, '_rawnaq_sf_unread', true ) ) {
			return; // Already read, nothing to do.
		}

		add_action( 'admin_enqueue_scripts', function () use ( $post_id ) {
			wp_register_script( 'rawnaq-sf-mark-read', '', [], RAWNAQ_VERSION, true );
			wp_enqueue_script( 'rawnaq-sf-mark-read' );

			$data = [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'postId'  => $post_id,
				'nonce'   => wp_create_nonce( 'rawnaq_sf_mark_read_' . $post_id ),
			];

			wp_add_inline_script(
				'rawnaq-sf-mark-read',
				'window.rawnaqSfMarkRead = ' . wp_json_encode( $data ) . ';' .
				'(function(){var d=window.rawnaqSfMarkRead;var body=new URLSearchParams();' .
				'body.append("action","rawnaq_sf_mark_read");body.append("post_id",d.postId);body.append("nonce",d.nonce);' .
				'fetch(d.ajaxUrl,{method:"POST",credentials:"same-origin",body:body});})();'
			);
		} );
	}

	public function ajax_mark_read() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		check_ajax_referer( 'rawnaq_sf_mark_read_' . $post_id, 'nonce' );

		if ( ! $post_id || 'rawnaq_sf_entry' !== get_post_type( $post_id ) ) {
			wp_send_json_error();
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error();
		}
		update_post_meta( $post_id, '_rawnaq_sf_unread', '0' );
		wp_send_json_success();
	}

	public function handle_toggle_read() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		if ( ! $post_id || 'rawnaq_sf_entry' !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'Invalid entry.', 'rawnaq' ) );
		}
		check_admin_referer( 'rawnaq_sf_toggle_read_' . $post_id );
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'rawnaq' ), 403 );
		}

		$current = (string) get_post_meta( $post_id, '_rawnaq_sf_unread', true );
		$new     = ( '1' === $current ) ? '0' : '1';
		update_post_meta( $post_id, '_rawnaq_sf_unread', $new );

		wp_safe_redirect( wp_get_referer() ?: get_edit_post_link( $post_id, 'raw' ) );
		exit;
	}

	public function menu_badge() {
		global $menu;
		if ( ! rawnaq_smart_form_can_manage_entries() ) {
			return;
		}
		if ( ! function_exists( 'rawnaq_smart_form_unread_count' ) ) {
			return;
		}
		$count = rawnaq_smart_form_unread_count();
		if ( $count < 1 || ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=rawnaq_sf_entry' === $item[2] ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- standard unread-count bubble on our own admin menu item.
				$menu[ $i ][0] .= ' <span class="awaiting-mod">' . esc_html( (string) $count ) . '</span>';
				break;
			}
		}
	}

	public function bulk_actions( $actions ) {
		$actions['rawnaq_sf_mark_read']      = __( 'Mark as read', 'rawnaq' );
		$actions['rawnaq_sf_mark_unread']    = __( 'Mark as unread', 'rawnaq' );
		$actions['rawnaq_sf_export_csv']     = __( 'Export selected CSV', 'rawnaq' );
		$actions['rawnaq_sf_retry_delivery'] = __( 'Retry failed delivery', 'rawnaq' );
		return $actions;
	}

	public function handle_bulk( $redirect, $action, $post_ids ) {
		if ( ! rawnaq_smart_form_can_manage_entries() ) {
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
		if ( 'rawnaq_sf_retry_delivery' === $action && $post_ids ) {
			$count = 0;
			foreach ( $post_ids as $id ) {
				if ( current_user_can( 'edit_post', $id ) ) {
					$delivery = get_post_meta( $id, '_rawnaq_sf_delivery', true );
					if ( is_array( $delivery ) && ( ( $delivery['email'] ?? '' ) === 'fail' || ( $delivery['webhook'] ?? '' ) === 'fail' || ( $delivery['crm'] ?? '' ) === 'fail' ) ) {
						$attempts = (int) get_post_meta( $id, '_rawnaq_sf_retry_attempts', true );
						if ( $attempts < 3 ) {
							wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'rawnaq_sf_retry_delivery', [ $id ] );
							$count++;
						}
					}
				}
			}
			return add_query_arg( [ 'rawnaq_sf_retried' => $count ], $redirect );
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

	public function row_actions( $actions, $post ) {
		if ( 'rawnaq_sf_entry' !== $post->post_type || ! rawnaq_smart_form_can_manage_entries() ) {
			return $actions;
		}
		$delivery = get_post_meta( $post->ID, '_rawnaq_sf_delivery', true );
		if ( is_array( $delivery ) && ( ( $delivery['email'] ?? '' ) === 'fail' || ( $delivery['webhook'] ?? '' ) === 'fail' || ( $delivery['crm'] ?? '' ) === 'fail' ) ) {
			$attempts = (int) get_post_meta( $post->ID, '_rawnaq_sf_retry_attempts', true );
			if ( $attempts < 3 ) {
				$retry_url = wp_nonce_url( admin_url( 'admin-post.php?action=rawnaq_sf_retry_single&post_id=' . $post->ID ), 'rawnaq_sf_retry_' . $post->ID );
				$actions['rawnaq_sf_retry'] = '<a href="' . esc_url( $retry_url ) . '">' . esc_html__( 'Retry delivery', 'rawnaq' ) . '</a>';
			} else {
				$actions['rawnaq_sf_retry'] = '<span style="color:#646970;">' . esc_html__( 'Max retries reached', 'rawnaq' ) . '</span>';
			}
		}
		return $actions;
	}

	public function handle_single_retry() {
		if ( ! rawnaq_smart_form_can_manage_entries() ) {
			wp_die( esc_html__( 'Unauthorized.', 'rawnaq' ) );
		}
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		check_admin_referer( 'rawnaq_sf_retry_' . $post_id );

		$attempts = (int) get_post_meta( $post_id, '_rawnaq_sf_retry_attempts', true );
		if ( $attempts < 3 ) {
			wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'rawnaq_sf_retry_delivery', [ $post_id ] );
		}
		wp_safe_redirect( add_query_arg( [ 'rawnaq_sf_retried' => 1 ], admin_url( 'edit.php?post_type=rawnaq_sf_entry' ) ) );
		exit;
	}

	/**
	 * Admin-only download endpoint for uploaded files and signatures.
	 *
	 * admin-post.php?action=rawnaq_sf_download&entry=ID&field=ID&_wpnonce=...
	 */
	public function handle_download() {
		if ( ! function_exists( 'rawnaq_smart_form_can_manage_entries' ) || ! rawnaq_smart_form_can_manage_entries() ) {
			wp_die( esc_html__( 'Unauthorized.', 'rawnaq' ), 403 );
		}
		$entry_id = isset( $_GET['entry'] ) ? absint( $_GET['entry'] ) : 0;
		$field_id = isset( $_GET['field'] ) ? sanitize_key( wp_unslash( $_GET['field'] ) ) : '';

		if ( ! $entry_id || ! $field_id || 'rawnaq_sf_entry' !== get_post_type( $entry_id ) ) {
			wp_die( esc_html__( 'Invalid entry or field.', 'rawnaq' ), 404 );
		}
		if ( ! current_user_can( 'edit_post', $entry_id ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'rawnaq' ), 403 );
		}

		check_admin_referer( 'rawnaq_sf_download_' . $entry_id . '_' . $field_id );

		$files = get_post_meta( $entry_id, '_rawnaq_sf_files', true );
		if ( ! is_array( $files ) || empty( $files[ $field_id ] ) ) {
			wp_die( esc_html__( 'File not found.', 'rawnaq' ), 404 );
		}

		$file_info  = $files[ $field_id ];
		$upload_dir = function_exists( 'rawnaq_smart_form_get_upload_dir' ) ? rawnaq_smart_form_get_upload_dir() : ( wp_upload_dir()['basedir'] . '/rawnaq-forms' );
		$rel_path   = basename( $file_info['path'] ?? '' );
		$abs_path   = $upload_dir . '/' . $rel_path;

		if ( ! file_exists( $abs_path ) || ! is_file( $abs_path ) ) {
			wp_die( esc_html__( 'File not found on disk.', 'rawnaq' ), 404 );
		}

		$real_dir  = realpath( $upload_dir );
		$real_file = realpath( $abs_path );
		if ( ! $real_file || 0 !== strpos( $real_file, $real_dir ) ) {
			wp_die( esc_html__( 'Access denied.', 'rawnaq' ), 403 );
		}

		$utf8_name   = (string) ( $file_info['name'] ?? ( $file_info['original_name'] ?? 'download' ) );
		$ascii_clean = sanitize_file_name( $utf8_name );
		// ASCII fallback: sanitize_file_name with non-ASCII stripped, never empty.
		$ascii_name  = preg_replace( '/[^\x20-\x7E]/', '', $ascii_clean );
		$ascii_name  = trim( str_replace( [ '"', "'", ';', '\\' ], '', $ascii_name ) );
		if ( '' === $ascii_name || '.' === $ascii_name ) {
			$ext        = pathinfo( $ascii_clean, PATHINFO_EXTENSION );
			$ascii_name = $ext ? ( 'download.' . $ext ) : 'download';
		}

		$mime        = ! empty( $file_info['mime'] ) ? sanitize_mime_type( $file_info['mime'] ) : 'application/octet-stream';
		$is_inline   = ! empty( $_GET['inline'] ) && 0 === strpos( $mime, 'image/' );
		$disposition = $is_inline ? 'inline' : 'attachment';

		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: ' . $disposition . '; filename="' . $ascii_name . '"; filename*=UTF-8\'\'' . rawurlencode( $utf8_name ) );
		$fsize = filesize( $abs_path );
		if ( $fsize > 0 ) {
			header( 'Content-Length: ' . (int) $fsize );
		}

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		readfile( $abs_path );
		exit;
	}

	public function fallback_lead_notice() {
		if ( ! rawnaq_smart_form_can_manage_entries() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-rawnaq_sf_entry' !== $screen->id ) {
			return;
		}
		if ( isset( $_GET['rawnaq_sf_retried'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$count = absint( $_GET['rawnaq_sf_retried'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( sprintf( /* translators: %d: number of submissions */ __( 'Delivery retry scheduled for %d submission(s) in 5 minutes.', 'rawnaq' ), $count ) ) . '</p></div>';
		}
		$fallback_posts = get_posts( [
			'post_type'      => 'rawnaq_sf_entry',
			'post_status'    => [ 'private', 'publish' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_rawnaq_sf_fallback',
			'meta_value'     => '1',
		] );
		if ( ! empty( $fallback_posts ) ) {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'Smart Form Fallback Leads:', 'rawnaq' ) . '</strong> ' . esc_html__( 'One or more submissions were automatically saved as fallback entries because email delivery was disabled or failed.', 'rawnaq' ) . '</p></div>';
		}
	}

	public function export_link_notice() {
		if ( ! rawnaq_smart_form_can_manage_entries() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-rawnaq_sf_entry' !== $screen->id ) {
			return;
		}

		$params = [
			'post_type'        => 'rawnaq_sf_entry',
			'rawnaq_sf_export' => 'all',
		];

		$has_filters = false;
		foreach ( [ 'sf_form', 'sf_status', 'sf_date_from', 'sf_date_to', 's' ] as $param ) {
			if ( ! empty( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$params[ $param ] = sanitize_text_field( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$has_filters      = true;
			}
		}

		$url      = wp_nonce_url( add_query_arg( $params, admin_url( 'edit.php' ) ), 'rawnaq_sf_export' );
		$btn_text = $has_filters ? __( 'Export filtered submissions (CSV)', 'rawnaq' ) : __( 'Export all submissions (CSV)', 'rawnaq' );

		echo '<div class="notice notice-info"><p><a class="button button-secondary" href="' . esc_url( $url ) . '"><span class="dashicons dashicons-media-spreadsheet" style="vertical-align:middle;margin-right:4px;"></span>' . esc_html( $btn_text ) . '</a></p></div>';
	}

	public function maybe_export_csv() {
		if ( empty( $_GET['rawnaq_sf_export'] ) || 'all' !== $_GET['rawnaq_sf_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! rawnaq_smart_form_can_manage_entries() ) {
			return;
		}
		check_admin_referer( 'rawnaq_sf_export' );

		$query_args = [
			'post_type'      => 'rawnaq_sf_entry',
			'post_status'    => [ 'private', 'publish' ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];

		$meta_query = [];
		if ( ! empty( $_GET['sf_form'] ) ) {
			$meta_query[] = [
				'key'     => '_rawnaq_sf_form_id',
				'value'   => sanitize_text_field( wp_unslash( $_GET['sf_form'] ) ),
				'compare' => '=',
			];
		}
		if ( ! empty( $_GET['sf_status'] ) ) {
			$st = sanitize_key( wp_unslash( $_GET['sf_status'] ) );
			if ( 'unread' === $st ) {
				$meta_query[] = [
					'key'     => '_rawnaq_sf_unread',
					'value'   => '1',
					'compare' => '=',
				];
			} elseif ( 'read' === $st ) {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key'     => '_rawnaq_sf_unread',
						'value'   => '0',
						'compare' => '=',
					],
					[
						'key'     => '_rawnaq_sf_unread',
						'compare' => 'NOT EXISTS',
					],
				];
			}
		}
		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$date_query = [];
		if ( ! empty( $_GET['sf_date_from'] ) ) {
			$date_query['after']     = sanitize_text_field( wp_unslash( $_GET['sf_date_from'] ) ) . ' 00:00:00';
			$date_query['inclusive'] = true;
		}
		if ( ! empty( $_GET['sf_date_to'] ) ) {
			$date_query['before']    = sanitize_text_field( wp_unslash( $_GET['sf_date_to'] ) ) . ' 23:59:59';
			$date_query['inclusive'] = true;
		}
		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = [ $date_query ];
		}

		if ( ! empty( $_GET['s'] ) ) {
			$query_args['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}

		$ids = get_posts( $query_args );
		$this->stream_csv( $ids );
		exit;
	}

	/**
	 * Stream submissions as CSV in batches of 200.
	 *
	 * @param int[] $post_ids IDs.
	 */
	private function stream_csv( $post_ids ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=rawnaq-form-submissions-' . gmdate( 'Y-m-d' ) . '.csv' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$out = fopen( 'php://output', 'w' );
		if ( ! $out ) {
			return;
		}
		// Write UTF-8 BOM so Excel displays Arabic and non-ASCII UTF-8 characters correctly.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $out, "\xEF\xBB\xBF" );

		$keys    = [];
		$batches = array_chunk( (array) $post_ids, 200 );
		foreach ( $batches as $batch ) {
			foreach ( $batch as $id ) {
				$values = get_post_meta( $id, '_rawnaq_sf_values', true );
				if ( is_array( $values ) ) {
					foreach ( array_keys( $values ) as $k ) {
						$keys[ $k ] = true;
					}
				}
			}
		}
		$key_list = array_keys( $keys );
		$header   = array_merge( [ 'id', 'title', 'date', 'form_id', 'form_label', 'unread', 'delivery_status', 'note' ], $key_list );
		fputcsv( $out, array_map( [ $this, 'sanitize_csv_cell' ], $header ) );

		foreach ( $batches as $batch ) {
			if ( function_exists( '_prime_post_caches' ) ) {
				_prime_post_caches( $batch, false, true );
			}
			foreach ( $batch as $id ) {
				$values = get_post_meta( $id, '_rawnaq_sf_values', true );
				if ( ! is_array( $values ) ) {
					$values = [];
				}
				$title      = get_the_title( $id );
				$delivery   = get_post_meta( $id, '_rawnaq_sf_delivery', true );
				$del_status = is_array( $delivery )
					? 'email:' . ( $delivery['email'] ?? 'off' ) . '; ar:' . ( $delivery['autoresponder'] ?? 'off' ) . '; crm:' . ( $delivery['crm'] ?? 'off' )
					: '';
				$note       = get_post_meta( $id, '_rawnaq_sf_note', true );

				$line = [
					$this->sanitize_csv_cell( (string) $id ),
					$this->sanitize_csv_cell( (string) $title ),
					$this->sanitize_csv_cell( (string) get_the_date( 'c', $id ) ),
					$this->sanitize_csv_cell( (string) get_post_meta( $id, '_rawnaq_sf_form_id', true ) ),
					$this->sanitize_csv_cell( (string) get_post_meta( $id, '_rawnaq_sf_form_label', true ) ),
					$this->sanitize_csv_cell( (string) get_post_meta( $id, '_rawnaq_sf_unread', true ) ),
					$this->sanitize_csv_cell( (string) $del_status ),
					$this->sanitize_csv_cell( (string) $note ),
				];
				foreach ( $key_list as $k ) {
					$val     = $values[ $k ] ?? '';
					$val_str = is_array( $val ) ? implode( ', ', array_map( 'strval', $val ) ) : (string) $val;
					if ( 0 === strpos( $val_str, 'data:' ) || ( 'signature' === $k && 0 === strpos( $val_str, 'data:image/' ) ) ) {
						$val_str = '[signature]';
					}
					$line[] = $this->sanitize_csv_cell( $val_str );
				}
				fputcsv( $out, $line );
			}
			if ( function_exists( 'ob_flush' ) && ob_get_level() > 0 ) {
				ob_flush();
			}
			flush();
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $out );
	}

	/**
	 * Guard against CSV formula injection (prefix single quote if starting with =, +, -, @, tab, or newline).
	 *
	 * @param string $cell Raw cell content.
	 * @return string
	 */
	private function sanitize_csv_cell( $cell ) {
		$str = (string) $cell;
		if ( '' !== $str && preg_match( '/^[=\+\-@\t\r]/', $str ) ) {
			return "'" . $str;
		}
		return $str;
	}

	/**
	 * Scan and migrate legacy uploads into the protected rawnaq-forms directory.
	 * Processes at most 50 entries per request and reschedules the rest.
	 */
	public function migrate_legacy_uploads() {
		if ( get_option( 'rawnaq_sf_legacy_uploads_checked' ) ) {
			return;
		}

		$upload_dir_info = wp_upload_dir();
		$baseurl         = $upload_dir_info['baseurl'];
		$basedir         = wp_normalize_path( $upload_dir_info['basedir'] );
		$protected_dir   = function_exists( 'rawnaq_smart_form_get_upload_dir' )
			? rawnaq_smart_form_get_upload_dir()
			: ( $upload_dir_info['basedir'] . '/rawnaq-forms' );

		if ( ! file_exists( $protected_dir ) ) {
			wp_mkdir_p( $protected_dir );
		}
		$real_protected = realpath( $protected_dir );
		$norm_protected = $real_protected ? ( rtrim( wp_normalize_path( $real_protected ), '/' ) . '/' ) : '';

		$entries = get_posts( [
			'post_type'      => 'rawnaq_sf_entry',
			'post_status'    => 'any',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => '_rawnaq_sf_files',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_rawnaq_sf_legacy_migrated',
					'compare' => 'NOT EXISTS',
				],
			],
		] );

		if ( empty( $entries ) ) {
			update_option( 'rawnaq_sf_legacy_uploads_checked', 1 );
			return;
		}

		$moved_count     = (int) get_option( 'rawnaq_sf_legacy_uploads_moved_count', 0 );
		$unmovable_count = (int) get_option( 'rawnaq_sf_legacy_uploads_unmovable_count', 0 );

		foreach ( $entries as $entry_id ) {
			$files_meta = get_post_meta( $entry_id, '_rawnaq_sf_files', true );
			if ( is_array( $files_meta ) && ! empty( $files_meta ) ) {
				$changed = false;
				foreach ( $files_meta as $field_id => $file_info ) {
					$raw_path = is_array( $file_info ) ? ( $file_info['path'] ?? '' ) : (string) $file_info;
					if ( ! $raw_path ) {
						continue;
					}

					$in_protected = rtrim( $protected_dir, '/\\' ) . DIRECTORY_SEPARATOR . basename( $raw_path );
					if ( false === strpos( $raw_path, '/' ) && false === strpos( $raw_path, '\\' ) && false === strpos( $raw_path, '://' ) && is_file( $in_protected ) ) {
						continue;
					}

					$src = '';
					if ( 0 === strpos( $raw_path, 'http://' ) || 0 === strpos( $raw_path, 'https://' ) ) {
						if ( 0 === strpos( $raw_path, $baseurl ) ) {
							$src = str_replace( $baseurl, $basedir, $raw_path );
						}
					} elseif ( file_exists( $raw_path ) ) {
						$src = $raw_path;
					} elseif ( file_exists( $basedir . '/' . ltrim( $raw_path, '/' ) ) ) {
						$src = $basedir . '/' . ltrim( $raw_path, '/' );
					}

					if ( ! $src || ! file_exists( $src ) || ! is_file( $src ) ) {
						continue;
					}

					$norm_src = wp_normalize_path( realpath( $src ) ?: $src );
					if ( $norm_protected && 0 === strpos( $norm_src, $norm_protected ) ) {
						continue; // Already in protected folder
					}

					$orig_name  = is_array( $file_info ) ? ( $file_info['original_name'] ?? ( $file_info['name'] ?? basename( $src ) ) ) : basename( $src );
					$clean_name = sanitize_file_name( $orig_name );
					$prefix     = wp_generate_password( 12, false, false ) . '-';
					$new_name   = $prefix . $clean_name;
					$dest       = rtrim( $protected_dir, '/\\' ) . DIRECTORY_SEPARATOR . $new_name;

					$moved = @rename( $src, $dest );
					if ( ! $moved ) {
						$moved = @copy( $src, $dest );
						if ( $moved ) {
							@unlink( $src );
						}
					}

					if ( $moved ) {
						$moved_count++;
						$changed = true;
						if ( is_array( $file_info ) ) {
							$files_meta[ $field_id ]['path'] = $new_name;
						} else {
							$files_meta[ $field_id ] = [
								'path'          => $new_name,
								'original_name' => $clean_name,
								'name'          => $clean_name,
								'mime'          => wp_check_filetype( $clean_name )['type'] ?: 'application/octet-stream',
								'size'          => filesize( $dest ),
							];
						}
					} else {
						$unmovable_count++;
					}
				}

				if ( $changed ) {
					update_post_meta( $entry_id, '_rawnaq_sf_files', $files_meta );
				}
			}

			update_post_meta( $entry_id, '_rawnaq_sf_legacy_migrated', 1 );
		}

		update_option( 'rawnaq_sf_legacy_uploads_moved_count', $moved_count );
		if ( $unmovable_count > 0 ) {
			update_option( 'rawnaq_sf_legacy_uploads_unmovable_count', $unmovable_count );
			update_option( 'rawnaq_sf_legacy_uploads_show_notice', 1 );
		}

		if ( count( $entries ) < 50 ) {
			update_option( 'rawnaq_sf_legacy_uploads_checked', 1 );
		} else {
			if ( ! wp_next_scheduled( 'rawnaq_sf_migrate_legacy_uploads_batch' ) ) {
				wp_schedule_single_event( time() + 5, 'rawnaq_sf_migrate_legacy_uploads_batch' );
			}
		}
	}

	/**
	 * Enqueue scripts for the Entries screen.
	 *
	 * Hooked to admin_enqueue_scripts so that inline scripts attached to jQuery
	 * are registered before head scripts are printed.
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 */
	public function enqueue_entries_scripts( $hook_suffix = '' ) {
		if ( ! rawnaq_smart_form_can_manage_entries() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-rawnaq_sf_entry' !== $screen->id ) {
			return;
		}
		if ( ! get_option( 'rawnaq_sf_legacy_uploads_show_notice' ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), 'rawnaq_dismiss_legacy_uploads_notice', true ) ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_add_inline_script(
			'jquery',
			'jQuery(document).on("click", ".rawnaq-sf-legacy-notice .notice-dismiss", function() {' .
			'var nonce = jQuery(this).closest(".rawnaq-sf-legacy-notice").data("nonce");' .
			'jQuery.post(ajaxurl, { action: "rawnaq_dismiss_legacy_uploads_notice", nonce: nonce });' .
			'});'
		);
	}

	/**
	 * Dismissible admin notice on the Entries screen if older attachments could not be moved.
	 */
	public function legacy_uploads_unmovable_notice() {
		if ( ! rawnaq_smart_form_can_manage_entries() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-rawnaq_sf_entry' !== $screen->id ) {
			return;
		}
		if ( ! get_option( 'rawnaq_sf_legacy_uploads_show_notice' ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), 'rawnaq_dismiss_legacy_uploads_notice', true ) ) {
			return;
		}

		$count = (int) get_option( 'rawnaq_sf_legacy_uploads_unmovable_count', 0 );
		$nonce = wp_create_nonce( 'rawnaq_dismiss_legacy_uploads' );
		?>
		<div class="notice notice-warning is-dismissible rawnaq-sf-legacy-notice" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<p>
				<strong><?php esc_html_e( 'Rawnaq Forms:', 'rawnaq' ); ?></strong>
				<?php
				echo esc_html( sprintf(
					/* translators: %d: number of unmovable files */
					__( '%d older form attachment(s) could not be moved into the protected folder and may still be publicly reachable. Please verify your uploads folder permissions.', 'rawnaq' ),
					$count
				) );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * AJAX handler for dismissing legacy uploads unmovable notice.
	 */
	public function dismiss_legacy_uploads_notice() {
		check_ajax_referer( 'rawnaq_dismiss_legacy_uploads', 'nonce' );
		if ( rawnaq_smart_form_can_manage_entries() ) {
			update_user_meta( get_current_user_id(), 'rawnaq_dismiss_legacy_uploads_notice', 1 );
			update_option( 'rawnaq_sf_legacy_uploads_show_notice', 0 );
		}
		wp_send_json_success();
	}
}
