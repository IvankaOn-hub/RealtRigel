<?php
/**
 * Property downloads manager.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Catalog_Download_Manager {

	/**
	 * Query arg for bulk download.
	 */
	private const QUERY_ARG = 'rr_catalog_download_all';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'template_redirect', array( $this, 'handle_bulk_download' ) );
	}

	/**
	 * Handle ZIP download request.
	 *
	 * @return void
	 */
	public function handle_bulk_download(): void {
		if ( ! isset( $_GET[ self::QUERY_ARG ] ) ) {
			return;
		}

		$post_id = absint( wp_unslash( $_GET[ self::QUERY_ARG ] ) );

		if ( $post_id <= 0 || RR_Property_Post_Type::POST_TYPE !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'Invalid catalog object.', 'realtrigel-core' ), 400 );
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $this->get_nonce_action( $post_id ) ) ) {
			wp_die( esc_html__( 'Download link has expired.', 'realtrigel-core' ), 403 );
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( esc_html__( 'ZIP archives are not supported on this server.', 'realtrigel-core' ), 500 );
		}

		$downloads = $this->get_download_entries( $post_id );

		if ( empty( $downloads ) ) {
			wp_die( esc_html__( 'No files available for download.', 'realtrigel-core' ), 404 );
		}

		$temp_zip = wp_tempnam( 'rr-catalog-downloads-' . $post_id );

		if ( false === $temp_zip ) {
			wp_die( esc_html__( 'Failed to prepare archive.', 'realtrigel-core' ), 500 );
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $temp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			@unlink( $temp_zip );
			wp_die( esc_html__( 'Failed to create archive.', 'realtrigel-core' ), 500 );
		}

		$used_names = array();

		foreach ( $downloads as $download ) {
			$archive_name = $this->build_archive_name( $download['title'], $download['path'], $used_names );
			$zip->addFile( $download['path'], $archive_name );
		}

		$zip->close();

		$archive_slug = sanitize_title( (string) get_the_title( $post_id ) );
		$archive_name = '' !== $archive_slug ? $archive_slug : 'catalog-object';
		$archive_name .= '-files.zip';

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $archive_name . '"' );
		header( 'Content-Length: ' . (string) filesize( $temp_zip ) );
		header( 'X-Content-Type-Options: nosniff' );

		readfile( $temp_zip );
		@unlink( $temp_zip );
		exit;
	}

	/**
	 * Get nonce action for download request.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function get_nonce_action( int $post_id ): string {
		return 'rr_catalog_download_all_' . $post_id;
	}

	/**
	 * Collect normalized download entries.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{title:string,path:string}>
	 */
	private function get_download_entries( int $post_id ): array {
		if ( ! function_exists( 'get_field' ) ) {
			return array();
		}

		$rows = get_field( 'downloads', $post_id );

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return array();
		}

		$entries = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || empty( $row['file'] ) ) {
				continue;
			}

			$attachment_id = $this->resolve_attachment_id( $row['file'] );

			if ( $attachment_id <= 0 ) {
				continue;
			}

			$file_path = get_attached_file( $attachment_id );

			if ( ! is_string( $file_path ) || '' === $file_path || ! file_exists( $file_path ) ) {
				continue;
			}

			$title = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';

			$entries[] = array(
				'title' => $title,
				'path'  => $file_path,
			);
		}

		return $entries;
	}

	/**
	 * Resolve attachment ID from ACF file field value.
	 *
	 * @param mixed $file_value File field value.
	 * @return int
	 */
	private function resolve_attachment_id( $file_value ): int {
		if ( is_numeric( $file_value ) ) {
			return (int) $file_value;
		}

		if ( is_array( $file_value ) ) {
			if ( isset( $file_value['ID'] ) && is_numeric( $file_value['ID'] ) ) {
				return (int) $file_value['ID'];
			}

			if ( isset( $file_value['id'] ) && is_numeric( $file_value['id'] ) ) {
				return (int) $file_value['id'];
			}

			if ( isset( $file_value['url'] ) && is_string( $file_value['url'] ) ) {
				return (int) attachment_url_to_postid( $file_value['url'] );
			}
		}

		return 0;
	}

	/**
	 * Build unique filename inside archive.
	 *
	 * @param string               $title Requested title.
	 * @param string               $path Source file path.
	 * @param array<string, bool> &$used_names Used archive names.
	 * @return string
	 */
	private function build_archive_name( string $title, string $path, array &$used_names ): string {
		$path_info = pathinfo( $path );
		$extension = isset( $path_info['extension'] ) ? (string) $path_info['extension'] : '';
		$basename  = '' !== $title ? $title : ( $path_info['filename'] ?? 'file' );
		$basename  = sanitize_file_name( $basename );

		if ( '' === $basename ) {
			$basename = 'file';
		}

		$filename = $basename;

		if ( '' !== $extension && ! str_ends_with( strtolower( $filename ), '.' . strtolower( $extension ) ) ) {
			$filename .= '.' . $extension;
		}

		$unique_name = $filename;
		$counter     = 2;

		while ( isset( $used_names[ strtolower( $unique_name ) ] ) ) {
			$suffix_base = $basename . '-' . $counter;
			$unique_name = '' !== $extension ? $suffix_base . '.' . $extension : $suffix_base;
			++$counter;
		}

		$used_names[ strtolower( $unique_name ) ] = true;

		return $unique_name;
	}
}
