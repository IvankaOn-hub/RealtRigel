<?php
/**
 * Referral admin pages coordinator.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Admin_Pages {

	/**
	 * Partners page slug.
	 */
	public const PARTNERS_SLUG = 'rr-referral-partners';

	/**
	 * Leads page slug.
	 */
	public const LEADS_SLUG = 'rr-referral-leads';

	/**
	 * Partners page controller.
	 *
	 * @var RR_Referral_Partners_Page
	 */
	private RR_Referral_Partners_Page $partners_page;

	/**
	 * Leads page controller.
	 *
	 * @var RR_Referral_Leads_Page
	 */
	private RR_Referral_Leads_Page $leads_page;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Partners_Page $partners_page Partners page.
	 * @param RR_Referral_Leads_Page    $leads_page Leads page.
	 */
	public function __construct( RR_Referral_Partners_Page $partners_page, RR_Referral_Leads_Page $leads_page ) {
		$this->partners_page = $partners_page;
		$this->leads_page    = $leads_page;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this->leads_page, 'maybe_export' ) );
		add_action( 'admin_init', array( $this->leads_page, 'maybe_delete' ) );
	}

	/**
	 * Register top-level referral menu and subpages.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Referral', 'realtrigel-core' ),
			__( 'Referral', 'realtrigel-core' ),
			'manage_options',
			self::PARTNERS_SLUG,
			array( $this->partners_page, 'render_page' ),
			'dashicons-share',
			58
		);

		add_submenu_page(
			self::PARTNERS_SLUG,
			__( 'Partners', 'realtrigel-core' ),
			__( 'Partners', 'realtrigel-core' ),
			'manage_options',
			self::PARTNERS_SLUG,
			array( $this->partners_page, 'render_page' )
		);

		add_submenu_page(
			self::PARTNERS_SLUG,
			__( 'Leads', 'realtrigel-core' ),
			__( 'Leads', 'realtrigel-core' ),
			'manage_options',
			self::LEADS_SLUG,
			array( $this->leads_page, 'render_page' )
		);
	}
}
