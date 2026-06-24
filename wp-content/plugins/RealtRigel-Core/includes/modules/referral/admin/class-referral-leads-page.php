<?php
/**
 * Referral leads admin page.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Leads_Page {

	/**
	 * Lead repository.
	 *
	 * @var RR_Referral_Lead_Repository
	 */
	private RR_Referral_Lead_Repository $lead_repository;

	/**
	 * Partner repository.
	 *
	 * @var RR_Referral_Partner_Repository
	 */
	private RR_Referral_Partner_Repository $partner_repository;

	/**
	 * Lead status history repository.
	 *
	 * @var RR_Referral_Lead_Status_History_Repository
	 */
	private RR_Referral_Lead_Status_History_Repository $lead_status_history_repository;

	/**
	 * Contact mask service.
	 *
	 * @var RR_Referral_Contact_Mask_Service
	 */
	private RR_Referral_Contact_Mask_Service $contact_mask_service;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Lead_Repository      $lead_repository Lead repository.
	 * @param RR_Referral_Partner_Repository   $partner_repository Partner repository.
	 * @param RR_Referral_Lead_Status_History_Repository $lead_status_history_repository Lead status history repository.
	 * @param RR_Referral_Contact_Mask_Service $contact_mask_service Contact mask service.
	 */
	public function __construct( RR_Referral_Lead_Repository $lead_repository, RR_Referral_Partner_Repository $partner_repository, RR_Referral_Lead_Status_History_Repository $lead_status_history_repository, RR_Referral_Contact_Mask_Service $contact_mask_service ) {
		$this->lead_repository                = $lead_repository;
		$this->partner_repository             = $partner_repository;
		$this->lead_status_history_repository = $lead_status_history_repository;
		$this->contact_mask_service           = $contact_mask_service;
	}

	/**
	 * Export leads when requested.
	 *
	 * @return void
	 */
	public function maybe_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['page'], $_GET['rr_leads_export'] ) || RR_Referral_Admin_Pages::LEADS_SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		check_admin_referer( 'rr_referral_export_leads' );

		$format = sanitize_key( wp_unslash( $_GET['rr_leads_export'] ) );
		$leads  = $this->lead_repository->list_all_for_export();

		if ( 'xls' === $format ) {
			$this->export_xls( $leads );
		}

		$this->export_csv( $leads );
	}

	/**
	 * Delete lead when requested.
	 *
	 * @return void
	 */
	public function maybe_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['page'], $_GET['rr_delete_lead'] ) || RR_Referral_Admin_Pages::LEADS_SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		$lead_id = absint( wp_unslash( $_GET['rr_delete_lead'] ) );

		if ( $lead_id <= 0 ) {
			return;
		}

		check_admin_referer( 'rr_referral_delete_lead_' . $lead_id );

		$this->lead_status_history_repository->delete_by_lead_id( $lead_id );
		$this->lead_repository->delete( $lead_id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => RR_Referral_Admin_Pages::LEADS_SLUG,
					'rr_lead_deleted' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page         = isset( $_GET['paged'] ) ? max( 1, (int) wp_unslash( $_GET['paged'] ) ) : 1;
		$per_page     = 20;
		$leads        = $this->lead_repository->list_all( $per_page, $page );
		$partners     = $this->partner_repository->list_all( 500, 1 );
		$total        = $this->lead_repository->count_all();
		$total_pages  = max( 1, (int) ceil( $total / $per_page ) );
		$partner_map  = array();

		foreach ( $partners as $partner ) {
			$partner_map[ (int) $partner['id'] ] = $partner;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Referral Leads', 'realtrigel-core' ); ?></h1>
			<?php if ( isset( $_GET['rr_lead_deleted'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Lead deleted.', 'realtrigel-core' ); ?></p>
				</div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Service list of leads captured by the referral system.', 'realtrigel-core' ); ?></p>
			<p>
				<a class="button" href="<?php echo esc_url( $this->get_export_url( 'csv' ) ); ?>"><?php esc_html_e( 'Export CSV', 'realtrigel-core' ); ?></a>
				<a class="button" href="<?php echo esc_url( $this->get_export_url( 'xls' ) ); ?>"><?php esc_html_e( 'Export XLS', 'realtrigel-core' ); ?></a>
			</p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Partner', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Source', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Телефон', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Email', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Telegram', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Message', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Property', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Language', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Bitrix ID', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Bitrix status', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Status', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Created', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Updated', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'realtrigel-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $leads ) ) : ?>
						<tr>
							<td colspan="15"><?php esc_html_e( 'No leads found.', 'realtrigel-core' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $leads as $lead ) : ?>
							<?php
							$partner         = $partner_map[ (int) $lead['partner_id'] ] ?? null;
							$partner_code    = is_array( $partner ) ? (string) $partner['partner_code'] : '—';
							$catalog_post_id = isset( $lead['catalog_post_id'] ) ? (int) $lead['catalog_post_id'] : 0;
							$property_title  = $catalog_post_id > 0 ? get_the_title( $catalog_post_id ) : '—';
							?>
							<?php
							$property_title = isset( $lead['object_title'] ) ? (string) $lead['object_title'] : '';

							if ( '' === $property_title && $catalog_post_id > 0 ) {
								$current_title  = get_the_title( $catalog_post_id );
								$property_title = is_string( $current_title ) ? $current_title : '';
							}

							if ( empty( $lead['contact_message'] ) ) {
								$lead['contact_message'] = '-';
							}

							if ( empty( $lead['language'] ) ) {
								$lead['language'] = '-';
							}
							?>
							<tr>
								<td><?php echo esc_html( (string) $lead['id'] ); ?></td>
								<td><code><?php echo esc_html( $partner_code ); ?></code></td>
								<td><?php echo esc_html( (string) $lead['source_type'] ); ?></td>
								<td><?php echo esc_html( '' !== (string) ( $lead['contact_phone'] ?? '' ) ? (string) $lead['contact_phone'] : '-' ); ?></td>
								<td><?php echo esc_html( '' !== (string) ( $lead['contact_email'] ?? '' ) ? (string) $lead['contact_email'] : '-' ); ?></td>
								<td><?php echo esc_html( '' !== (string) ( $lead['contact_telegram'] ?? '' ) ? (string) $lead['contact_telegram'] : '-' ); ?></td>
								<td><?php echo esc_html( wp_trim_words( (string) ( $lead['contact_message'] ?? '' ), 8, '...' ) ?: 'â€”' ); ?></td>
								<td><?php echo esc_html( is_string( $property_title ) && '' !== $property_title ? $property_title : '—' ); ?></td>
								<td><?php echo esc_html( (string) ( $lead['language'] ?? 'â€”' ) ); ?></td>
								<td><?php echo esc_html( '' !== (string) ( $lead['bitrix_lead_id'] ?? '' ) ? (string) $lead['bitrix_lead_id'] : '-' ); ?></td>
								<td><?php echo esc_html( $this->get_bitrix_status_display( $lead ) ); ?></td>
								<td><?php echo esc_html( (string) $lead['status'] ); ?></td>
								<td><?php echo esc_html( $this->format_datetime( (string) $lead['created_at'] ) ); ?></td>
								<td><?php echo esc_html( $this->format_datetime( (string) $lead['updated_at'] ) ); ?></td>
								<td>
									<a class="submitdelete" href="<?php echo esc_url( $this->get_delete_url( (int) $lead['id'] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this lead?', 'realtrigel-core' ) ); ?>');">
										<?php esc_html_e( 'Delete', 'realtrigel-core' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<?php
			if ( $total_pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $page,
							'total'     => $total_pages,
							'prev_text' => __( 'Prev', 'realtrigel-core' ),
							'next_text' => __( 'Next', 'realtrigel-core' ),
						)
					)
				);
				echo '</div></div>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Build export URL.
	 *
	 * @param string $format Export format.
	 * @return string
	 */
	private function get_export_url( string $format ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'page'            => RR_Referral_Admin_Pages::LEADS_SLUG,
					'rr_leads_export' => $format,
				),
				admin_url( 'admin.php' )
			),
			'rr_referral_export_leads'
		);
	}

	/**
	 * Build delete URL.
	 *
	 * @param int $lead_id Lead id.
	 * @return string
	 */
	private function get_delete_url( int $lead_id ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'page'           => RR_Referral_Admin_Pages::LEADS_SLUG,
					'rr_delete_lead' => $lead_id,
				),
				admin_url( 'admin.php' )
			),
			'rr_referral_delete_lead_' . $lead_id
		);
	}

	/**
	 * Export leads as CSV.
	 *
	 * @param array<int, array<string, mixed>> $leads Leads.
	 * @return never
	 */
	private function export_csv( array $leads ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=referral-leads-' . gmdate( 'Y-m-d' ) . '.csv' );

		echo "\xEF\xBB\xBF";

		$output = fopen( 'php://output', 'w' );

		if ( false !== $output ) {
			fputcsv( $output, $this->get_export_headers() );

			foreach ( $this->build_export_rows( $leads ) as $row ) {
				fputcsv( $output, $row );
			}

			fclose( $output );
		}

		exit;
	}

	/**
	 * Export leads as Excel-compatible HTML.
	 *
	 * @param array<int, array<string, mixed>> $leads Leads.
	 * @return never
	 */
	private function export_xls( array $leads ): void {
		nocache_headers();
		header( 'Content-Type: application/vnd.ms-excel; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=referral-leads-' . gmdate( 'Y-m-d' ) . '.xls' );

		echo "\xEF\xBB\xBF";
		echo '<table><thead><tr>';

		foreach ( $this->get_export_headers() as $header ) {
			echo '<th>' . esc_html( $header ) . '</th>';
		}

		echo '</tr></thead><tbody>';

		foreach ( $this->build_export_rows( $leads ) as $row ) {
			echo '<tr>';

			foreach ( $row as $cell ) {
				echo '<td>' . esc_html( $cell ) . '</td>';
			}

			echo '</tr>';
		}

		echo '</tbody></table>';
		exit;
	}

	/**
	 * Get export headers.
	 *
	 * @return string[]
	 */
	private function get_export_headers(): array {
		return array(
			'ID',
			'Created',
			'Updated',
			'Partner code',
			'Source',
			'Status',
			'Bitrix lead ID',
			'Bitrix status ID',
			'Bitrix status',
			'Bitrix synced at',
			'Object ID',
			'Object title',
			'Object URL',
			'Name',
			'Phone',
			'Email',
			'Telegram',
			'Message',
			'Redirect URL',
			'Language',
			'User Agent',
		);
	}

	/**
	 * Build export rows.
	 *
	 * @param array<int, array<string, mixed>> $leads Leads.
	 * @return array<int, array<int, string>>
	 */
	private function build_export_rows( array $leads ): array {
		$partner_map = $this->get_partner_code_map();
		$rows        = array();

		foreach ( $leads as $lead ) {
			$partner_id      = isset( $lead['partner_id'] ) ? (int) $lead['partner_id'] : 0;
			$catalog_post_id = isset( $lead['catalog_post_id'] ) ? (int) $lead['catalog_post_id'] : 0;
			$object_title    = isset( $lead['object_title'] ) ? (string) $lead['object_title'] : '';

			if ( '' === $object_title && $catalog_post_id > 0 ) {
				$current_title = get_the_title( $catalog_post_id );
				$object_title  = is_string( $current_title ) ? $current_title : '';
			}

			$rows[] = array(
				(string) ( $lead['id'] ?? '' ),
				(string) ( $lead['created_at'] ?? '' ),
				(string) ( $lead['updated_at'] ?? '' ),
				$partner_map[ $partner_id ] ?? '',
				(string) ( $lead['source_type'] ?? '' ),
				(string) ( $lead['status'] ?? '' ),
				(string) ( $lead['bitrix_lead_id'] ?? '' ),
				(string) ( $lead['bitrix_status_id'] ?? '' ),
				(string) ( $lead['bitrix_status_label'] ?? '' ),
				(string) ( $lead['bitrix_synced_at'] ?? '' ),
				$catalog_post_id > 0 ? (string) $catalog_post_id : '',
				is_string( $object_title ) ? $object_title : '',
				(string) ( $lead['object_url'] ?? '' ),
				(string) ( $lead['contact_name'] ?? '' ),
				(string) ( $lead['contact_phone'] ?? '' ),
				(string) ( $lead['contact_email'] ?? '' ),
				(string) ( $lead['contact_telegram'] ?? '' ),
				(string) ( $lead['contact_message'] ?? '' ),
				(string) ( $lead['redirect_url'] ?? '' ),
				(string) ( $lead['language'] ?? '' ),
				(string) ( $lead['user_agent'] ?? '' ),
			);
		}

		return $rows;
	}

	/**
	 * Get partner code map.
	 *
	 * @return array<int, string>
	 */
	private function get_partner_code_map(): array {
		$partners = $this->partner_repository->list_all( 5000, 1 );
		$map      = array();

		foreach ( $partners as $partner ) {
			$map[ (int) $partner['id'] ] = (string) $partner['partner_code'];
		}

		return $map;
	}

	/**
	 * Format datetime for display.
	 *
	 * @param string $value Datetime value.
	 * @return string
	 */
	private function format_datetime( string $value ): string {
		$timestamp = strtotime( $value );

		return false !== $timestamp ? wp_date( 'd.m.Y H:i', $timestamp ) : '—';
	}

	/**
	 * Get Bitrix status display value.
	 *
	 * @param array<string, mixed> $lead Lead.
	 * @return string
	 */
	private function get_bitrix_status_display( array $lead ): string {
		$label = isset( $lead['bitrix_status_label'] ) ? trim( (string) $lead['bitrix_status_label'] ) : '';
		$id    = isset( $lead['bitrix_status_id'] ) ? trim( (string) $lead['bitrix_status_id'] ) : '';

		if ( '' !== $label ) {
			return $label;
		}

		return '' !== $id ? $id : '-';
	}
}
