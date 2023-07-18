<?php
/**
 * Plugin Name: WP CLI: Import ACF JSON
 * Plugin URI: https://github.com/crstauf/wpcli-acf-import/
 * Description: WP CLI command to import field groups, because enabling the UI only to import is silly.
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Version: 1.0.0
 * Author: Caleb Stauffer
 * Author URI: https://develop.calebstauffer.com
 * Update URI: false
 */

defined( 'WPINC' ) || exit();

if ( ! defined( 'WP_CLI' ) || ! constant( 'WP_CLI' ) ) {
	return;
}

/**
 * @see ACF_Admin_Tool_Import::submit()
 */
function wpcli_acf_import( array $args ) {
	if ( ! class_exists( 'ACF' ) ) {
		WP_CLI::error( 'ACF plugin is required' );
	}

	if ( ! function_exists( 'acf' ) ) {
		WP_CLI::error( 'ACF instance is inaccessible' );
	}

	$acf = acf();

	if ( ! version_compare( $acf->version, '6.1', '>=' ) ) {
		WP_CLI::error( 'ACF version must be at least 6.1' );
	}

	$file = array(
		'name'     => $args[0],
		'tmp_name' => $args[0],
	);

	// Check file type.
	if ( pathinfo( $file['name'], PATHINFO_EXTENSION ) !== 'json' ) {
		WP_CLI::error( 'Incorrect file type' );
	}

	// Read JSON.
	$json = file_get_contents( $file['tmp_name'] );
	$json = json_decode( $json, true );

	// Check if empty.
	if ( ! $json || ! is_array( $json ) ) {
		WP_CLI::error( 'Import file empty' );
	}

	// Ensure $json is an array of posts.
	if ( isset( $json['key'] ) ) {
		$json = array( $json );
	}

	// Remember imported post ids.
	$ids = array();

	// Loop over json.
	foreach ( $json as $to_import ) {
		// Search database for existing post.
		$post_type = acf_determine_internal_post_type( $to_import['key'] );
		$post      = acf_get_internal_post_type_post( $to_import['key'], $post_type );

		if ( $post ) {
			$to_import['ID'] = $post->ID;
		}

		// Import the post.
		$to_import = acf_import_internal_post_type( $to_import, $post_type );

		// Append message.
		$ids[] = $to_import['ID'];

		WP_CLI::debug( sprintf( 'Imported %s', $to_import['ID'] ) );
	}

	// Count number of imported posts.
	$total = count( $ids );

	// Generate text.
	$text = sprintf( _n( 'Imported 1 item', 'Imported %s items', $total, 'acf' ), $total );

	WP_CLI::success( $text );
}

WP_CLI::add_command( 'acf-import', 'wpcli_acf_import' );
