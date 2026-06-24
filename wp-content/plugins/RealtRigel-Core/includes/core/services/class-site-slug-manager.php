<?php
/**
 * Global site slug transliteration service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Site_Slug_Manager {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'sanitize_title', array( $this, 'filter_sanitize_title' ), 9, 3 );
	}

	/**
	 * Ensure generated slugs are Latin ASCII.
	 *
	 * @param string $title     Sanitized title.
	 * @param string $raw_title Raw source title.
	 * @param string $context   Sanitization context.
	 *
	 * @return string
	 */
	public function filter_sanitize_title( string $title, string $raw_title = '', string $context = 'save' ): string {
		if ( '' === trim( $raw_title ) ) {
			return $title;
		}

		$normalized_source = $this->normalize_source_value( $raw_title );
		$ascii_slug        = $this->generate_ascii_slug( $normalized_source );

		if ( '' === $ascii_slug ) {
			return $title;
		}

		if ( $this->source_needs_ascii_normalization( $raw_title, $title ) ) {
			return $ascii_slug;
		}

		return $title;
	}

	/**
	 * Normalize incoming source value before transliteration.
	 *
	 * @param string $value Raw source.
	 * @return string
	 */
	private function normalize_source_value( string $value ): string {
		$value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = trim( preg_replace( '/\s+/u', ' ', $value ) ?? '' );

		if ( '' !== $value && false !== strpos( $value, '%' ) ) {
			$decoded = rawurldecode( $value );

			if ( is_string( $decoded ) && '' !== trim( $decoded ) ) {
				$value = $decoded;
			}
		}

		return $value;
	}

	/**
	 * Determine if current source/title requires ASCII normalization.
	 *
	 * @param string $raw_title Raw source title.
	 * @param string $title     Current sanitized title.
	 * @return bool
	 */
	private function source_needs_ascii_normalization( string $raw_title, string $title ): bool {
		if ( false !== strpos( $raw_title, '%' ) || false !== strpos( $title, '%' ) ) {
			return true;
		}

		if ( 1 === preg_match( '/[^\x20-\x7E]/u', $raw_title ) ) {
			return true;
		}

		return 1 !== preg_match( '/^[a-z0-9-]+$/', $title );
	}

	/**
	 * Generate ASCII slug from any source string.
	 *
	 * @param string $value Source value.
	 * @return string
	 */
	private function generate_ascii_slug( string $value ): string {
		$value = remove_accents( $value );

		if ( class_exists( 'Transliterator' ) ) {
			$transliterated = transliterator_transliterate( 'Any-Latin; Latin-ASCII; Lower()', $value );

			if ( is_string( $transliterated ) && '' !== trim( $transliterated ) ) {
				$value = $transliterated;
			}
		} elseif ( function_exists( 'iconv' ) ) {
			$transliterated = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $value );

			if ( false !== $transliterated && '' !== trim( $transliterated ) ) {
				$value = (string) $transliterated;
			}
		}

		$value = $this->fallback_transliterate( $value );
		$value = strtolower( $value );

		// Avoid recursion: this class is itself hooked into `sanitize_title`.
		return (string) sanitize_title_with_dashes( $value, '', 'save' );
	}

	/**
	 * Fallback transliteration for Cyrillic and Polish characters.
	 *
	 * @param string $value Source value.
	 * @return string
	 */
	private function fallback_transliterate( string $value ): string {
		$map = array(
			1040 => 'A', 1072 => 'a', 1041 => 'B', 1073 => 'b', 1042 => 'V', 1074 => 'v',
			1043 => 'G', 1075 => 'g', 1044 => 'D', 1076 => 'd', 1045 => 'E', 1077 => 'e',
			1025 => 'Yo', 1105 => 'yo', 1046 => 'Zh', 1078 => 'zh', 1047 => 'Z', 1079 => 'z',
			1048 => 'I', 1080 => 'i', 1049 => 'Y', 1081 => 'y', 1050 => 'K', 1082 => 'k',
			1051 => 'L', 1083 => 'l', 1052 => 'M', 1084 => 'm', 1053 => 'N', 1085 => 'n',
			1054 => 'O', 1086 => 'o', 1055 => 'P', 1087 => 'p', 1056 => 'R', 1088 => 'r',
			1057 => 'S', 1089 => 's', 1058 => 'T', 1090 => 't', 1059 => 'U', 1091 => 'u',
			1060 => 'F', 1092 => 'f', 1061 => 'Kh', 1093 => 'kh', 1062 => 'Ts', 1094 => 'ts',
			1063 => 'Ch', 1095 => 'ch', 1064 => 'Sh', 1096 => 'sh', 1065 => 'Shch', 1097 => 'shch',
			1066 => '', 1098 => '', 1067 => 'Y', 1099 => 'y', 1068 => '', 1100 => '',
			1069 => 'E', 1101 => 'e', 1070 => 'Yu', 1102 => 'yu', 1071 => 'Ya', 1103 => 'ya',
			1028 => 'Ye', 1108 => 'ye', 1030 => 'I', 1110 => 'i', 1031 => 'Yi', 1111 => 'yi',
			1029 => 'G', 1109 => 'g', 1168 => 'G', 1169 => 'g',
			321 => 'L', 322 => 'l', 211 => 'O', 243 => 'o', 260 => 'A', 261 => 'a',
			280 => 'E', 281 => 'e', 262 => 'C', 263 => 'c', 323 => 'N', 324 => 'n',
			346 => 'S', 347 => 's', 377 => 'Z', 378 => 'z', 379 => 'Z', 380 => 'z'
		);

		$result = '';
		$length = mb_strlen( $value, 'UTF-8' );

		for ( $index = 0; $index < $length; $index++ ) {
			$character = mb_substr( $value, $index, 1, 'UTF-8' );
			$codepoint = mb_ord( $character, 'UTF-8' );

			if ( isset( $map[ $codepoint ] ) ) {
				$result .= $map[ $codepoint ];
				continue;
			}

			$result .= $character;
		}

		return $result;
	}
}
