<?php
/**
 * Theme setup and supports.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function realtrigel_theme_setup(): void {
	load_theme_textdomain( 'realtrigel', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 220,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'realtrigel' ),
			'footer'  => __( 'Footer Menu', 'realtrigel' ),
		)
	);
}
add_action( 'after_setup_theme', 'realtrigel_theme_setup' );

function realtrigel_content_width(): void {
	$GLOBALS['content_width'] = apply_filters( 'realtrigel_content_width', 1200 );
}
add_action( 'after_setup_theme', 'realtrigel_content_width', 0 );
