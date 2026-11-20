<?php
/**
 * Post meta declared with a post type.
 *
 * @package   SugarCommerce\ContentTypes
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Traits\PostType;

/**
 * Trait Meta
 *
 * `meta` registers each key with register_post_meta() for this post type
 * only, with what a key usually needs and core leaves off:
 *
 *   - `single` and `show_in_rest` on, so the block editor and the REST API
 *     can read and write it -- and `custom-fields` added to `supports`,
 *     without which core leaves meta out of the post's REST response
 *     whatever `show_in_rest` says.
 *   - A REST schema for an `array` or `object`, which core refuses to
 *     expose without one: `items` (for an array) or `properties` (for an
 *     object) is enough to build it.
 *   - A `sanitize_callback` from the type, so a value written outside REST
 *     is cleaned too: text for a string, a whole number for an integer.
 *     A `sanitize_callback` of your own replaces it.
 *
 *     'meta' => [
 *         'isbn'   => [ 'description' => 'The book\'s ISBN.' ],
 *         'pages'  => [ 'type' => 'integer', 'default' => 0 ],
 *         'awards' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
 *         '_stock' => [ 'type' => 'integer', 'show_in_rest' => false ],
 *     ]
 *
 * Anything else is register_meta()'s own argument (`auth_callback`,
 * `revisions_enabled`, `label`…) and passes through. A key starting with an
 * underscore is protected in core: REST writes it only for a user an
 * `auth_callback` allows.
 *
 * @since 1.0.0
 */
trait Meta {

	/**
	 * The meta declared, resolved: what register_post_meta() is given.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function meta(): array {
		$meta = [];

		foreach ( (array) $this->config['meta'] as $key => $args ) {
			$key = (string) $key;

			if ( '' === $key ) {
				continue;
			}

			$args = array_merge(
				[
					'type'         => 'string',
					'single'       => true,
					'show_in_rest' => true,
				],
				(array) $args
			);

			$type = (string) $args['type'];

			if ( ! isset( $args['sanitize_callback'] ) && null !== self::sanitizer( $type ) ) {
				$args['sanitize_callback'] = self::sanitizer( $type );
			}

			// Core will not put an array or object in REST without a schema
			// saying what is in it.
			if ( true === $args['show_in_rest'] && in_array( $type, [ 'array', 'object' ], true ) ) {
				$schema = 'array' === $type
					? [
						'type' => 'array',
						'items' => (array) ( $args['items'] ?? [ 'type' => 'string' ] ),
					]
					: [
						'type' => 'object',
						'properties' => (array) ( $args['properties'] ?? [] ),
						'additionalProperties' => false,
					];

				$args['show_in_rest'] = [ 'schema' => $schema ];
			}

			unset( $args['items'], $args['properties'] );

			$meta[ $key ] = $args;
		}

		return $meta;
	}

	/**
	 * Whether any meta is to be in REST, which needs `custom-fields`.
	 *
	 * @return bool
	 */
	private function meta_in_rest(): bool {
		foreach ( $this->meta() as $args ) {
			if ( false !== $args['show_in_rest'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register the meta. Part of on_init().
	 *
	 * @return void
	 */
	private function register_meta(): void {
		foreach ( $this->meta() as $key => $args ) {
			register_post_meta( $this->post_type, $key, $args );
		}
	}

	/**
	 * What cleans a value of a meta type, or null to leave it to core.
	 *
	 * @param string $type The meta's type.
	 *
	 * @return callable|null
	 */
	private static function sanitizer( string $type ): ?callable {
		// Closures, not intval and floatval: a sanitize filter is given four
		// arguments, and intval()'s second is the base.
		return match ( $type ) {
			'string'  => 'sanitize_text_field',
			'integer' => static fn( mixed $value ): int => is_scalar( $value ) ? (int) $value : 0,
			'number'  => static fn( mixed $value ): float => is_scalar( $value ) ? (float) $value : 0.0,
			'boolean' => 'rest_sanitize_boolean',
			default   => null,
		};
	}
}
