<?php
/**
 * Frontend referral rules shortcode.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Rules_Shortcode {

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	public function init(): void {
		add_shortcode( 'rr_partner_rules', array( $this, 'render' ) );
	}

	/**
	 * Render rules page content.
	 *
	 * @return string
	 */
	public function render(): string {
		wp_enqueue_style(
			'rr-referral-rules',
			plugins_url( 'assets/css/referral-rules.css', RR_CORE_PLUGIN_FILE ),
			array(),
			filemtime( plugin_dir_path( RR_CORE_PLUGIN_FILE ) . 'assets/css/referral-rules.css' )
		);

		ob_start();
		?>
		<div class="rr-referral-rules">
			<div class="rr-referral-rules__hero">
				<p class="rr-referral-rules__eyebrow"><?php esc_html_e( 'Referral Program', 'realtrigel-core' ); ?></p>
				<h2><?php esc_html_e( 'Program Rules', 'realtrigel-core' ); ?></h2>
				<p><?php esc_html_e( 'This page explains how attribution works, what counts as a lead, and which statuses you will see in your partner cabinet.', 'realtrigel-core' ); ?></p>
			</div>

			<div class="rr-referral-rules__grid">
				<section class="rr-referral-rules__card">
					<h3><?php esc_html_e( 'How attribution works', 'realtrigel-core' ); ?></h3>
					<p><?php esc_html_e( 'The program uses the first-click model. The partner who first brought the visitor is credited with the lead.', 'realtrigel-core' ); ?></p>
					<p><?php esc_html_e( 'Attribution duration is managed by the business in the admin settings.', 'realtrigel-core' ); ?></p>
				</section>

				<section class="rr-referral-rules__card">
					<h3><?php esc_html_e( 'What counts as a lead', 'realtrigel-core' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Property inquiry form', 'realtrigel-core' ); ?></li>
						<li><?php esc_html_e( 'Phone call', 'realtrigel-core' ); ?></li>
						<li><?php esc_html_e( 'WhatsApp request', 'realtrigel-core' ); ?></li>
						<li><?php esc_html_e( 'Telegram request', 'realtrigel-core' ); ?></li>
						<li><?php esc_html_e( 'Callback request', 'realtrigel-core' ); ?></li>
					</ul>
				</section>

				<section class="rr-referral-rules__card">
					<h3><?php esc_html_e( 'Statuses in the cabinet', 'realtrigel-core' ); ?></h3>
					<ul>
						<li><strong><?php esc_html_e( 'New', 'realtrigel-core' ); ?></strong> <?php esc_html_e( 'A lead has just been created.', 'realtrigel-core' ); ?></li>
						<li><strong><?php esc_html_e( 'In progress', 'realtrigel-core' ); ?></strong> <?php esc_html_e( 'The lead is being processed.', 'realtrigel-core' ); ?></li>
						<li><strong><?php esc_html_e( 'Qualified', 'realtrigel-core' ); ?></strong> <?php esc_html_e( 'The lead has passed validation.', 'realtrigel-core' ); ?></li>
						<li><strong><?php esc_html_e( 'Rejected', 'realtrigel-core' ); ?></strong> <?php esc_html_e( 'The lead was rejected or marked invalid.', 'realtrigel-core' ); ?></li>
						<li><strong><?php esc_html_e( 'Deal', 'realtrigel-core' ); ?></strong> <?php esc_html_e( 'The lead has reached a successful final result.', 'realtrigel-core' ); ?></li>
					</ul>
				</section>

				<section class="rr-referral-rules__card">
					<h3><?php esc_html_e( 'Privacy and display', 'realtrigel-core' ); ?></h3>
					<p><?php esc_html_e( 'Only masked contact details are shown in the partner cabinet. Full customer contact details are not disclosed to partners.', 'realtrigel-core' ); ?></p>
				</section>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
