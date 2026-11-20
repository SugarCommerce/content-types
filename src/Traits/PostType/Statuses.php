<?php
/**
 * Post statuses declared with a post type.
 *
 * @package   SugarCommerce\ContentTypes
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Traits\PostType;

/**
 * Trait Statuses
 *
 * `statuses` registers each with register_post_status(), with the two things
 * a hand-written one usually gets wrong:
 *
 *   - `label_count`, which the status links above the list read ("Refunded
 *     (3)"); without it they print the bare label with no count.
 *   - Visibility. A status that is not public, protected, private or
 *     internal is made internal by core -- hidden from every admin list --
 *     so the default here is protected: listed in the admin for anybody who
 *     can edit, never shown on the site.
 *
 *     'statuses' => [
 *         'refunded' => __( 'Refunded', 'my-plugin' ),
 *         'archived' => [ 'label' => __( 'Archived', 'my-plugin' ), 'show_in_admin_all_list' => false ],
 *     ]
 *
 * Statuses are site-wide in WordPress -- core has no "status of this post
 * type" -- so give them names your plugin owns.
 *
 * @since 1.0.0
 */
trait Statuses {

	/**
	 * The statuses declared, resolved: what register_post_status() is given.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function statuses(): array {
		$statuses = [];

		foreach ( (array) $this->config['statuses'] as $status => $args ) {
			$status = sanitize_key( (string) $status );

			if ( '' === $status ) {
				continue;
			}

			$args  = is_array( $args ) ? $args : [ 'label' => (string) $args ];
			$label = (string) ( $args['label'] ?? '' );
			$label = '' === $label ? ucwords( str_replace( [ '-', '_' ], ' ', $status ) ) : $label;

			$statuses[ $status ] = array_merge(
				[
					'label'                     => $label,
					'label_count'               => self::label_count( $label, (string) $this->config['text_domain'] ),
					'protected'                 => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
				],
				$args,
				[ 'label' => $label ]
			);
		}

		return $statuses;
	}

	/**
	 * Register the statuses. Part of on_init().
	 *
	 * @return void
	 */
	private function register_statuses(): void {
		foreach ( $this->statuses() as $status => $args ) {
			register_post_status( $status, $args );
		}
	}

	/**
	 * A status's count label, in the shape _n_noop() returns.
	 *
	 * Built by hand rather than with _n_noop(), whose strings have to be
	 * literals for an extractor to find: these are the label, which the
	 * caller translated already.
	 *
	 * @param string $label       The status's label.
	 * @param string $text_domain The consumer's text domain, or ''.
	 *
	 * @return array{0: string, 1: string, singular: string, plural: string, context: null, domain: string|null}
	 */
	private static function label_count( string $label, string $text_domain ): array {
		$counted = $label . ' <span class="count">(%s)</span>';

		return [
			0          => $counted,
			1          => $counted,
			'singular' => $counted,
			'plural'   => $counted,
			'context'  => null,
			'domain'   => '' === $text_domain ? null : $text_domain,
		];
	}
}
