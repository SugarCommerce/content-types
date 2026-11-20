<?php
/**
 * A block-editor template, written the short way.
 *
 * @package   SugarCommerce\ContentTypes
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Support;

/**
 * Class BlockTemplate
 *
 * A post type's `template` is the blocks a new post starts with. Core wants
 * a list of `[ name, attributes, inner blocks ]` arrays, every level of it,
 * and a block written any other way is silently not there. This takes the
 * short forms as well and hands core the long one:
 *
 *     'template' => [
 *         'heading',                                         // core/heading
 *         [ 'image', [ 'align' => 'wide' ] ],
 *         [ 'core/columns', [], [ 'column', 'column' ] ],
 *         'my-plugin/price',
 *     ]
 *
 * A name with no namespace is core's. Anything that is not a block name --
 * a typo with a space in it, a number -- is dropped rather than passed on to
 * fail in the editor.
 *
 * @since 1.0.0
 */
final class BlockTemplate {

	/**
	 * A template in core's form.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, mixed> $template Blocks, short or long.
	 *
	 * @return array<int, array{0: string, 1: array<string, mixed>, 2?: array<int, mixed>}>
	 */
	public static function normalize( array $template ): array {
		$blocks = [];

		foreach ( $template as $block ) {
			$block = is_array( $block ) ? array_values( $block ) : [ $block ];
			$name  = self::name( $block[0] ?? null );

			if ( null === $name ) {
				continue;
			}

			$normal = [ $name, is_array( $block[1] ?? null ) ? $block[1] : [] ];

			if ( isset( $block[2] ) && is_array( $block[2] ) ) {
				$normal[] = self::normalize( $block[2] );
			}

			$blocks[] = $normal;
		}

		return $blocks;
	}

	/**
	 * A block's full name, or null when it is not one.
	 *
	 * @param mixed $name What was written.
	 *
	 * @return string|null
	 */
	private static function name( mixed $name ): ?string {
		if ( ! is_string( $name ) ) {
			return null;
		}

		$name = strtolower( trim( $name ) );
		$name = str_contains( $name, '/' ) ? $name : 'core/' . $name;

		return 1 === preg_match( '#^[a-z][a-z0-9-]*/[a-z][a-z0-9-]*$#', $name ) ? $name : null;
	}
}
