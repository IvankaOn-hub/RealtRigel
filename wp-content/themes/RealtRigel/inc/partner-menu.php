<?php
/**
 * Conditional partner menu visibility.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter menu items visibility based on current visitor state.
 *
 * Supported menu item CSS classes:
 * - rtg-menu-guest
 * - rtg-menu-logged-in
 * - rtg-menu-partner
 * - rtg-menu-non-partner
 *
 * @param WP_Post[] $items Menu item objects.
 * @param stdClass  $args  Menu arguments.
 * @return WP_Post[]
 */
function realtrigel_filter_partner_menu_items( array $items, stdClass $args ): array {
	if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $items;
	}

	$is_logged_in = is_user_logged_in();
	$is_partner   = realtrigel_is_partner_user();

	return array_values(
		array_filter(
			$items,
			static function ( WP_Post $item ) use ( $is_logged_in, $is_partner ): bool {
				$classes = is_array( $item->classes ) ? $item->classes : array();

				if ( in_array( 'rtg-menu-guest', $classes, true ) && $is_logged_in ) {
					return false;
				}

				if ( in_array( 'rtg-menu-logged-in', $classes, true ) && ! $is_logged_in ) {
					return false;
				}

				if ( in_array( 'rtg-menu-partner', $classes, true ) && ! $is_partner ) {
					return false;
				}

				if ( in_array( 'rtg-menu-non-partner', $classes, true ) && $is_partner ) {
					return false;
				}

				return true;
			}
		)
	);
}
add_filter( 'wp_nav_menu_objects', 'realtrigel_filter_partner_menu_items', 10, 2 );

/**
 * Determine whether current user is a partner.
 *
 * @return bool
 */
function realtrigel_is_partner_user(): bool {
	$user = wp_get_current_user();

	if ( ! $user instanceof WP_User || 0 === (int) $user->ID ) {
		return false;
	}

	return in_array( 'partner', (array) $user->roles, true );
}
