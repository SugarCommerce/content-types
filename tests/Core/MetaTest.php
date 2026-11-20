<?php
/**
 * Post meta declared with a post type.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests\Core;

use SugarCommerce\ContentTypes\PostType;
use WP_REST_Request;

/**
 * Registered for this post type only, in the REST schema, cleaned on save.
 */
final class MetaTest extends TestCase {

	public function tear_down(): void {
		foreach ( [ 'ct_isbn', 'ct_pages', 'ct_awards', '_ct_stock' ] as $key ) {
			unregister_post_meta( 'ct_book', $key );
		}

		unregister_post_type( 'ct_book' );
		parent::tear_down();
	}

	private function register(): void {
		PostType::register(
			'ct_book',
			[
				'supports' => [ 'title', 'editor' ],
				'meta'     => [
					'ct_isbn'   => [ 'description' => 'The ISBN.' ],
					'ct_pages'  => [ 'type' => 'integer', 'default' => 0 ],
					'ct_awards' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
					'_ct_stock' => [ 'type' => 'integer', 'show_in_rest' => false ],
				],
			]
		);
	}

	public function test_meta_is_registered_for_this_post_type_only(): void {
		$this->register();

		$keys = get_registered_meta_keys( 'post', 'ct_book' );

		$this->assertSame( [ 'ct_isbn', 'ct_pages', 'ct_awards', '_ct_stock' ], array_keys( $keys ) );
		$this->assertTrue( $keys['ct_isbn']['single'] );
		$this->assertSame( 0, $keys['ct_pages']['default'] );
		$this->assertArrayNotHasKey( 'ct_isbn', get_registered_meta_keys( 'post', 'post' ) );
	}

	/**
	 * custom-fields is what puts meta in a post's REST answer at all.
	 */
	public function test_meta_in_rest_adds_custom_fields_support(): void {
		$this->register();

		$this->assertTrue( post_type_supports( 'ct_book', 'custom-fields' ) );
	}

	public function test_the_rest_schema_has_the_meta_with_its_types(): void {
		$this->register();
		do_action( 'rest_api_init' );

		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/ct_book' );
		$response = rest_get_server()->dispatch( $request );
		$meta     = $response->get_data()['schema']['properties']['meta']['properties'];

		$this->assertSame( 'string', $meta['ct_isbn']['type'] );
		$this->assertSame( 'integer', $meta['ct_pages']['type'] );
		$this->assertSame( 'array', $meta['ct_awards']['type'] );
		$this->assertSame( [ 'type' => 'string' ], $meta['ct_awards']['items'] );
		$this->assertArrayNotHasKey( '_ct_stock', $meta );
	}

	public function test_an_editor_writes_meta_through_rest(): void {
		$this->register();
		do_action( 'rest_api_init' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$id      = self::factory()->post->create( [ 'post_type' => 'ct_book' ] );
		$request = new WP_REST_Request( 'POST', '/wp/v2/ct_book/' . $id );
		$request->set_body_params( [ 'meta' => [ 'ct_pages' => 320, 'ct_awards' => [ 'Booker' ] ] ] );

		$this->assertSame( 200, rest_get_server()->dispatch( $request )->get_status() );
		$this->assertSame( '320', get_post_meta( $id, 'ct_pages', true ) );
		$this->assertSame( [ 'Booker' ], get_post_meta( $id, 'ct_awards', true ) );
	}

	public function test_a_value_saved_outside_rest_is_cleaned_by_its_type(): void {
		$this->register();

		$id = self::factory()->post->create( [ 'post_type' => 'ct_book' ] );

		update_post_meta( $id, 'ct_isbn', "<b>978-0</b>\n" );
		update_post_meta( $id, 'ct_pages', '12 pages' );

		$this->assertSame( '978-0', get_post_meta( $id, 'ct_isbn', true ) );
		$this->assertSame( '12', get_post_meta( $id, 'ct_pages', true ) );
	}
}
