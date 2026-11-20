<?php
/**
 * PHPUnit bootstrap for the core suite: real WordPress, a real database.
 *
 * The unit suite (tests/*.php) checks what this library decides, against
 * stubs. This one checks what WordPress then does with it -- the label keys
 * core knows, every argument arriving on the registered object, capabilities
 * on real roles, statuses, meta in the REST schema, rewrite rules, sortable
 * columns -- so a release of core that adds a label or an argument shows up
 * here as a failure rather than as a screen that says "Post".
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$content_types_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: dirname( __DIR__, 2 ) . '/vendor/wp-phpunit/wp-phpunit';

if ( ! is_readable( $content_types_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "Could not find the WordPress test library at {$content_types_tests_dir}. Run 'composer install', or set WP_TESTS_DIR.\n" );
	exit( 1 );
}

require_once $content_types_tests_dir . '/includes/functions.php';
require_once $content_types_tests_dir . '/includes/bootstrap.php';
