<?php
/**
 * A post type's own capabilities, on real roles.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests\Core;

use SugarCommerce\ContentTypes\PostType;

/**
 * Granted on activation, revoked on uninstall -- and never one core needs.
 */
final class CapabilitiesTest extends TestCase {

	public function tear_down(): void {
		( new PostType( 'ct_book', [ 'capability_type' => 'ct_book', 'map_meta_cap' => true ] ) )->revoke_capabilities();
		unregister_post_type( 'ct_book' );
		parent::tear_down();
	}

	public function test_the_capabilities_are_the_ones_core_will_check(): void {
		$type = new PostType( 'ct_book', [ 'capability_type' => [ 'ct_book', 'ct_books' ], 'map_meta_cap' => true ] );

		PostType::register( 'ct_book', [ 'capability_type' => [ 'ct_book', 'ct_books' ], 'map_meta_cap' => true ] );

		$cap      = (array) get_post_type_object( 'ct_book' )->cap;
		$expected = array_values( array_diff( array_unique( array_values( $cap ) ), [ 'edit_ct_book', 'read_ct_book', 'delete_ct_book', 'read' ] ) );

		sort( $expected );
		$caps = $type->capabilities();
		sort( $caps );

		$this->assertSame( $expected, $caps );
		$this->assertContains( 'edit_others_ct_books', $caps );
		$this->assertSame( 'edit_ct_books', get_post_type_object( 'ct_book' )->cap->create_posts, 'core creates with the edit capability' );
	}

	public function test_an_administrator_can_use_the_post_type_once_granted(): void {
		$admin = self::factory()->user->create_and_get( [ 'role' => 'administrator' ] );
		$type  = PostType::register( 'ct_book', [ 'capability_type' => 'ct_book', 'map_meta_cap' => true ] );

		$this->assertFalse( user_can( $admin, 'edit_ct_books' ), 'nobody has them until granted' );

		$type->grant_capabilities();
		$admin = get_userdata( $admin->ID );

		$this->assertTrue( user_can( $admin, 'edit_ct_books' ) );
		$this->assertTrue( user_can( $admin, 'publish_ct_books' ) );

		$post = self::factory()->post->create( [ 'post_type' => 'ct_book', 'post_author' => self::factory()->user->create() ] );

		$this->assertTrue( user_can( $admin, 'edit_post', $post ), 'meta capabilities map onto the granted ones' );
	}

	public function test_other_roles_only_when_asked(): void {
		$type = PostType::register( 'ct_book', [ 'capability_type' => 'ct_book', 'map_meta_cap' => true, 'grant_to' => [ 'administrator', 'editor', 'no-such-role' ] ] );

		$type->grant_capabilities();

		$this->assertTrue( get_role( 'editor' )->has_cap( 'edit_ct_books' ) );
		$this->assertFalse( get_role( 'author' )->has_cap( 'edit_ct_books' ) );

		$type->grant_capabilities( [ 'author' ] );

		$this->assertTrue( get_role( 'author' )->has_cap( 'edit_ct_books' ) );
	}

	public function test_revoking_takes_them_from_every_role(): void {
		$type = PostType::register( 'ct_book', [ 'capability_type' => 'ct_book', 'map_meta_cap' => true ] );

		$type->grant_capabilities( [ 'administrator', 'editor' ] );
		$type->revoke_capabilities();

		foreach ( [ 'administrator', 'editor' ] as $role ) {
			$this->assertFalse( get_role( $role )->has_cap( 'edit_ct_books' ), $role );
		}
	}

	/**
	 * The guard that matters: a post type on core's capabilities has none of
	 * its own, so an uninstall can never take edit_posts from the site.
	 */
	public function test_core_capabilities_are_never_granted_or_revoked(): void {
		$plain  = new PostType( 'ct_book' );
		$mapped = new PostType( 'ct_book', [ 'capability_type' => 'ct_book', 'capabilities' => [ 'edit_posts' => 'edit_posts', 'read' => 'read' ] ] );

		$this->assertSame( [], $plain->capabilities() );
		$this->assertSame( [], $plain->revoke_capabilities() );
		$this->assertNotContains( 'edit_posts', $mapped->capabilities() );
		$this->assertNotContains( 'read', $mapped->capabilities() );

		$mapped->revoke_capabilities();

		$this->assertTrue( get_role( 'editor' )->has_cap( 'edit_posts' ) );
		$this->assertTrue( get_role( 'subscriber' )->has_cap( 'read' ) );
	}
}
