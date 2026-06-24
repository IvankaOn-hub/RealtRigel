<?php
/**
 * Frontend partner dashboard shortcode.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Dashboard_Shortcode {

	/**
	 * Partner service.
	 *
	 * @var RR_Referral_Partner_Service
	 */
	private RR_Referral_Partner_Service $partner_service;

	/**
	 * Lead repository.
	 *
	 * @var RR_Referral_Lead_Repository
	 */
	private RR_Referral_Lead_Repository $lead_repository;

	/**
	 * Contact mask service.
	 *
	 * @var RR_Referral_Contact_Mask_Service
	 */
	private RR_Referral_Contact_Mask_Service $contact_mask_service;

	/**
	 * Bitrix status sync service.
	 *
	 * @var RR_Referral_Bitrix_Status_Sync_Service
	 */
	private RR_Referral_Bitrix_Status_Sync_Service $bitrix_status_sync_service;

	/**
	 * QR code service.
	 *
	 * @var RR_Referral_Qr_Service
	 */
	private RR_Referral_Qr_Service $qr_service;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Partner_Service      $partner_service Partner service.
	 * @param RR_Referral_Lead_Repository      $lead_repository Lead repository.
	 * @param RR_Referral_Contact_Mask_Service $contact_mask_service Contact mask service.
	 * @param RR_Referral_Bitrix_Status_Sync_Service $bitrix_status_sync_service Bitrix status sync service.
	 * @param RR_Referral_Qr_Service           $qr_service QR service.
	 */
	public function __construct( RR_Referral_Partner_Service $partner_service, RR_Referral_Lead_Repository $lead_repository, RR_Referral_Contact_Mask_Service $contact_mask_service, RR_Referral_Bitrix_Status_Sync_Service $bitrix_status_sync_service, RR_Referral_Qr_Service $qr_service ) {
		$this->partner_service            = $partner_service;
		$this->lead_repository            = $lead_repository;
		$this->contact_mask_service       = $contact_mask_service;
		$this->bitrix_status_sync_service = $bitrix_status_sync_service;
		$this->qr_service                 = $qr_service;
	}

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	public function init(): void {
		add_shortcode( 'rr_partner_dashboard', array( $this, 'render' ) );
	}

	/**
	 * Render partner dashboard.
	 *
	 * @return string
	 */
	public function render(): string {
		if ( ! is_user_logged_in() ) {
			return '<div class="rr-referral-dashboard rr-referral-dashboard--state"><p>' . esc_html__( 'Пожалуйста, войдите, чтобы получить доступ к кабинету партнера.', 'realtrigel-core' ) . '</p></div>';
		}

		$user = wp_get_current_user();

		if ( ! in_array( RR_Referral_Role_Service::ROLE, (array) $user->roles, true ) ) {
			return '<div class="rr-referral-dashboard rr-referral-dashboard--state"><p>' . esc_html__( 'Ваш аккаунт не настроен как партнерский.', 'realtrigel-core' ) . '</p></div>';
		}

		$partner      = $this->partner_service->create_partner_for_user( get_current_user_id() );
		$referral_url = $this->partner_service->get_referral_url_for_user( get_current_user_id() );
		$qr_image_url = '' !== $referral_url ? $this->qr_service->build_qr_image_url( $referral_url ) : '';
		$logout_url   = wp_logout_url( home_url( '/' ) );

		if ( ! is_array( $partner ) || empty( $partner['id'] ) ) {
			return '<div class="rr-referral-dashboard rr-referral-dashboard--state"><p>' . esc_html__( 'Не удалось загрузить профиль партнера.', 'realtrigel-core' ) . '</p></div>';
		}

		$page        = isset( $_GET['rr_leads_page'] ) ? max( 1, (int) wp_unslash( $_GET['rr_leads_page'] ) ) : 1;
		$per_page    = 20;
		$leads       = $this->lead_repository->list_by_partner_id( (int) $partner['id'], $per_page, $page );
		$this->bitrix_status_sync_service->sync_leads( $leads );
		$leads       = $this->lead_repository->list_by_partner_id( (int) $partner['id'], $per_page, $page );
		$stats       = $this->lead_repository->get_status_counts_by_partner_id( (int) $partner['id'] );
		$total       = $this->lead_repository->count_by_partner_id( (int) $partner['id'] );
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		$success     = isset( $_GET['rr_partner_registered'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['rr_partner_registered'] ) );

		wp_enqueue_style(
			'rr-referral-dashboard',
			plugins_url( 'assets/css/referral-dashboard.css', RR_CORE_PLUGIN_FILE ),
			array(),
			filemtime( plugin_dir_path( RR_CORE_PLUGIN_FILE ) . 'assets/css/referral-dashboard.css' )
		);

		ob_start();
		?>
		<div class="rr-referral-dashboard">
			<div class="rr-referral-dashboard__hero">
				<div class="rr-referral-dashboard__hero-copy">
					<p class="rr-referral-dashboard__eyebrow"><?php esc_html_e( 'Кабинет партнера', 'realtrigel-core' ); ?></p>
					<h2 class="rr-referral-dashboard__hero-title"><?php echo esc_html( sprintf( __( 'Здравствуйте, %s', 'realtrigel-core' ), $user->display_name ?: $user->user_email ) ); ?></h2>
					<p class="rr-referral-dashboard__hero-text"><?php esc_html_e( 'Здесь находится ваша персональная реферальная ссылка и текущий статус лидов, закрепленных за вашим аккаунтом.', 'realtrigel-core' ); ?></p>
				</div>

				<div class="rr-referral-dashboard__hero-card">
					<span class="rr-referral-dashboard__label"><?php esc_html_e( 'Реферальная ссылка', 'realtrigel-core' ); ?></span>
					<input type="text" readonly value="<?php echo esc_attr( $referral_url ); ?>" onclick="this.select();">
					<span class="rr-referral-dashboard__meta"><?php echo esc_html( sprintf( __( 'Код партнера: %s', 'realtrigel-core' ), (string) $partner['partner_code'] ) ); ?></span>
					<div class="rr-referral-dashboard__actions">
						<a class="rr-referral-dashboard__logout" href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Выйти', 'realtrigel-core' ); ?></a>
					</div>
					<?php if ( '' !== $qr_image_url ) : ?>
						<div class="rr-referral-dashboard__qr">
							<img src="<?php echo esc_attr( $qr_image_url ); ?>" alt="<?php esc_attr_e( 'QR-код реферальной ссылки', 'realtrigel-core' ); ?>" loading="lazy">
							<span class="rr-referral-dashboard__meta"><?php esc_html_e( 'QR-код для быстрого обмена ссылкой', 'realtrigel-core' ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $success ) : ?>
				<div class="rr-referral-dashboard__notice rr-referral-dashboard__notice--success">
					<?php esc_html_e( 'Регистрация завершена. Ваш партнерский аккаунт активен.', 'realtrigel-core' ); ?>
				</div>
			<?php endif; ?>

			<div class="rr-referral-dashboard__stats">
				<div class="rr-referral-dashboard__stat">
					<span><?php esc_html_e( 'Всего лидов', 'realtrigel-core' ); ?></span>
					<strong><?php echo esc_html( (string) $stats['total'] ); ?></strong>
				</div>
				<div class="rr-referral-dashboard__stat">
					<span><?php esc_html_e( 'Новые', 'realtrigel-core' ); ?></span>
					<strong><?php echo esc_html( (string) $stats['new'] ); ?></strong>
				</div>
				<div class="rr-referral-dashboard__stat">
					<span><?php esc_html_e( 'В работе', 'realtrigel-core' ); ?></span>
					<strong><?php echo esc_html( (string) $stats['in_progress'] ); ?></strong>
				</div>
				<div class="rr-referral-dashboard__stat">
					<span><?php esc_html_e( 'Сделки', 'realtrigel-core' ); ?></span>
					<strong><?php echo esc_html( (string) $stats['deal'] ); ?></strong>
				</div>
			</div>

			<div class="rr-referral-dashboard__leads">
				<div class="rr-referral-dashboard__section-head">
					<h3><?php esc_html_e( 'Мои лиды', 'realtrigel-core' ); ?></h3>
				</div>

				<?php if ( empty( $leads ) ) : ?>
					<div class="rr-referral-dashboard__empty">
						<p><?php esc_html_e( 'Лидов пока нет.', 'realtrigel-core' ); ?></p>
					</div>
				<?php else : ?>
					<div class="rr-referral-dashboard__table-wrap">
						<table class="rr-referral-dashboard__table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Дата', 'realtrigel-core' ); ?></th>
									<th><?php esc_html_e( 'Источник', 'realtrigel-core' ); ?></th>
									<th><?php esc_html_e( 'Контакт', 'realtrigel-core' ); ?></th>
									<th><?php esc_html_e( 'Статус', 'realtrigel-core' ); ?></th>
									<th><?php esc_html_e( 'Обновлено', 'realtrigel-core' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $leads as $lead ) : ?>
									<tr>
										<td><?php echo esc_html( $this->format_datetime( (string) $lead['created_at'] ) ); ?></td>
										<td><?php echo esc_html( $this->format_source( (string) $lead['source_type'] ) ); ?></td>
										<td><?php echo esc_html( $this->contact_mask_service->get_preferred_masked_contact( $lead ) ); ?></td>
										<td><span class="rr-referral-dashboard__status rr-referral-dashboard__status--<?php echo esc_attr( sanitize_html_class( $this->get_display_status_class( $lead ) ) ); ?>"><?php echo esc_html( $this->get_display_status_label( $lead ) ); ?></span></td>
										<td><?php echo esc_html( $this->format_datetime( (string) $lead['updated_at'] ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if ( $total_pages > 1 ) : ?>
						<div class="rr-referral-dashboard__pagination">
							<?php echo wp_kses_post(
								paginate_links(
									array(
										'base'      => add_query_arg( 'rr_leads_page', '%#%' ),
										'format'    => '',
										'current'   => $page,
										'total'     => $total_pages,
										'prev_text' => __( 'Prev', 'realtrigel-core' ),
										'next_text' => __( 'Next', 'realtrigel-core' ),
										'type'      => 'plain',
									)
								)
							); ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Format datetime for display.
	 *
	 * @param string $value Datetime string.
	 * @return string
	 */
	private function format_datetime( string $value ): string {
		$timestamp = strtotime( $value );

		return false !== $timestamp ? wp_date( 'd.m.Y H:i', $timestamp ) : '-';
	}

	/**
	 * Format source label.
	 *
	 * @param string $source Source key.
	 * @return string
	 */
	private function format_source( string $source ): string {
		$map = array(
			'form'     => __( 'Форма', 'realtrigel-core' ),
			'call'     => __( 'Звонок', 'realtrigel-core' ),
			'whatsapp' => __( 'WhatsApp', 'realtrigel-core' ),
			'telegram' => __( 'Telegram', 'realtrigel-core' ),
			'callback' => __( 'Обратный звонок', 'realtrigel-core' ),
		);

		return $map[ $source ] ?? ucfirst( $source );
	}

	/**
	 * Format status label.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	private function format_status( string $status ): string {
		$map = array(
			'new'         => __( 'Новый', 'realtrigel-core' ),
			'sent_to_crm' => __( 'Отправлен в CRM', 'realtrigel-core' ),
			'in_progress' => __( 'В работе', 'realtrigel-core' ),
			'qualified'   => __( 'Квалифицирован', 'realtrigel-core' ),
			'rejected'    => __( 'Отклонен', 'realtrigel-core' ),
			'deal'        => __( 'Сделка', 'realtrigel-core' ),
		);

		return $map[ $status ] ?? ucfirst( str_replace( '_', ' ', $status ) );
	}

	/**
	 * Get lead status label for partner dashboard.
	 *
	 * @param array<string, mixed> $lead Lead.
	 * @return string
	 */
	private function get_display_status_label( array $lead ): string {
		$bitrix_status_label = isset( $lead['bitrix_status_label'] ) ? trim( (string) $lead['bitrix_status_label'] ) : '';
		$bitrix_status_id    = isset( $lead['bitrix_status_id'] ) ? trim( (string) $lead['bitrix_status_id'] ) : '';

		if ( '' !== $bitrix_status_label ) {
			return $bitrix_status_label;
		}

		if ( '' !== $bitrix_status_id ) {
			return $bitrix_status_id;
		}

		return $this->format_status( (string) ( $lead['status'] ?? 'new' ) );
	}

	/**
	 * Get lead status class for partner dashboard.
	 *
	 * @param array<string, mixed> $lead Lead.
	 * @return string
	 */
	private function get_display_status_class( array $lead ): string {
		$bitrix_status_id = isset( $lead['bitrix_status_id'] ) ? trim( (string) $lead['bitrix_status_id'] ) : '';

		return '' !== $bitrix_status_id ? 'bitrix-' . strtolower( $bitrix_status_id ) : (string) ( $lead['status'] ?? 'new' );
	}
}
