<?php
/**
 * Parity with what core actually ships.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests;

use SugarCommerce\ContentTypes\Support\Icon;
use SugarCommerce\ContentTypes\Support\Labels;
use PHPUnit\Framework\TestCase;

/**
 * Two lists this library has an opinion about, checked against core's.
 *
 * The label set, because a label core adds and this does not is a screen that
 * silently keeps saying "Post" — which is the one thing the library exists to
 * stop. And the dashicon names, because the shortcut table is a set of words
 * that are deliberately *not* dashicons, and a word that is one must never be
 * intercepted.
 *
 * Both fixtures are copies rather than reads of a WordPress install: the
 * suite runs without one. A copy goes stale in one direction only — core adds
 * names, it does not remove them — so a stale fixture under-reports and never
 * fails wrongly.
 */
final class CoreParityTest extends TestCase {

	/**
	 * Every label WP_Post_Type::get_labels() produces.
	 *
	 * From wp-includes/post.php, get_post_type_labels(), WordPress 7.1.
	 *
	 * @var string[]
	 */
	private const CORE_POST_TYPE = [
		'name',
		'singular_name',
		'add_new',
		'add_new_item',
		'edit_item',
		'new_item',
		'view_item',
		'view_items',
		'search_items',
		'not_found',
		'not_found_in_trash',
		'parent_item_colon',
		'all_items',
		'archives',
		'attributes',
		'insert_into_item',
		'uploaded_to_this_item',
		'featured_image',
		'set_featured_image',
		'remove_featured_image',
		'use_featured_image',
		'filter_items_list',
		'filter_by_date',
		'items_list_navigation',
		'items_list',
		'item_published',
		'item_published_privately',
		'item_reverted_to_draft',
		'item_scheduled',
		'item_updated',
		'item_link',
		'item_link_description',
		'item_trashed',
		'menu_name',
		'name_admin_bar',
	];

	/**
	 * Every label get_taxonomy_labels() produces, for both kinds.
	 *
	 * From wp-includes/taxonomy.php, WordPress 7.1. Core returns all of them
	 * whichever kind the taxonomy is, setting the ones that do not apply to
	 * null; this library leaves them out and core's merge fills the null
	 * back in, which is the same object either way.
	 *
	 * @var string[]
	 */
	private const CORE_TAXONOMY = [
		'name',
		'singular_name',
		'search_items',
		'popular_items',
		'all_items',
		'parent_item',
		'parent_item_colon',
		'name_field_description',
		'slug_field_description',
		'parent_field_description',
		'desc_field_description',
		'edit_item',
		'view_item',
		'update_item',
		'add_new_item',
		'new_item_name',
		'separate_items_with_commas',
		'add_or_remove_items',
		'choose_from_most_used',
		'not_found',
		'no_terms',
		'filter_by_item',
		'items_list_navigation',
		'items_list',
		'most_used',
		'back_to_items',
		'item_link',
		'item_link_description',
		'menu_name',
		'name_admin_bar',
		'template_name',
	];

	/**
	 * Every label core sets is one this library names.
	 *
	 * A missing one is a screen that keeps core's default — "Add Post" on a
	 * post type called Book, which is the entire reason to wrap
	 * register_post_type().
	 */
	public function test_every_post_type_label_is_covered(): void {
		$labels = Labels::post_type( 'Book', 'Books' );

		foreach ( self::CORE_POST_TYPE as $key ) {
			$this->assertArrayHasKey( $key, $labels, sprintf( 'Core sets %s and this does not.', $key ) );
			$this->assertNotSame( '', $labels[ $key ], sprintf( '%s is empty.', $key ) );
		}
	}

	/**
	 * Likewise for a taxonomy, of either kind.
	 *
	 * Between the two kinds every label has to be covered. The four each kind
	 * leaves out are the four core sets to null for that kind — a tag has no
	 * parent, so "Parent Tag" would name a control that does not exist.
	 */
	public function test_every_taxonomy_label_is_covered_by_one_kind_or_the_other(): void {
		$covered = array_merge(
			array_keys( Labels::taxonomy( 'Genre', 'Genres', true ) ),
			array_keys( Labels::taxonomy( 'Tag', 'Tags', false ) )
		);

		foreach ( self::CORE_TAXONOMY as $key ) {
			$this->assertContains( $key, $covered, sprintf( 'Core sets %s and neither kind does.', $key ) );
		}
	}

	/**
	 * A taxonomy template is an archive, not a single item.
	 *
	 * The post type's wording was copied across, so the site editor listed a
	 * taxonomy template as "Single item: Genre".
	 */
	public function test_a_taxonomy_template_is_named_as_an_archive(): void {
		$this->assertSame( 'Genre Archives', Labels::taxonomy( 'Genre', 'Genres' )['template_name'] );
		$this->assertSame( 'Single item: Book', Labels::post_type( 'Book', 'Books' )['template_name'] );
	}

	/**
	 * Every dashicon core ships resolves, without being listed.
	 *
	 * Which is the answer to "should the shortcut table have all of them in
	 * it": it already does, in one line.
	 */
	public function test_every_dashicon_resolves(): void {
		$names = $this->dashicons();

		$this->assertGreaterThan( 300, count( $names ), 'The dashicon fixture looks wrong.' );

		foreach ( $names as $name ) {
			$this->assertSame(
				'dashicons-' . $name,
				Icon::resolve( $name ),
				sprintf( '%s does not resolve to itself.', $name )
			);

			$this->assertSame(
				'dashicons-' . $name,
				Icon::resolve( 'dashicons-' . $name ),
				sprintf( 'dashicons-%s does not survive being passed through.', $name )
			);
		}
	}

	/**
	 * No shortcut may shadow a dashicon of the same name.
	 *
	 * `calendar` did. Somebody asking for the icon that exists and is called
	 * `calendar` was handed `calendar-alt` instead, and nothing said so —
	 * both are real icons, so it renders and it is simply not the one that
	 * was asked for.
	 */
	public function test_no_shortcut_shadows_a_real_dashicon(): void {
		$real = $this->dashicons();

		foreach ( Icon::shortcuts() as $word ) {
			$this->assertNotContains(
				$word,
				$real,
				sprintf( '"%s" is a dashicon of its own; the shortcut intercepts it.', $word )
			);
		}
	}

	/**
	 * And every shortcut points at one that exists.
	 */
	public function test_every_shortcut_points_at_a_real_dashicon(): void {
		$real = $this->dashicons();

		foreach ( Icon::shortcuts() as $word ) {
			$resolved = substr( Icon::resolve( $word ), strlen( 'dashicons-' ) );

			$this->assertContains(
				$resolved,
				$real,
				sprintf( '"%s" resolves to dashicons-%s, which core does not ship.', $word, $resolved )
			);
		}
	}

	/**
	 * The dashicon names core ships.
	 *
	 * @return string[]
	 */
	private function dashicons(): array {
		$path = __DIR__ . '/dashicons.txt';

		$this->assertFileExists( $path );

		return array_values( array_filter( array_map( 'trim', (array) file( $path ) ) ) );
	}
}
