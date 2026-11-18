<?php
/**
 * Registration tests.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests;

use SugarCommerce\ContentTypes\PostType;
use SugarCommerce\ContentTypes\Support\Icon;
use SugarCommerce\ContentTypes\Taxonomy;
use PHPUnit\Framework\TestCase;

/**
 * What actually reaches register_post_type() and register_taxonomy().
 *
 * Everything this library does is decide those arguments, so what it passed
 * is the whole of what there is to check — and the two things most easily got
 * wrong are passing a key WordPress does not know, which sits on the object
 * being silently ignored, and registering at a moment when the hook has
 * already run.
 */
final class RegistrationTest extends TestCase {

	/**
	 * Forget the last test's registrations.
	 */
	protected function setUp(): void {
		ct_reset_globals();
	}

	/**
	 * Register a post type and hand back what WordPress was given.
	 *
	 * @param array<string, mixed> $config Its configuration.
	 * @param string               $slug   The slug.
	 *
	 * @return array<string, mixed>
	 */
	private function registered( array $config = [], string $slug = 'book' ): array {
		$GLOBALS['ct_did']['init'] = 1;

		PostType::register( $slug, $config );

		return $GLOBALS['ct_post_types'][ $slug ] ?? [];
	}

	/**
	 * A post type reaches WordPress with its labels filled in.
	 */
	public function test_a_post_type_is_registered_with_its_labels(): void {
		$args = $this->registered( [ 'singular' => 'Book' ] );

		$this->assertSame( 'Add New Book', $args['labels']['add_new_item'] );
		$this->assertTrue( $args['public'] );
	}

	/**
	 * The library's own keys do not reach WordPress.
	 *
	 * register_post_type() puts every key it is given onto the post type
	 * object, so a key it does not know sits there for anything reading the
	 * object to find and wonder about.
	 */
	public function test_the_librarys_own_keys_are_not_passed_through(): void {
		$args = $this->registered(
			[
				'singular'    => 'Book',
				'plural'      => 'Books',
				'icon'        => 'book-alt',
				'text_domain' => 'my-plugin',
			]
		);

		foreach ( [ 'singular', 'plural', 'icon', 'text_domain' ] as $key ) {
			$this->assertArrayNotHasKey( $key, $args, sprintf( '%s was passed to register_post_type().', $key ) );
		}

		// And the one it turns into.
		$this->assertSame( 'dashicons-book-alt', $args['menu_icon'] );
	}

	/**
	 * A name is guessed from the slug when none is given.
	 *
	 * `product-brand` becomes "Product Brand", which is a guess — and a
	 * better one than "Posts".
	 */
	public function test_a_name_is_guessed_from_the_slug(): void {
		$args = $this->registered( [], 'product_brand' );

		$this->assertSame( 'Product Brand', $args['labels']['singular_name'] );
		$this->assertSame( 'Product Brands', $args['labels']['name'] );
	}

	/**
	 * A taxonomy declared with a post type is registered and attached to it.
	 *
	 * The reason to write it there rather than register it separately.
	 */
	public function test_a_taxonomy_declared_with_a_post_type_is_attached(): void {
		$this->registered(
			[
				'singular'   => 'Book',
				'taxonomies' => [ 'genre' => [ 'singular' => 'Genre', 'hierarchical' => true ] ],
			]
		);

		$genre = $GLOBALS['ct_taxonomies']['genre'] ?? [];

		$this->assertSame( [ 'book' ], $genre['objects'] );
		$this->assertSame( 'Parent Genre', $genre['labels']['parent_item'] );

		// And the post type knows about it, which is what puts it in the
		// REST response and the block editor.
		$this->assertSame( [ 'genre' ], $GLOBALS['ct_post_types']['book']['taxonomies'] );
	}

	/**
	 * A taxonomy named but not described is passed through as a slug.
	 *
	 * Which is what register_post_type() has always taken, for a taxonomy
	 * that already exists.
	 */
	public function test_an_existing_taxonomy_can_be_named(): void {
		$this->registered( [ 'singular' => 'Book', 'taxonomies' => [ 'category', 'post_tag' ] ] );

		$this->assertSame( [ 'category', 'post_tag' ], $GLOBALS['ct_post_types']['book']['taxonomies'] );
		$this->assertSame( [], $GLOBALS['ct_taxonomies'], 'An existing taxonomy was re-registered.' );
	}

	/**
	 * The text domain reaches a taxonomy declared with the post type.
	 *
	 * Otherwise the post type's labels are translated and its taxonomy's are
	 * not, in the same plugin, from the same call.
	 */
	public function test_the_text_domain_reaches_a_declared_taxonomy(): void {
		$GLOBALS['ct_translations']['my-plugin'] = [ 'Edit %1$s' => 'Modifier %1$s' ];

		$this->registered(
			[
				'singular'    => 'Livre',
				'text_domain' => 'my-plugin',
				'taxonomies'  => [ 'genre' => [ 'singular' => 'Genre' ] ],
			]
		);

		$this->assertSame( 'Modifier Genre', $GLOBALS['ct_taxonomies']['genre']['labels']['edit_item'] );
	}

	/**
	 * Registration waits for init, and does not wait if init has gone.
	 *
	 * Both halves matter. Registering before init does not work; deferring
	 * to a hook that has already fired never runs at all, and that is what
	 * happens to anything registered from inside init itself.
	 */
	public function test_registration_is_hooked_or_immediate(): void {
		$GLOBALS['ct_did']['init'] = 0;

		PostType::register( 'early', [ 'singular' => 'Early' ] );

		$this->assertArrayHasKey( 'init', $GLOBALS['ct_hooks'] );
		$this->assertSame( [], $GLOBALS['ct_post_types'], 'It registered before init.' );

		$GLOBALS['ct_did']['init'] = 1;

		PostType::register( 'late', [ 'singular' => 'Late' ] );

		$this->assertArrayHasKey( 'late', $GLOBALS['ct_post_types'], 'It deferred to a hook that had already run.' );
	}

	/**
	 * Built, not registered: the constructor resolves and stops there, so a
	 * post type can be inspected -- its labels, say -- without registering it.
	 */
	public function test_constructing_does_not_register(): void {
		$GLOBALS['ct_did']['init'] = 1;

		$type = new PostType( 'book', [ 'singular' => 'Book' ] );
		$tax  = new Taxonomy( 'genre', [ 'singular' => 'Genre' ] );

		$this->assertSame( [], $GLOBALS['ct_post_types'] );
		$this->assertSame( [], $GLOBALS['ct_taxonomies'] );
		$this->assertFalse( $type->registered() );
		$this->assertSame( 'Add New Book', $type->labels()['add_new_item'] );
		$this->assertSame( 'Edit Genre', $tax->labels()['edit_item'] );
	}

	/**
	 * Handed to WordPress once, however often init is run at it, and its
	 * declared taxonomies with it.
	 */
	public function test_it_registers_once(): void {
		$GLOBALS['ct_did']['init'] = 0;

		$type = PostType::register( 'book', [ 'singular' => 'Book', 'taxonomies' => [ 'genre' => [] ] ] );

		$this->assertFalse( $type->registered() );

		// Init running: did_action() already counts it.
		$GLOBALS['ct_did']['init'] = 1;
		$type->on_init();
		$GLOBALS['ct_post_types'] = [];
		$type->on_init();

		$this->assertTrue( $type->registered() );
		$this->assertSame( [], $GLOBALS['ct_post_types'], 'It registered twice.' );
		$this->assertSame( [ 'genre' ], array_keys( $type->taxonomies() ) );
		$this->assertTrue( $type->taxonomies()['genre']->registered() );
		$this->assertSame( 'book', $type->slug() );
	}

	/**
	 * A hierarchical taxonomy gets a meta box that can express hierarchy.
	 *
	 * Without it, a hierarchical taxonomy renders the tag box — a free-text
	 * field with no parents in it — which is the commonest way a custom
	 * taxonomy comes out wrong.
	 */
	public function test_a_hierarchical_taxonomy_gets_the_right_meta_box(): void {
		$GLOBALS['ct_did']['init'] = 1;

		Taxonomy::register( 'genre', [ 'singular' => 'Genre', 'hierarchical' => true ] );
		Taxonomy::register( 'mood', [ 'singular' => 'Mood' ] );

		$this->assertSame( 'post_categories_meta_box', $GLOBALS['ct_taxonomies']['genre']['meta_box_cb'] );
		$this->assertArrayNotHasKey( 'meta_box_cb', $GLOBALS['ct_taxonomies']['mood'] );
	}

	/**
	 * An icon shortcut resolves; anything already valid is left alone.
	 *
	 * @dataProvider iconProvider
	 *
	 * @param string $given    What was configured.
	 * @param string $expected What menu_icon should receive.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'iconProvider' )]
	public function test_an_icon_is_resolved( string $given, string $expected ): void {
		$this->assertSame( $expected, Icon::resolve( $given ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function iconProvider(): array {
		return [
			'a shortcut'          => [ 'cart', 'dashicons-cart' ],
			'a dashicon name'     => [ 'admin-users', 'dashicons-admin-users' ],
			'already prefixed'    => [ 'dashicons-book', 'dashicons-book' ],
			'a URL'               => [ 'https://example.test/icon.svg', 'https://example.test/icon.svg' ],
			'a data URI'          => [ 'data:image/svg+xml;base64,PHN2Zz4=', 'data:image/svg+xml;base64,PHN2Zz4=' ],

			// Checked before the shortcut table, or a file called book.svg
			// would be mistaken for the word.
			'a file that is a word' => [ 'book.svg', 'book.svg' ],
			'nothing'             => [ '', '' ],
		];
	}

	/**
	 * A named menu position becomes the number WordPress wants.
	 */
	/**
	 * Every named position, one past core's own item.
	 */
	public function test_every_named_menu_position(): void {
		$expected = [
			'dashboard'  => 3,
			'Posts'      => 6,
			'media'      => 11,
			'pages'      => 21,
			'comments'   => 26,
			'appearance' => 61,
			'plugins'    => 66,
			'users'      => 71,
			'tools'      => 76,
			'settings'   => 81,
			'nowhere'    => null,
		];

		foreach ( $expected as $name => $position ) {
			$this->assertSame( $position, ( new PostType( 'x', [ 'menu_position' => $name ] ) )->config()['menu_position'], $name );
		}
	}

	/**
	 * A taxonomy on its own waits for init, registers at once after it, and
	 * reaches WordPress once and without this library's own keys.
	 */
	public function test_a_taxonomy_on_its_own(): void {
		$GLOBALS['ct_did']['init'] = 0;

		$tax = Taxonomy::register( 'genre', [ 'singular' => 'Genre', 'plural' => 'Genres', 'objects' => [ 'book' ], 'text_domain' => 'mine' ] );

		$this->assertSame( [], $GLOBALS['ct_taxonomies'], 'It registered before init.' );
		$this->assertSame( [ [ $tax, 'on_init' ] ], $GLOBALS['ct_hooks']['init'] );

		$GLOBALS['ct_did']['init'] = 1;
		$tax->on_init();
		$args = $GLOBALS['ct_taxonomies']['genre'];
		$GLOBALS['ct_taxonomies'] = [];
		$tax->on_init();

		$this->assertSame( [], $GLOBALS['ct_taxonomies'], 'It registered twice.' );
		$this->assertSame( [ 'book' ], $args['objects'] );

		foreach ( [ 'singular', 'plural', 'text_domain' ] as $key ) {
			$this->assertArrayNotHasKey( $key, $args, sprintf( '%s was passed to register_taxonomy().', $key ) );
		}

		Taxonomy::register( 'mood', [ 'singular' => 'Mood' ] );

		$this->assertArrayHasKey( 'mood', $GLOBALS['ct_taxonomies'], 'It deferred to a hook that had already run.' );
		$this->assertSame( [ 'post' ], $GLOBALS['ct_taxonomies']['mood']['objects'] );
	}

	public function test_a_named_menu_position_is_resolved(): void {
		$this->assertSame( 6, $this->registered( [ 'menu_position' => 'posts' ] )['menu_position'] );

		// And a number given outright is left alone.
		$this->assertSame( 42, $this->registered( [ 'menu_position' => 42 ], 'other' )['menu_position'] );
	}
}
