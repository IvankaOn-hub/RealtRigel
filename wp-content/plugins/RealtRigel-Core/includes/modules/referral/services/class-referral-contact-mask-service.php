<?php
/**
 * Referral contact masking service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Contact_Mask_Service {

	/**
	 * Mask phone number.
	 *
	 * @param string $phone Raw phone.
	 * @return string
	 */
	public function mask_phone( string $phone ): string {
		$phone = trim( $phone );

		if ( '' === $phone ) {
			return '';
		}

		$digits = preg_replace( '/\D+/', '', $phone ) ?? '';

		if ( strlen( $digits ) <= 4 ) {
			return str_repeat( '*', strlen( $digits ) );
		}

		$last_four = substr( $digits, -4 );

		return '+** *** *** ' . $last_four;
	}

	/**
	 * Mask email address.
	 *
	 * @param string $email Raw email.
	 * @return string
	 */
	public function mask_email( string $email ): string {
		$email = trim( $email );

		if ( '' === $email || false === strpos( $email, '@' ) ) {
			return '';
		}

		list( $name, $domain ) = explode( '@', $email, 2 );
		$visible = mb_substr( $name, 0, min( 2, mb_strlen( $name ) ), 'UTF-8' );

		return $visible . '***@' . $domain;
	}

	/**
	 * Mask telegram handle.
	 *
	 * @param string $telegram Raw telegram handle.
	 * @return string
	 */
	public function mask_telegram( string $telegram ): string {
		$telegram = trim( ltrim( $telegram, '@' ) );

		if ( '' === $telegram ) {
			return '';
		}

		$visible = mb_substr( $telegram, 0, min( 3, mb_strlen( $telegram ) ), 'UTF-8' );

		return '@' . $visible . '***';
	}

	/**
	 * Pick best masked contact for display.
	 *
	 * @param array<string, mixed> $lead Lead row.
	 * @return string
	 */
	public function get_preferred_masked_contact( array $lead ): string {
		$phone = isset( $lead['contact_phone'] ) ? $this->mask_phone( (string) $lead['contact_phone'] ) : '';

		if ( '' !== $phone ) {
			return $phone;
		}

		$email = isset( $lead['contact_email'] ) ? $this->mask_email( (string) $lead['contact_email'] ) : '';

		if ( '' !== $email ) {
			return $email;
		}

		$telegram = isset( $lead['contact_telegram'] ) ? $this->mask_telegram( (string) $lead['contact_telegram'] ) : '';

		return '' !== $telegram ? $telegram : '—';
	}
}
