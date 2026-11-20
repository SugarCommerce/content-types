<?php
/**
 * Rewrite rules flushed once, when they change.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests\Core;

use SugarCommerce\ContentTypes\PostType;
use SugarCommerce\ContentTypes\Support\Rewrites;
use SugarCommerce\ContentTypes\Taxonomy;

/**
 * A new or changed slug flushes, an unchanged one never does.
 */
final class RewritesTest extends TestCase {

	/**
	 * Flushes seen, through core's own action.
	 *
	 * @var int
	 */
	private int $flushes = 0;

	public function set_up(): void {
		parent::set_up();

		$this->set_permalink_structure( '/%postname%/' );
		Rewrites::reset();
		$this->flushes = 0;

		add_action(
			'generate_rewrite_rules',
			function (): void {
				++$this->flushes;
			}
		);
	}

	public function tear_down(): void {
		unregister_post_type( 'ct_book' );
		unregister_taxonomy( 'ct_genre' );
		Rewrites::reset();
		parent::tear_down();
	}

	public function test_a_new_post_type_flushes_once_and_its_urls_work(): void {
		PostType::register( 'ct_book', [ 'flush_rewrite' => true, 'rewrite' => [ 'slug' => 'books' ] ] );

		$this->assertSame( 1, $this->flushes );
		$this->assertArrayHasKey( 'books/([^/]+)(?:/([0-9]+))?/?$', (array) get_option( 'rewrite_rules' ) );

		// The next request, the same settings: nothing to do.
		unregister_post_type( 'ct_book' );
		PostType::register( 'ct_book', [ 'flush_rewrite' => true, 'rewrite' => [ 'slug' => 'books' ] ] );

		$this->assertSame( 1, $this->flushes );
	}

	public function test_a_changed_slug_flushes_again(): void {
		PostType::register( 'ct_book', [ 'flush_rewrite' => true, 'rewrite' => [ 'slug' => 'books' ] ] );
		unregister_post_type( 'ct_book' );
		PostType::register( 'ct_book', [ 'flush_rewrite' => true, 'rewrite' => [ 'slug' => 'library' ] ] );

		$this->assertSame( 2, $this->flushes );
		$this->assertArrayHasKey( 'library/([^/]+)(?:/([0-9]+))?/?$', (array) get_option( 'rewrite_rules' ) );
	}

	public function test_off_by_default(): void {
		PostType::register( 'ct_book' );
		Taxonomy::register( 'ct_genre' );

		$this->assertSame( 0, $this->flushes );
		$this->assertFalse( get_option( Rewrites::OPTION ) );
	}

	public function test_a_taxonomy_flushes_too_and_reset_flushes_again(): void {
		Taxonomy::register( 'ct_genre', [ 'flush_rewrite' => true, 'rewrite' => [ 'slug' => 'genre' ] ] );

		$this->assertSame( 1, $this->flushes );

		Rewrites::reset();
		unregister_taxonomy( 'ct_genre' );
		Taxonomy::register( 'ct_genre', [ 'flush_rewrite' => true, 'rewrite' => [ 'slug' => 'genre' ] ] );

		$this->assertSame( 2, $this->flushes );
	}
}
