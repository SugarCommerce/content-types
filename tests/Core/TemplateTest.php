<?php
/**
 * A block template, written the short way.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests\Core;

use SugarCommerce\ContentTypes\PostType;

/**
 * What the block editor is given is core's long form.
 */
final class TemplateTest extends TestCase {

	public function tear_down(): void {
		unregister_post_type( 'ct_recipe' );
		parent::tear_down();
	}

	public function test_short_forms_reach_core_in_its_own(): void {
		PostType::register(
			'ct_recipe',
			[
				'template'      => [
					'heading',
					[ 'image', [ 'align' => 'wide' ] ],
					[ 'core/columns', [], [ 'column', [ 'column', [ 'width' => '33%' ] ] ] ],
					'my-plugin/price',
					'not a block',
					42,
				],
				'template_lock' => 'all',
			]
		);

		$this->assertSame(
			[
				[ 'core/heading', [] ],
				[ 'core/image', [ 'align' => 'wide' ] ],
				[ 'core/columns', [], [ [ 'core/column', [] ], [ 'core/column', [ 'width' => '33%' ] ] ] ],
				[ 'my-plugin/price', [] ],
			],
			get_post_type_object( 'ct_recipe' )->template
		);
		$this->assertSame( 'all', get_post_type_object( 'ct_recipe' )->template_lock );
	}

	public function test_no_template_is_core_s_none(): void {
		PostType::register( 'ct_recipe' );

		$this->assertSame( [], get_post_type_object( 'ct_recipe' )->template );
	}
}
