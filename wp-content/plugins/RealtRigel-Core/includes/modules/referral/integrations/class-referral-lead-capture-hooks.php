<?php
/**
 * Referral lead capture integration hooks.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Lead_Capture_Hooks {

	/**
	 * Partner repository.
	 *
	 * @var RR_Referral_Partner_Repository
	 */
	private RR_Referral_Partner_Repository $partner_repository;

	/**
	 * Visit repository.
	 *
	 * @var RR_Referral_Visit_Repository
	 */
	private RR_Referral_Visit_Repository $visit_repository;

	/**
	 * Lead service.
	 *
	 * @var RR_Referral_Lead_Service
	 */
	private RR_Referral_Lead_Service $lead_service;

	/**
	 * Tracking service.
	 *
	 * @var RR_Referral_Tracking_Service
	 */
	private RR_Referral_Tracking_Service $tracking_service;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Partner_Repository $partner_repository Partner repository.
	 * @param RR_Referral_Visit_Repository   $visit_repository Visit repository.
	 * @param RR_Referral_Lead_Service       $lead_service Lead service.
	 * @param RR_Referral_Tracking_Service   $tracking_service Tracking service.
	 */
	public function __construct( RR_Referral_Partner_Repository $partner_repository, RR_Referral_Visit_Repository $visit_repository, RR_Referral_Lead_Service $lead_service, RR_Referral_Tracking_Service $tracking_service ) {
		$this->partner_repository = $partner_repository;
		$this->visit_repository   = $visit_repository;
		$this->lead_service       = $lead_service;
		$this->tracking_service   = $tracking_service;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'realtrigel_property_contact_validated', array( $this, 'capture_property_contact_lead' ), 10, 1 );
	}

	/**
	 * Create referral lead from the property contact form.
	 *
	 * @param array<string, mixed> $payload Form payload.
	 * @return void
	 */
	public function capture_property_contact_lead( array $payload ): void {
		$partner_code = isset( $payload['partner_code'] ) ? sanitize_text_field( (string) $payload['partner_code'] ) : '';

		if ( '' === $partner_code ) {
			$partner_code = $this->tracking_service->get_current_partner_code();
		}

		$partner_id = 0;
		$visit_id   = 0;

		if ( '' !== $partner_code ) {
			$partner = $this->partner_repository->get_by_partner_code( $partner_code );

			if ( is_array( $partner ) && ! empty( $partner['id'] ) ) {
				$partner_id = (int) $partner['id'];
				$visit_id   = $this->resolve_visit_id_for_current_session( $partner_id );
			}
		}

		$contact_name = isset( $payload['contact_name'] ) ? (string) $payload['contact_name'] : '';
		$contact_phone = isset( $payload['contact_phone'] ) ? (string) $payload['contact_phone'] : '';
		$contact_email = isset( $payload['contact_email'] ) ? (string) $payload['contact_email'] : '';
		$contact_telegram = isset( $payload['contact_telegram'] ) ? (string) $payload['contact_telegram'] : '';
		$contact_message = isset( $payload['contact_message'] ) ? (string) $payload['contact_message'] : '';
		$post_id = isset( $payload['post_id'] ) ? (int) $payload['post_id'] : 0;

		$duplicate = $this->lead_service->find_recent_duplicate(
			$partner_id,
			'form',
			$contact_phone,
			$contact_email,
			$contact_telegram
		);

		if ( is_array( $duplicate ) ) {
			return;
		}

		$lead_data = array(
			'partner_id'       => $partner_id,
			'visit_id'         => $visit_id > 0 ? $visit_id : null,
			'source_type'      => 'form',
			'contact_name'     => $contact_name,
			'contact_phone'    => $contact_phone,
			'contact_email'    => $contact_email,
			'contact_telegram' => $contact_telegram,
			'contact_message'  => $contact_message,
			'catalog_post_id'  => $post_id > 0 ? $post_id : null,
			'object_title'     => isset( $payload['post_title'] ) ? (string) $payload['post_title'] : '',
			'object_url'       => isset( $payload['object_url'] ) ? (string) $payload['object_url'] : '',
			'redirect_url'     => isset( $payload['redirect_url'] ) ? (string) $payload['redirect_url'] : '',
			'language'         => isset( $payload['language'] ) ? (string) $payload['language'] : '',
			'user_agent'       => isset( $payload['user_agent'] ) ? (string) $payload['user_agent'] : '',
			'status'           => 'new',
		);

		$lead_id = $this->lead_service->create_lead( $lead_data );

		if ( $lead_id > 0 ) {
			do_action( 'rr_referral_lead_created', $lead_id, $lead_data, $payload );
		}
	}

	/**
	 * Resolve current visit id for the same partner.
	 *
	 * @param int $partner_id Partner id.
	 * @return int
	 */
	private function resolve_visit_id_for_current_session( int $partner_id ): int {
		$session_key = $this->tracking_service->get_current_session_key();

		if ( '' === $session_key ) {
			return 0;
		}

		$visit = $this->visit_repository->get_latest_by_session_key( $session_key );

		if ( ! is_array( $visit ) || (int) $visit['partner_id'] !== $partner_id ) {
			return 0;
		}

		return (int) $visit['id'];
	}
}
