<?php
/**
 * Property contact form handler.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve partner code from the manual field or the referral cookie.
 *
 * @return string
 */
function realtrigel_resolve_contact_partner_code(): string {
	$manual_code = isset( $_POST['partner_code'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_code'] ) ) : '';

	if ( '' !== $manual_code ) {
		return $manual_code;
	}

	return isset( $_COOKIE['rr_referral_partner'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['rr_referral_partner'] ) ) : '';
}

/**
 * Resolve normalized language code for lead integrations.
 *
 * @return string
 */
function realtrigel_resolve_contact_language(): string {
	$language = '';

	if ( has_filter( 'wpml_current_language' ) ) {
		$language = (string) apply_filters( 'wpml_current_language', null );
	}

	if ( '' === $language && defined( 'ICL_LANGUAGE_CODE' ) ) {
		$language = (string) ICL_LANGUAGE_CODE;
	}

	if ( '' === $language ) {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		if ( preg_match( '~^/(ru|ua|uk|pl)(?:/|$)~i', $path, $matches ) ) {
			$language = (string) $matches[1];
		}
	}

	if ( '' === $language ) {
		$language = substr( get_locale(), 0, 2 );
	}

	$language = strtolower( trim( sanitize_key( $language ) ) );

	if ( 'uk' === $language ) {
		return 'ua';
	}

	$allowed = array( 'ru', 'ua', 'pl' );

	return in_array( $language, $allowed, true ) ? $language : '';
}

function realtrigel_handle_property_contact(): void {
	$redirect_url = wp_get_referer();

	if ( ! $redirect_url ) {
		$redirect_url = home_url( '/' );
	}

	$redirect_url = remove_query_arg(
		array( 'rtg_contact_status', 'rtg_contact_error' ),
		$redirect_url
	);

	if ( ! isset( $_POST['rtg_property_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rtg_property_contact_nonce'] ) ), 'rtg_property_contact' ) ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'rtg_contact_status' => 'error',
					'rtg_contact_error'  => 'nonce',
				),
				$redirect_url
			)
		);
		exit;
	}

	$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$post_title = $post_id ? get_the_title( $post_id ) : '';
	$permalink  = $post_id ? get_permalink( $post_id ) : $redirect_url;
	$name       = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
	$phone      = isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '';
	$email      = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
	$telegram   = isset( $_POST['contact_telegram'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_telegram'] ) ) : '';
	$contact_message = isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '';
	$language   = realtrigel_resolve_contact_language();
	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	$partner_code = realtrigel_resolve_contact_partner_code();

	if ( '' === $name || ( '' === $phone && '' === $email && '' === $telegram ) ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'rtg_contact_status' => 'error',
					'rtg_contact_error'  => 'required',
				),
				$redirect_url
			)
		);
		exit;
	}

	$admin_email = get_option( 'admin_email' );
	$subject     = sprintf(
		/* translators: %s: property title. */
		__( 'Новая заявка по объекту: %s', 'realtrigel' ),
		$post_title ? $post_title : __( 'без названия', 'realtrigel' )
	);

	$message_lines = array(
		__( 'Получена новая заявка с сайта.', 'realtrigel' ),
		'',
		sprintf( __( 'Объект: %s', 'realtrigel' ), $post_title ? $post_title : __( 'не указан', 'realtrigel' ) ),
		sprintf( __( 'Ссылка: %s', 'realtrigel' ), $post_id ? get_permalink( $post_id ) : $redirect_url ),
		'',
		sprintf( __( 'Имя: %s', 'realtrigel' ), $name ),
		sprintf( __( 'Телефон: %s', 'realtrigel' ), '' !== $phone ? $phone : '—' ),
		sprintf( __( 'Email: %s', 'realtrigel' ), '' !== $email ? $email : '—' ),
		sprintf( __( 'Telegram: %s', 'realtrigel' ), '' !== $telegram ? $telegram : '—' ),
	);

	$message_lines[] = sprintf( __( 'Message: %s', 'realtrigel' ), '' !== $contact_message ? $contact_message : '-' );
	$message_lines[] = sprintf( __( 'Partner code: %s', 'realtrigel' ), '' !== $partner_code ? $partner_code : '-' );
	$message_lines[] = sprintf( __( 'Language: %s', 'realtrigel' ), '' !== $language ? $language : '-' );
	$message_lines[] = sprintf( __( 'User Agent: %s', 'realtrigel' ), '' !== $user_agent ? $user_agent : '-' );

	$headers = array();

	if ( '' !== $email ) {
		$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
	}

	$lead_data = array(
		'post_id'          => $post_id,
		'post_title'       => $post_title,
		'permalink'        => $permalink,
		'object_url'       => $permalink,
		'name'             => $name,
		'phone'            => $phone,
		'email'            => $email,
		'telegram'         => $telegram,
		'message'          => $contact_message,
		'language'         => $language,
		'user_agent'       => $user_agent,
		'contact_name'     => $name,
		'contact_phone'    => $phone,
		'contact_email'    => $email,
		'contact_telegram' => $telegram,
		'contact_message'  => $contact_message,
		'partner_code'     => $partner_code,
		'source'           => 'portal',
		'redirect_url'     => $redirect_url,
	);

	do_action( 'realtrigel_property_contact_validated', $lead_data );

	$sent = wp_mail( $admin_email, $subject, implode( "\n", $message_lines ), $headers );

	if ( $sent ) {
		do_action( 'rr_referral_capture_property_contact', $lead_data );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'rtg_contact_status' => $sent ? 'success' : 'error',
				'rtg_contact_error'  => $sent ? null : 'mail',
			),
			$redirect_url
		)
	);
	exit;
}

add_action( 'admin_post_realtrigel_property_contact', 'realtrigel_handle_property_contact' );
add_action( 'admin_post_nopriv_realtrigel_property_contact', 'realtrigel_handle_property_contact' );
