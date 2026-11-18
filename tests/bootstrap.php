<?php
/**
 * PHPUnit bootstrap.
 *
 * A library, not a plugin: there is no WordPress to load. What is stubbed
 * here is only what the registration path touches, and register_post_type()
 * and register_taxonomy() record their arguments rather than doing anything —
 * what this library does is decide those arguments, so what it passed is the
 * whole of what there is to check.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! function_exists( 'register_post_type' ) ) {
	function register_post_type( $post_type, $args = [] ) {
		$GLOBALS['ct_post_types'][ $post_type ] = $args;

		return (object) [ 'name' => $post_type ];
	}
}

if ( ! function_exists( 'register_taxonomy' ) ) {
	function register_taxonomy( $taxonomy, $object_type, $args = [] ) {
		$GLOBALS['ct_taxonomies'][ $taxonomy ] = [ 'objects' => (array) $object_type ] + $args;

		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
		$GLOBALS['ct_hooks'][ $hook ][] = $callback;

		return true;
	}
}

if ( ! function_exists( 'did_action' ) ) {
	function did_action( $hook ) {
		return (int) ( $GLOBALS['ct_did'][ $hook ] ?? 0 );
	}
}

/*
 * The translation layer. `translate()` looks the string up in whatever
 * dictionary a test put there, so a test can prove the domain is honoured
 * without a .mo file.
 */
if ( ! function_exists( 'translate' ) ) {
	function translate( $text, $domain = 'default' ) {
		return $GLOBALS['ct_translations'][ $domain ][ $text ] ?? $text;
	}
}

/**
 * Forget everything a previous test set up.
 *
 * @return void
 */
function ct_reset_globals(): void {
	$GLOBALS['ct_post_types']   = [];
	$GLOBALS['ct_taxonomies']   = [];
	$GLOBALS['ct_hooks']        = [];
	$GLOBALS['ct_did']          = [];
	$GLOBALS['ct_translations'] = [];
}
