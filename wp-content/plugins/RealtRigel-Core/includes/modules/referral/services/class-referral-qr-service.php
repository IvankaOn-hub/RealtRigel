<?php
/**
 * Referral QR code service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Qr_Service {

	/**
	 * Build QR image URL for referral link.
	 *
	 * @param string $url Referral URL.
	 * @param int    $size Image size in px.
	 * @return string
	 */
	public function build_qr_image_url( string $url, int $size = 220 ): string {
		$size = max( 120, min( 600, $size ) );

		if ( '' === trim( $url ) || ! class_exists( '\BaconQrCode\Writer' ) ) {
			return '';
		}

		try {
			$renderer = new \BaconQrCode\Renderer\ImageRenderer(
				new \BaconQrCode\Renderer\RendererStyle\RendererStyle( $size, 2 ),
				new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
			);

			$writer = new \BaconQrCode\Writer( $renderer );
			$svg    = $writer->writeString( $url );

			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		} catch ( \Throwable $exception ) {
			return '';
		}
	}
}
