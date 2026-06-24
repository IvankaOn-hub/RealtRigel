<?php
/**
 * Referral attribution service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Attribution_Service {

	/**
	 * Visit repository.
	 *
	 * @var RR_Referral_Visit_Repository
	 */
	private RR_Referral_Visit_Repository $visit_repository;

	/**
	 * Settings service.
	 *
	 * @var RR_Referral_Settings_Service
	 */
	private RR_Referral_Settings_Service $settings_service;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Visit_Repository  $visit_repository Visit repository.
	 * @param RR_Referral_Settings_Service  $settings_service Settings service.
	 */
	public function __construct( RR_Referral_Visit_Repository $visit_repository, RR_Referral_Settings_Service $settings_service ) {
		$this->visit_repository  = $visit_repository;
		$this->settings_service = $settings_service;
	}

	/**
	 * Get current attribution config snapshot.
	 *
	 * @return array<string, mixed>
	 */
	public function get_lifetime_config(): array {
		return array(
			'mode' => $this->settings_service->get_attribution_lifetime_mode(),
			'days' => $this->settings_service->get_attribution_lifetime_days(),
		);
	}
}
