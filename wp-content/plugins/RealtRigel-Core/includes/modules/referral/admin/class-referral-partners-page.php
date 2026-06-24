<?php
/**
 * Referral partners admin page.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Partners_Page {

	/**
	 * Partner repository.
	 *
	 * @var RR_Referral_Partner_Repository
	 */
	private RR_Referral_Partner_Repository $partner_repository;

	/**
	 * Partner service.
	 *
	 * @var RR_Referral_Partner_Service
	 */
	private RR_Referral_Partner_Service $partner_service;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Partner_Repository $partner_repository Partner repository.
	 * @param RR_Referral_Partner_Service    $partner_service Partner service.
	 */
	public function __construct( RR_Referral_Partner_Repository $partner_repository, RR_Referral_Partner_Service $partner_service ) {
		$this->partner_repository = $partner_repository;
		$this->partner_service    = $partner_service;
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

		$page        = isset( $_GET['paged'] ) ? max( 1, (int) wp_unslash( $_GET['paged'] ) ) : 1;
		$per_page    = 20;
		$partners    = $this->partner_repository->list_all( $per_page, $page );
		$total       = $this->partner_repository->count_all();
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Referral Partners', 'realtrigel-core' ); ?></h1>
			<p><?php esc_html_e( 'Service list of all partner accounts registered in the referral system.', 'realtrigel-core' ); ?></p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'User', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Email', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Partner Code', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Referral Link', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Status', 'realtrigel-core' ); ?></th>
						<th><?php esc_html_e( 'Created', 'realtrigel-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $partners ) ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'No partners found.', 'realtrigel-core' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $partners as $partner ) : ?>
							<?php
							$user         = ! empty( $partner['user_id'] ) ? get_userdata( (int) $partner['user_id'] ) : false;
							$display_name = $user instanceof \WP_User ? $user->display_name : '—';
							$user_email   = $user instanceof \WP_User ? $user->user_email : '—';
							$referral_url = $this->partner_service->get_referral_url_for_user( (int) $partner['user_id'] );
							?>
							<tr>
								<td><?php echo esc_html( (string) $partner['id'] ); ?></td>
								<td><?php echo esc_html( $display_name ); ?></td>
								<td><?php echo esc_html( $user_email ); ?></td>
								<td><code><?php echo esc_html( (string) $partner['partner_code'] ); ?></code></td>
								<td>
									<?php if ( '' !== $referral_url ) : ?>
										<a href="<?php echo esc_url( $referral_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $referral_url ); ?></a>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( (string) $partner['status'] ); ?></td>
								<td><?php echo esc_html( $this->format_datetime( (string) $partner['created_at'] ) ); ?></td>
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
	 * Format datetime for display.
	 *
	 * @param string $value Datetime value.
	 * @return string
	 */
	private function format_datetime( string $value ): string {
		$timestamp = strtotime( $value );

		return false !== $timestamp ? wp_date( 'd.m.Y H:i', $timestamp ) : '—';
	}
}
