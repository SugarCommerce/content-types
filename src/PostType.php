<?php
/**
 * Post Type
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes;

use SugarCommerce\ContentTypes\Support\Icon;
use SugarCommerce\ContentTypes\Support\Labels;

/**
 * A post type, registered with labels that use its own name.
 *
 * Core does not substitute your name into its labels — register a post type
 * called Book and WordPress offers "Add Post" and "No posts found", because
 * those are the defaults for the post post type and it uses them as they are.
 * Filling in all thirty by hand is what nobody does, which is why every
 * hand-written custom post type has "Add Post" somewhere in it for ever.
 *
 * Everything else here is register_post_type()'s own argument list, with
 * three conveniences on top: `singular` and `plural` build the labels,
 * `icon` takes an ordinary word as well as a dashicon, and `taxonomies` may
 * be written out in full rather than registered separately — which is how a
 * post type and its taxonomies are almost always declared.
 *
 *     PostType::register( 'book', [
 *         'singular'   => __( 'Book', 'my-plugin' ),
 *         'icon'       => 'book-alt',
 *         'taxonomies' => [
 *             'genre' => [ 'singular' => __( 'Genre', 'my-plugin' ), 'hierarchical' => true ],
 *         ],
 *     ] );
 *
 * Columns are deliberately not here: a columns library does that, for every
 * kind of list table rather than only this one.
 */
final class PostType {

	/**
	 * The post type slug.
	 *
	 * @var string
	 */
	private string $post_type;

	/**
	 * The resolved configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * The taxonomies declared alongside it.
	 *
	 * @var Taxonomy[]
	 */
	private array $taxonomies = [];

	/**
	 * Whether it has been handed to WordPress.
	 *
	 * @var bool
	 */
	private bool $registered = false;

	/**
	 * Register a post type, on init.
	 *
	 * On init, because that is where post types belong -- but at once if
	 * init has been and gone, since something registering during or after
	 * init would otherwise wait for a hook that never comes.
	 *
	 * @param string               $post_type The slug.
	 * @param array<string, mixed> $config    Its configuration.
	 *
	 * @return PostType
	 */
	public static function register( string $post_type, array $config = [] ): self {
		$type = new self( $post_type, $config );

		if ( did_action( 'init' ) ) {
			$type->on_init();
		} else {
			add_action( 'init', [ $type, 'on_init' ] );
		}

		return $type;
	}

	/**
	 * A post type, resolved but not registered. register() is the usual way in.
	 *
	 * @param string               $post_type The slug.
	 * @param array<string, mixed> $config    Its configuration.
	 */
	public function __construct( string $post_type, array $config = [] ) {
		$this->post_type = $post_type;
		$this->config    = $this->parse( $config );
	}

	/**
	 * Fill in what was not given.
	 *
	 * @param array<string, mixed> $config The configuration.
	 *
	 * @return array<string, mixed>
	 */
	private function parse( array $config ): array {
		$config = array_merge(
			[
				'singular'      => '',
				'plural'        => '',
				'labels'        => [],
				'text_domain'   => '',
				'taxonomies'    => [],
				'icon'          => '',
				'public'        => true,
				'has_archive'   => true,
				'show_in_rest'  => true,
				'supports'      => [ 'title', 'editor', 'thumbnail' ],
				'menu_position' => null,
			],
			$config
		);

		if ( '' === $config['singular'] ) {
			$config['singular'] = ucwords( str_replace( [ '-', '_' ], ' ', $this->post_type ) );
		}

		$config['labels'] = Labels::post_type(
			(string) $config['singular'],
			(string) $config['plural'],
			(array) $config['labels'],
			(string) $config['text_domain']
		);

		if ( '' !== (string) $config['icon'] ) {
			$config['menu_icon'] = Icon::resolve( (string) $config['icon'] );
		}

		if ( is_string( $config['menu_position'] ) ) {
			$config['menu_position'] = self::menu_position( $config['menu_position'] );
		}

		return $config;
	}

	/**
	 * Where in the admin menu a named position sits.
	 *
	 * menu_position is an integer nobody remembers, and getting it wrong
	 * puts a post type between Media and Pages for no reason anybody can
	 * see. The names are core's own menu items.
	 *
	 * @param string $position A menu item to sit after.
	 *
	 * @return int|null
	 */
	private static function menu_position( string $position ): ?int {
		// One past core's own, so "after posts" is after Posts rather than
		// contending with it for the same slot.
		return match ( strtolower( $position ) ) {
			'dashboard'  => 3,
			'posts'      => 6,
			'media'      => 11,
			'pages'      => 21,
			'comments'   => 26,
			'appearance' => 61,
			'plugins'    => 66,
			'users'      => 71,
			'tools'      => 76,
			'settings'   => 81,
			default      => null,
		};
	}

	/**
	 * Hand it to WordPress, and register anything declared with it.
	 *
	 * Hooked onto init by register(); runs once.
	 *
	 * @return void
	 */
	public function on_init(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;

		$config     = $this->config;
		$taxonomies = (array) $config['taxonomies'];

		// Ours, not register_post_type()'s.
		unset( $config['singular'], $config['plural'], $config['icon'], $config['text_domain'], $config['taxonomies'] );

		// A list of slugs is what register_post_type() takes; anything given
		// as a configuration array is registered below and attached here.
		$config['taxonomies'] = array_map(
			static fn( $key, $value ): string => is_int( $key ) ? (string) $value : (string) $key,
			array_keys( $taxonomies ),
			$taxonomies
		);

		register_post_type( $this->post_type, $config );

		foreach ( $taxonomies as $key => $value ) {
			if ( is_int( $key ) ) {
				continue;
			}

			// Declared in full. Attached to this post type unless it says
			// otherwise, which is the only reason to write it here rather
			// than register it on its own.
			$this->taxonomies[ $key ] = Taxonomy::register(
				(string) $key,
				array_merge(
					[
						'objects'     => [ $this->post_type ],
						'text_domain' => (string) $this->config['text_domain'],
					],
					(array) $value
				)
			);
		}
	}

	/**
	 * The slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return $this->post_type;
	}

	/**
	 * The configuration as it was resolved.
	 *
	 * @return array<string, mixed>
	 */
	public function config(): array {
		return $this->config;
	}

	/**
	 * The taxonomies declared with it, by slug.
	 *
	 * @return Taxonomy[]
	 */
	public function taxonomies(): array {
		return $this->taxonomies;
	}

	/**
	 * Whether it has been handed to WordPress.
	 *
	 * @return bool
	 */
	public function registered(): bool {
		return $this->registered;
	}

	/**
	 * Its labels, as WordPress was given them.
	 *
	 * @return array<string, string>
	 */
	public function labels(): array {
		return $this->config['labels'];
	}
}
