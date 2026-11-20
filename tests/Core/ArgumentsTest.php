<?php
/**
 * Every argument reaches WordPress as it was given.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests\Core;

use SugarCommerce\ContentTypes\PostType;
use SugarCommerce\ContentTypes\Taxonomy;
use WP_REST_Posts_Controller;
use WP_REST_Terms_Controller;

/**
 * Anything register_post_type() or register_taxonomy() takes passes through
 * untouched: this library adds keys of its own and changes no core one.
 */
final class ArgumentsTest extends TestCase {

	public function tear_down(): void {
		unregister_post_type( 'ct_event' );
		unregister_taxonomy( 'ct_venue' );
		parent::tear_down();
	}

	/**
	 * A meta box callback for the test.
	 *
	 * @return void
	 */
	public static function meta_boxes(): void {}

	public function test_every_post_type_argument_reaches_the_object(): void {
		$given = [
			'description'                     => 'Things that happen.',
			'public'                          => false,
			'hierarchical'                    => true,
			'exclude_from_search'             => true,
			'publicly_queryable'              => true,
			'embeddable'                      => false,
			'show_ui'                         => true,
			'show_in_menu'                    => 'tools.php',
			'show_in_nav_menus'               => false,
			'show_in_admin_bar'               => false,
			'menu_position'                   => 42,
			'capability_type'                 => [ 'ct_event', 'ct_events' ],
			'map_meta_cap'                    => true,
			'register_meta_box_cb'            => [ self::class, 'meta_boxes' ],
			'has_archive'                     => 'happenings',
			'rewrite'                         => [ 'slug' => 'happening', 'with_front' => false, 'feeds' => false, 'pages' => true, 'ep_mask' => EP_PERMALINK ],
			'query_var'                       => 'happening',
			'can_export'                      => false,
			'delete_with_user'                => true,
			'template'                        => [ [ 'core/paragraph', [ 'placeholder' => 'What happens' ] ] ],
			'template_lock'                   => 'insert',
			'show_in_rest'                    => true,
			'rest_base'                       => 'happenings',
			'rest_namespace'                  => 'events/v1',
			'rest_controller_class'           => WP_REST_Posts_Controller::class,
			'autosave_rest_controller_class'  => 'WP_REST_Autosaves_Controller',
			'revisions_rest_controller_class' => 'WP_REST_Revisions_Controller',
			'late_route_registration'         => true,
		];

		PostType::register( 'ct_event', [ 'singular' => 'Event' ] + $given );

		$object = get_post_type_object( 'ct_event' );

		// Core keeps the singular of a [ singular, plural ] capability type,
		// having built the capabilities from both.
		$given['capability_type'] = 'ct_event';

		foreach ( $given as $key => $value ) {
			$this->assertSame( $value, $object->$key, $key );
		}

		$this->assertSame( 'edit_ct_events', $object->cap->edit_posts );
		$this->assertTrue( post_type_supports( 'ct_event', 'title' ) );
	}

	public function test_supports_and_taxonomies_reach_core_as_core_keeps_them(): void {
		PostType::register(
			'ct_event',
			[
				'supports'   => [ 'title', 'excerpt', 'page-attributes' ],
				'taxonomies' => [ 'category' ],
			]
		);

		$this->assertTrue( post_type_supports( 'ct_event', 'excerpt' ) );
		$this->assertFalse( post_type_supports( 'ct_event', 'editor' ) );
		$this->assertContains( 'ct_event', get_taxonomy( 'category' )->object_type );
	}

	public function test_none_of_this_librarys_own_keys_reach_the_object(): void {
		PostType::register( 'ct_event', [ 'singular' => 'Event', 'plural' => 'Events', 'icon' => 'event', 'text_domain' => 'x' ] );

		$object = get_post_type_object( 'ct_event' );

		foreach ( [ 'singular', 'plural', 'icon', 'text_domain' ] as $ours ) {
			$this->assertFalse( property_exists( $object, $ours ), $ours );
		}

		$this->assertSame( \SugarCommerce\ContentTypes\Support\Icon::resolve( 'event' ), $object->menu_icon );
		$this->assertStringStartsWith( 'dashicons-', $object->menu_icon );
	}

	/**
	 * A count callback for the test.
	 *
	 * @return void
	 */
	public static function count_terms(): void {}

	public function test_every_taxonomy_argument_reaches_the_object(): void {
		$given = [
			'description'           => 'Where things happen.',
			'public'                => false,
			'publicly_queryable'    => true,
			'hierarchical'          => true,
			'show_ui'               => true,
			'show_in_menu'          => false,
			'show_in_nav_menus'     => false,
			'show_tagcloud'         => false,
			'show_in_quick_edit'    => false,
			'show_admin_column'     => false,
			'meta_box_cb'           => false,
			'meta_box_sanitize_cb'  => 'taxonomy_meta_box_sanitize_cb_input',
			'capabilities'          => [ 'manage_terms' => 'manage_venues', 'edit_terms' => 'edit_venues', 'delete_terms' => 'delete_venues', 'assign_terms' => 'assign_venues' ],
			'rewrite'               => [ 'slug' => 'venue', 'with_front' => false, 'hierarchical' => true, 'ep_mask' => EP_NONE ],
			'query_var'             => 'venue',
			'update_count_callback' => [ self::class, 'count_terms' ],
			'show_in_rest'          => true,
			'rest_base'             => 'venues',
			'rest_namespace'        => 'events/v1',
			'rest_controller_class' => WP_REST_Terms_Controller::class,
			'default_term'          => [ 'name' => 'Online', 'slug' => 'online', 'description' => 'No room at all.' ],
			'sort'                  => true,
			'args'                  => [ 'orderby' => 'term_order' ],
		];

		Taxonomy::register( 'ct_venue', [ 'singular' => 'Venue', 'objects' => [ 'post' ] ] + $given );

		$object = get_taxonomy( 'ct_venue' );

		foreach ( $given as $key => $value ) {
			if ( 'capabilities' === $key ) {
				foreach ( $value as $cap => $name ) {
					$this->assertSame( $name, $object->cap->$cap, $cap );
				}

				continue;
			}

			$this->assertSame( $value, $object->$key, $key );
		}

		$this->assertSame( [ 'post' ], $object->object_type );
	}
}
