<?php
/**
 * Meta columns in the list, sortable.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests\Core;

use SugarCommerce\ContentTypes\PostType;

/**
 * Through core's own list-table hooks and a real query.
 */
final class ColumnsTest extends TestCase {

	/**
	 * The post type under test.
	 *
	 * @var PostType
	 */
	private PostType $type;

	public function set_up(): void {
		parent::set_up();

		$this->type = PostType::register(
			'ct_book',
			[
				'columns' => [
					'ct_isbn'  => [ 'label' => 'ISBN', 'meta' => 'ct_isbn', 'after' => 'title' ],
					'ct_pages' => [ 'label' => 'Pages', 'meta' => 'ct_pages', 'numeric' => true ],
					'ct_stock' => [ 'label' => 'Stock', 'render' => static fn( int $id ): string => '<strong>' . $id . '</strong><script>x</script>' ],
				],
			]
		);
	}

	public function tear_down(): void {
		remove_action( 'pre_get_posts', [ $this->type, 'sort_by_column' ] );
		unset( $GLOBALS['current_screen'] );
		unregister_post_type( 'ct_book' );
		parent::tear_down();
	}

	/**
	 * The main query of an admin list screen, as edit.php runs it.
	 *
	 * @param array<string, string> $vars Query vars from the request.
	 *
	 * @return \WP_Query
	 */
	private function admin_list( array $vars ): \WP_Query {
		set_current_screen( 'edit-ct_book' );

		$query                  = new \WP_Query();
		$GLOBALS['wp_the_query'] = $query;
		$query->query( [ 'post_type' => 'ct_book', 'post_status' => 'any' ] + $vars );

		return $query;
	}

	public function test_columns_go_where_they_were_asked(): void {
		$columns = apply_filters( 'manage_ct_book_posts_columns', [ 'cb' => '', 'title' => 'Title', 'author' => 'Author', 'date' => 'Date' ] );

		$this->assertSame( [ 'cb', 'title', 'ct_isbn', 'author', 'ct_pages', 'ct_stock', 'date' ], array_keys( $columns ) );
		$this->assertSame( 'ISBN', $columns['ct_isbn'] );
	}

	public function test_a_value_is_printed_escaped_and_a_render_through_kses(): void {
		$id = self::factory()->post->create( [ 'post_type' => 'ct_book' ] );
		update_post_meta( $id, 'ct_isbn', '<img src=x onerror=alert(1)>' );

		ob_start();
		do_action( 'manage_ct_book_posts_custom_column', 'ct_isbn', $id );
		do_action( 'manage_ct_book_posts_custom_column', 'ct_pages', $id );
		do_action( 'manage_ct_book_posts_custom_column', 'ct_stock', $id );
		do_action( 'manage_ct_book_posts_custom_column', 'not_ours', $id );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $html );
		$this->assertStringContainsString( '&#8212;', $html, 'no value is a dash' );
		$this->assertStringContainsString( '<strong>' . $id . '</strong>', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_meta_columns_are_sortable_and_a_rendered_one_is_not(): void {
		$sortable = apply_filters( 'manage_edit-ct_book_sortable_columns', [] );

		$this->assertSame( [ 'ct_isbn' => 'ct_isbn', 'ct_pages' => 'ct_pages' ], $sortable );
	}

	/**
	 * A real admin query, sorted by the meta as numbers, with a post that
	 * has no value still in the list.
	 */
	public function test_the_admin_list_sorts_by_the_meta_and_keeps_posts_without_it(): void {
		set_current_screen( 'edit-ct_book' );

		$ten     = self::factory()->post->create( [ 'post_type' => 'ct_book' ] );
		$nine    = self::factory()->post->create( [ 'post_type' => 'ct_book' ] );
		$hundred = self::factory()->post->create( [ 'post_type' => 'ct_book' ] );
		$none    = self::factory()->post->create( [ 'post_type' => 'ct_book' ] );

		update_post_meta( $ten, 'ct_pages', '10' );
		update_post_meta( $nine, 'ct_pages', '9' );
		update_post_meta( $hundred, 'ct_pages', '100' );

		$ids = wp_list_pluck( $this->admin_list( [ 'orderby' => 'ct_pages', 'order' => 'ASC' ] )->posts, 'ID' );

		$this->assertContains( $none, $ids );
		$this->assertSame( [ $nine, $ten, $hundred ], array_values( array_intersect( $ids, [ $nine, $ten, $hundred ] ) ), 'as numbers: 9, 10, 100' );
	}

	public function test_an_orderby_that_is_no_column_of_ours_is_left_alone(): void {
		set_current_screen( 'edit-ct_book' );

		$query = $this->admin_list( [ 'orderby' => 'ct_stock' ] );

		$this->assertSame( 'ct_stock', $query->get( 'orderby' ) );
		$this->assertEmpty( $query->get( 'meta_query' ) );
	}
}
