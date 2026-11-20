<?php
/**
 * Every label core knows, generated.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests\Core;

use SugarCommerce\ContentTypes\PostType;
use SugarCommerce\ContentTypes\Support\Labels;
use SugarCommerce\ContentTypes\Taxonomy;
use WP_Post_Type;
use WP_Taxonomy;

/**
 * The label keys are diffed against what this WordPress produces, not against
 * a copied list: a label core adds in a later release fails here the day the
 * suite runs on it, instead of quietly saying "Post" on somebody's screen.
 */
final class LabelsTest extends TestCase {

	public function tear_down(): void {
		unregister_post_type( 'ct_book' );
		unregister_taxonomy( 'ct_genre' );
		unregister_taxonomy( 'ct_mood' );
		parent::tear_down();
	}

	public function test_every_post_type_label_core_knows_is_written(): void {
		$core = array_keys( (array) get_post_type_labels( new WP_Post_Type( 'ct_probe', [] ) ) );

		$this->assertSame( [], array_values( array_diff( $core, array_keys( Labels::post_type( 'Book' ) ) ) ) );
	}

	public function test_every_taxonomy_label_core_knows_is_written_for_both_kinds(): void {
		foreach ( [ false, true ] as $hierarchical ) {
			// A flat taxonomy's parent labels are null in core: there is no
			// parent to name, so nothing to write.
			$core = array_keys( array_filter( (array) get_taxonomy_labels( new WP_Taxonomy( 'ct_probe', 'post', [ 'hierarchical' => $hierarchical ] ) ), static fn( $label ): bool => null !== $label ) );

			$this->assertSame( [], array_values( array_diff( $core, array_keys( Labels::taxonomy( 'Genre', '', $hierarchical ) ) ) ), $hierarchical ? 'hierarchical' : 'flat' );
		}
	}

	/**
	 * Registered, nothing on the object is core's own wording for posts.
	 */
	public function test_no_registered_label_is_cores_default_for_posts(): void {
		PostType::register( 'ct_book', [ 'singular' => 'Book' ] );

		$ours  = (array) get_post_type_object( 'ct_book' )->labels;
		$posts = (array) get_post_type_object( 'post' )->labels;

		// Wording that is the same for every type (featured image, filter
		// by date) is core's and right.
		$generic = [ 'featured_image', 'set_featured_image', 'remove_featured_image', 'use_featured_image', 'filter_by_date' ];

		foreach ( $ours as $key => $label ) {
			if ( in_array( $key, $generic, true ) || ! isset( $posts[ $key ] ) ) {
				continue;
			}

			$this->assertNotSame( $posts[ $key ], $label, $key );
		}

		$this->assertSame( 'Add New Book', $ours['add_new_item'] );
		$this->assertSame( 'Single item: Book', $ours['template_name'] );
	}

	public function test_a_taxonomys_labels_reach_the_registered_object(): void {
		Taxonomy::register( 'ct_genre', [ 'singular' => 'Genre', 'hierarchical' => true ] );
		Taxonomy::register( 'ct_mood', [ 'singular' => 'Mood' ] );

		$genre = (array) get_taxonomy( 'ct_genre' )->labels;
		$mood  = (array) get_taxonomy( 'ct_mood' )->labels;

		$this->assertSame( 'Parent Genre', $genre['parent_item'] );
		$this->assertSame( 'Genre Archives', $genre['template_name'] );
		$this->assertSame( '&larr; Go to Genres', $genre['back_to_items'] );
		$this->assertSame( 'Separate moods with commas', $mood['separate_items_with_commas'] );
		$this->assertNull( $mood['parent_item'] );
	}
}
