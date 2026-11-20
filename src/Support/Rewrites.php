<?php
/**
 * Flushing rewrite rules once, when they change.
 *
 * @package   SugarCommerce\ContentTypes
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Support;

/**
 * Class Rewrites
 *
 * A post type's URLs work only after the rewrite rules are rebuilt, and
 * rebuilding them is slow -- which is why a plugin that flushes on every
 * request is a support ticket, and one that never does ships a post type
 * whose pages 404 until somebody saves the permalink settings.
 *
 * With `flush_rewrite` on, each post type or taxonomy's URL settings (slug,
 * archive, query var, public or not) are remembered by a fingerprint. The
 * rules are flushed once, on wp_loaded, on the first request where a
 * fingerprint is new or different -- after activation, or after the slug
 * changed -- and never again until it changes.
 *
 *     register_activation_hook( __FILE__, [ Rewrites::class, 'reset' ] );
 *
 * @since 1.0.0
 */
final class Rewrites {

	/**
	 * Where the fingerprints are kept: one option, keyed by kind and name.
	 */
	public const string OPTION = 'sugarcommerce_content_types_rewrites';

	/**
	 * The arguments that change what URLs a post type or taxonomy has.
	 */
	private const array RELEVANT = [ 'public', 'publicly_queryable', 'hierarchical', 'rewrite', 'has_archive', 'query_var', 'objects' ];

	/**
	 * Whether a flush is waiting for wp_loaded in this request.
	 *
	 * @var bool
	 */
	private static bool $pending = false;

	/**
	 * Remember a post type or taxonomy's URL settings, and flush if they are
	 * new or changed.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $kind 'post_type' or 'taxonomy'.
	 * @param string               $name Its slug.
	 * @param array<string, mixed> $args What it was registered with.
	 *
	 * @return bool Whether a flush was asked for.
	 */
	public static function watch( string $kind, string $name, array $args ): bool {
		$key   = $kind . ':' . $name;
		$print = md5( (string) wp_json_encode( array_intersect_key( $args, array_flip( self::RELEVANT ) ) ) );
		$known = (array) get_option( self::OPTION, [] );

		if ( ( $known[ $key ] ?? null ) === $print ) {
			return false;
		}

		$known[ $key ] = $print;
		update_option( self::OPTION, $known );

		if ( ! self::$pending ) {
			self::$pending = true;

			// After every plugin has registered what it registers on init,
			// so the rules are built once with all of it.
			if ( did_action( 'wp_loaded' ) ) {
				self::flush();
			} else {
				add_action( 'wp_loaded', [ self::class, 'flush' ] );
			}
		}

		return true;
	}

	/**
	 * Rebuild the rules. Hooked by watch(); once a request at most.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function flush(): void {
		self::$pending = false;

		flush_rewrite_rules( false );
	}

	/**
	 * Forget every fingerprint, so the next request flushes.
	 *
	 * For an activation hook, which runs before the post types are
	 * registered and so cannot usefully flush itself.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		delete_option( self::OPTION );
	}
}
