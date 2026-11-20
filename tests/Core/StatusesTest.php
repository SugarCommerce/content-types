<?php
/**
 * Post statuses declared with a post type.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests\Core;

use SugarCommerce\ContentTypes\PostType;

/**
 * Registered with a count label and listed in the admin.
 */
final class StatusesTest extends TestCase {

	public function tear_down(): void {
		global $wp_post_statuses;

		unset( $wp_post_statuses['ct_refunded'], $wp_post_statuses['ct_archived'] );
		unregister_post_type( 'ct_order' );
		parent::tear_down();
	}

	public function test_a_status_is_registered_listed_and_counted(): void {
		PostType::register(
			'ct_order',
			[
				'statuses' => [
					'ct_refunded' => 'Refunded',
					'ct_archived' => [ 'label' => 'Archived', 'show_in_admin_all_list' => false ],
				],
			]
		);

		$refunded = get_post_status_object( 'ct_refunded' );

		$this->assertSame( 'Refunded', $refunded->label );
		$this->assertTrue( $refunded->protected );
		$this->assertFalse( $refunded->internal, 'core makes a status with no visibility internal: hidden from every list' );
		$this->assertFalse( $refunded->public );
		$this->assertTrue( $refunded->show_in_admin_status_list );
		$this->assertSame( 'Refunded <span class="count">(3)</span>', sprintf( translate_nooped_plural( $refunded->label_count, 3 ), 3 ) );

		$this->assertFalse( get_post_status_object( 'ct_archived' )->show_in_admin_all_list );
	}

	public function test_a_post_can_be_saved_in_it_and_is_not_on_the_site(): void {
		PostType::register( 'ct_order', [ 'statuses' => [ 'ct_refunded' => 'Refunded' ] ] );

		$id = self::factory()->post->create( [ 'post_type' => 'ct_order', 'post_status' => 'ct_refunded' ] );

		$this->assertSame( 'ct_refunded', get_post_status( $id ) );
		$this->assertSame( [], get_posts( [ 'post_type' => 'ct_order', 'post_status' => 'publish' ] ) );
	}
}
