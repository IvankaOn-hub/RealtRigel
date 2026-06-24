<?php
/**
 * Referral module bootstrap.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Module {

	/**
	 * Settings page controller.
	 *
	 * @var RR_Referral_Settings_Page
	 */
	private RR_Referral_Settings_Page $settings_page;

	/**
	 * Referral admin pages coordinator.
	 *
	 * @var RR_Referral_Admin_Pages
	 */
	private RR_Referral_Admin_Pages $admin_pages;

	/**
	 * Frontend partner registration shortcode.
	 *
	 * @var RR_Referral_Registration_Shortcode
	 */
	private RR_Referral_Registration_Shortcode $registration_shortcode;

	/**
	 * Frontend partner dashboard shortcode.
	 *
	 * @var RR_Referral_Dashboard_Shortcode
	 */
	private RR_Referral_Dashboard_Shortcode $dashboard_shortcode;

	/**
	 * Frontend referral rules shortcode.
	 *
	 * @var RR_Referral_Rules_Shortcode
	 */
	private RR_Referral_Rules_Shortcode $rules_shortcode;

	/**
	 * Database installer.
	 *
	 * @var RR_Referral_Installer
	 */
	private RR_Referral_Installer $installer;

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
	 * Settings service.
	 *
	 * @var RR_Referral_Settings_Service
	 */
	private RR_Referral_Settings_Service $settings_service;

	/**
	 * Attribution service.
	 *
	 * @var RR_Referral_Attribution_Service
	 */
	private RR_Referral_Attribution_Service $attribution_service;

	/**
	 * Lead service.
	 *
	 * @var RR_Referral_Lead_Service
	 */
	private RR_Referral_Lead_Service $lead_service;

	/**
	 * Bitrix status sync service.
	 *
	 * @var RR_Referral_Bitrix_Status_Sync_Service
	 */
	private RR_Referral_Bitrix_Status_Sync_Service $bitrix_status_sync_service;

	/**
	 * Partner service.
	 *
	 * @var RR_Referral_Partner_Service
	 */
	private RR_Referral_Partner_Service $partner_service;

	/**
	 * Role service.
	 *
	 * @var RR_Referral_Role_Service
	 */
	private RR_Referral_Role_Service $role_service;

	/**
	 * Contact mask service.
	 *
	 * @var RR_Referral_Contact_Mask_Service
	 */
	private RR_Referral_Contact_Mask_Service $contact_mask_service;

	/**
	 * QR code service.
	 *
	 * @var RR_Referral_Qr_Service
	 */
	private RR_Referral_Qr_Service $qr_service;

	/**
	 * Link service.
	 *
	 * @var RR_Referral_Link_Service
	 */
	private RR_Referral_Link_Service $link_service;

	/**
	 * Tracking service.
	 *
	 * @var RR_Referral_Tracking_Service
	 */
	private RR_Referral_Tracking_Service $tracking_service;

	/**
	 * Lead capture integration hooks.
	 *
	 * @var RR_Referral_Lead_Capture_Hooks
	 */
	private RR_Referral_Lead_Capture_Hooks $lead_capture_hooks;

	/**
	 * Google Sheets export hooks.
	 *
	 * @var RR_Referral_Google_Sheets_Export_Hooks
	 */
	private RR_Referral_Google_Sheets_Export_Hooks $google_sheets_export_hooks;

	/**
	 * Telegram notification hooks.
	 *
	 * @var RR_Referral_Telegram_Notification_Hooks
	 */
	private RR_Referral_Telegram_Notification_Hooks $telegram_notification_hooks;

	/**
	 * Bitrix24 lead export hooks.
	 *
	 * @var RR_Referral_Bitrix_Lead_Export_Hooks
	 */
	private RR_Referral_Bitrix_Lead_Export_Hooks $bitrix_lead_export_hooks;

	/**
	 * Init module.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_dependencies();

		$this->installer                       = new RR_Referral_Installer();
		$this->partner_repository              = new RR_Referral_Partner_Repository();
		$this->visit_repository                = new RR_Referral_Visit_Repository();
		$this->lead_repository                 = new RR_Referral_Lead_Repository();
		$this->lead_status_history_repository  = new RR_Referral_Lead_Status_History_Repository();
		$this->settings_service                = new RR_Referral_Settings_Service();
		$this->role_service                    = new RR_Referral_Role_Service();
		$this->settings_page                   = new RR_Referral_Settings_Page( $this->settings_service );
		$this->link_service                    = new RR_Referral_Link_Service();
		$this->partner_service                 = new RR_Referral_Partner_Service( $this->partner_repository, $this->link_service );
		$this->contact_mask_service            = new RR_Referral_Contact_Mask_Service();
		$this->admin_pages                     = new RR_Referral_Admin_Pages(
			new RR_Referral_Partners_Page( $this->partner_repository, $this->partner_service ),
			new RR_Referral_Leads_Page( $this->lead_repository, $this->partner_repository, $this->lead_status_history_repository, $this->contact_mask_service )
		);
		$this->qr_service                      = new RR_Referral_Qr_Service();
		$this->attribution_service             = new RR_Referral_Attribution_Service( $this->visit_repository, $this->settings_service );
		$this->lead_service                    = new RR_Referral_Lead_Service( $this->lead_repository, $this->lead_status_history_repository );
		$this->bitrix_status_sync_service      = new RR_Referral_Bitrix_Status_Sync_Service( $this->settings_service, $this->lead_repository );
		$this->tracking_service                = new RR_Referral_Tracking_Service( $this->partner_repository, $this->visit_repository, $this->settings_service );
		$this->lead_capture_hooks              = new RR_Referral_Lead_Capture_Hooks( $this->partner_repository, $this->visit_repository, $this->lead_service, $this->tracking_service );
		$this->google_sheets_export_hooks      = new RR_Referral_Google_Sheets_Export_Hooks( $this->settings_service );
		$this->telegram_notification_hooks     = new RR_Referral_Telegram_Notification_Hooks( $this->settings_service );
		$this->bitrix_lead_export_hooks        = new RR_Referral_Bitrix_Lead_Export_Hooks( $this->settings_service, $this->lead_repository );
		$this->registration_shortcode          = new RR_Referral_Registration_Shortcode( $this->partner_service );
		$this->dashboard_shortcode             = new RR_Referral_Dashboard_Shortcode( $this->partner_service, $this->lead_repository, $this->contact_mask_service, $this->bitrix_status_sync_service, $this->qr_service );
		$this->rules_shortcode                 = new RR_Referral_Rules_Shortcode();

		$this->installer->init();
		$this->role_service->init();
		$this->admin_pages->init();
		$this->settings_page->init();
		$this->tracking_service->init();
		$this->lead_capture_hooks->init();
		$this->google_sheets_export_hooks->init();
		$this->telegram_notification_hooks->init();
		$this->bitrix_lead_export_hooks->init();
		$this->registration_shortcode->init();
		$this->dashboard_shortcode->init();
		$this->rules_shortcode->init();
	}

	/**
	 * Load module files.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once __DIR__ . '/helpers.php';
		require_once __DIR__ . '/database/class-referral-schema.php';
		require_once __DIR__ . '/database/class-referral-installer.php';
		require_once __DIR__ . '/admin/class-referral-admin-pages.php';
		require_once __DIR__ . '/admin/class-referral-partners-page.php';
		require_once __DIR__ . '/admin/class-referral-leads-page.php';
		require_once __DIR__ . '/admin/class-referral-settings-page.php';
		require_once __DIR__ . '/frontend/class-referral-dashboard-shortcode.php';
		require_once __DIR__ . '/frontend/class-referral-registration-shortcode.php';
		require_once __DIR__ . '/frontend/class-referral-rules-shortcode.php';
		require_once __DIR__ . '/repositories/class-referral-partner-repository.php';
		require_once __DIR__ . '/repositories/class-referral-visit-repository.php';
		require_once __DIR__ . '/repositories/class-referral-lead-repository.php';
		require_once __DIR__ . '/repositories/class-referral-lead-status-history-repository.php';
		require_once __DIR__ . '/services/class-referral-role-service.php';
		require_once __DIR__ . '/services/class-referral-settings-service.php';
		require_once __DIR__ . '/services/class-referral-link-service.php';
		require_once __DIR__ . '/services/class-referral-contact-mask-service.php';
		require_once __DIR__ . '/services/class-referral-partner-service.php';
		require_once __DIR__ . '/services/class-referral-qr-service.php';
		require_once __DIR__ . '/services/class-referral-attribution-service.php';
		require_once __DIR__ . '/services/class-referral-lead-service.php';
		require_once __DIR__ . '/services/class-referral-bitrix-status-sync-service.php';
		require_once __DIR__ . '/services/class-referral-tracking-service.php';
		require_once __DIR__ . '/integrations/class-referral-lead-capture-hooks.php';
		require_once __DIR__ . '/integrations/class-referral-google-sheets-export-hooks.php';
		require_once __DIR__ . '/integrations/class-referral-telegram-notification-hooks.php';
		require_once __DIR__ . '/integrations/class-referral-bitrix-lead-export-hooks.php';
	}
}
