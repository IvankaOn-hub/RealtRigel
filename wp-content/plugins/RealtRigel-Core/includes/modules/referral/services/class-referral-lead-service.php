<?php
/**
 * Referral lead service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Lead_Service {

	/**
	 * Lead repository.
	 *
	 * @var RR_Referral_Lead_Repository
	 */
	private RR_Referral_Lead_Repository $lead_repository;

	/**
	 * Lead status history repository.
	 *
	 * @var RR_Referral_Lead_Status_History_Repository
	 */
	private RR_Referral_Lead_Status_History_Repository $lead_status_history_repository;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Lead_Repository                $lead_repository Lead repository.
	 * @param RR_Referral_Lead_Status_History_Repository $lead_status_history_repository History repository.
	 */
	public function __construct( RR_Referral_Lead_Repository $lead_repository, RR_Referral_Lead_Status_History_Repository $lead_status_history_repository ) {
		$this->lead_repository                = $lead_repository;
		$this->lead_status_history_repository = $lead_status_history_repository;
	}

	/**
	 * Create lead and initial history entry.
	 *
	 * @param array<string, mixed> $data Lead data.
	 * @return int
	 */
	public function create_lead( array $data ): int {
		$lead_id = $this->lead_repository->create( $data );

		if ( $lead_id > 0 ) {
			$this->lead_status_history_repository->create(
				array(
					'lead_id'    => $lead_id,
					'old_status' => '',
					'new_status' => isset( $data['status'] ) ? (string) $data['status'] : 'new',
					'source'     => 'site',
				)
			);
		}

		return $lead_id;
	}

	/**
	 * Find recent duplicate lead candidate.
	 *
	 * @param int    $partner_id Partner id.
	 * @param string $source_type Source type.
	 * @param string $contact_phone Contact phone.
	 * @param string $contact_email Contact email.
	 * @param string $contact_telegram Contact telegram.
	 * @return array<string, mixed>|null
	 */
	public function find_recent_duplicate( int $partner_id, string $source_type, string $contact_phone, string $contact_email, string $contact_telegram ): ?array {
		return $this->lead_repository->find_recent_duplicate(
			$partner_id,
			$source_type,
			$contact_phone,
			$contact_email,
			$contact_telegram
		);
	}
}
