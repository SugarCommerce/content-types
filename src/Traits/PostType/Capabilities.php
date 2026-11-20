<?php
/**
 * Capabilities of a post type's own.
 *
 * @package   SugarCommerce\ContentTypes
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Traits\PostType;

/**
 * Trait Capabilities
 *
 * A post type with a `capability_type` of its own -- `book`, or
 * `[ 'book', 'books' ]` -- is checked against capabilities nobody has:
 * WordPress builds `edit_books` and the rest, and gives them to no role, so
 * not even an administrator can open the screen. Core leaves granting them to
 * you; this works out which they are, the way core will, and grants or
 * revokes them.
 *
 * Only ever capabilities of this post type's own. One that a core post type
 * also uses -- `edit_posts`, because `capability_type` was left as `post`, or
 * because `capabilities` mapped onto it -- and `read` are never touched: an
 * uninstall that revoked them would take them from every role on the site.
 *
 *     register_activation_hook( __FILE__, static fn() => ( new PostType( 'book', $config ) )->grant_capabilities() );
 *     // uninstall.php
 *     ( new PostType( 'book', $config ) )->revoke_capabilities();
 *
 * @since 1.0.0
 */
trait Capabilities {

	/**
	 * The primitive capabilities this post type is checked against that are
	 * its own, in core's order.
	 *
	 * Worked out from the configuration with core's get_post_type_capabilities(),
	 * so it is right before the post type is registered -- on activation, say.
	 * Meta capabilities (`edit_book`, checked per post and mapped) are left out:
	 * no role holds those.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] Empty for a post type using core's capabilities.
	 */
	public function capabilities(): array {
		$type = (string) ( is_array( $this->config['capability_type'] ?? null ) ? ( $this->config['capability_type'][0] ?? 'post' ) : ( $this->config['capability_type'] ?? 'post' ) );

		$caps = (array) get_post_type_capabilities(
			(object) [
				'capability_type' => $this->config['capability_type'] ?? 'post',
				'capabilities'    => (array) ( $this->config['capabilities'] ?? [] ),
				// As WP_Post_Type decides it: on for post and page, off for
				// anything else unless asked.
				'map_meta_cap'    => $this->config['map_meta_cap'] ?? in_array( $type, [ 'post', 'page' ], true ),
			]
		);

		unset( $caps['edit_post'], $caps['read_post'], $caps['delete_post'] );

		$core = self::core_capabilities();

		return array_values( array_unique( array_filter( array_map( 'strval', $caps ), static fn( string $cap ): bool => '' !== $cap && ! in_array( $cap, $core, true ) ) ) );
	}

	/**
	 * Give this post type's capabilities to roles.
	 *
	 * For an activation hook. Roles that do not exist are skipped.
	 *
	 * @since 1.0.0
	 *
	 * @param string[]|null $roles Role names. Default the configuration's
	 *                             `grant_to`, which defaults to
	 *                             administrator.
	 *
	 * @return string[] The capabilities granted.
	 */
	public function grant_capabilities( ?array $roles = null ): array {
		$caps = $this->capabilities();

		foreach ( $roles ?? (array) $this->config['grant_to'] as $name ) {
			$role = get_role( (string) $name );

			if ( null === $role ) {
				continue;
			}

			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}

		return $caps;
	}

	/**
	 * Take this post type's capabilities back from every role.
	 *
	 * For uninstall. Every role, not only the ones granted to: a site owner
	 * may have handed them on with a role editor.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] The capabilities revoked.
	 */
	public function revoke_capabilities(): array {
		$caps = $this->capabilities();

		foreach ( array_keys( wp_roles()->roles ) as $name ) {
			$role = get_role( (string) $name );

			foreach ( $caps as $cap ) {
				$role?->remove_cap( $cap );
			}
		}

		return $caps;
	}

	/**
	 * Every capability core's own post types and roles rely on, which
	 * nothing here may grant or revoke.
	 *
	 * @return string[]
	 */
	private static function core_capabilities(): array {
		$core = [ 'read' ];

		foreach ( [ 'post', 'page' ] as $type ) {
			$core = array_merge(
				$core,
				array_values(
					(array) get_post_type_capabilities(
						(object) [
							'capability_type' => $type,
							'capabilities'    => [],
							'map_meta_cap'    => true,
						]
					)
				)
			);
		}

		return array_values( array_unique( $core ) );
	}
}
