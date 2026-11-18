<?php
/**
 * Taxonomy
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes;

use SugarCommerce\ContentTypes\Support\Labels;

/**
 * A taxonomy, registered with labels that use its own name.
 *
 * Everything here is register_taxonomy()'s own argument list, with three
 * differences: `singular` and `plural` generate the labels, `objects` is
 * whatever the taxonomy is attached to, and the defaults are the ones a
 * custom taxonomy usually wants rather than core's.
 *
 *     Taxonomy::register( 'genre', [
 *         'singular'     => __( 'Genre', 'my-plugin' ),
 *         'objects'      => [ 'book' ],
 *         'hierarchical' => true,
 *     ] );
 */
final class Taxonomy {

	/**
	 * The taxonomy slug.
	 *
	 * @var string
	 */
	private string $taxonomy;

	/**
	 * The resolved configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Whether it has been handed to WordPress.
	 *
	 * @var bool
	 */
	private bool $registered = false;

	/**
	 * Register a taxonomy, on init.
	 *
	 * On init, because that is where taxonomies belong and registering
	 * earlier is not possible -- but at once if init has been and gone, since
	 * something registering during or after init would otherwise attach to a
	 * hook that has already run and never fire.
	 *
	 * @param string               $taxonomy The slug.
	 * @param array<string, mixed> $config   Its configuration.
	 *
	 * @return Taxonomy
	 */
	public static function register( string $taxonomy, array $config = [] ): self {
		$tax = new self( $taxonomy, $config );

		if ( did_action( 'init' ) ) {
			$tax->on_init();
		} else {
			add_action( 'init', [ $tax, 'on_init' ] );
		}

		return $tax;
	}

	/**
	 * A taxonomy, resolved but not registered. register() is the usual way in.
	 *
	 * @param string               $taxonomy The slug.
	 * @param array<string, mixed> $config   Its configuration.
	 */
	public function __construct( string $taxonomy, array $config = [] ) {
		$this->taxonomy = $taxonomy;
		$this->config   = $this->parse( $config );
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
				'singular'          => '',
				'plural'            => '',
				'objects'           => [ 'post' ],
				'labels'            => [],
				'text_domain'       => '',
				'hierarchical'      => false,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
			],
			$config
		);

		// A name to build labels from. Without one the slug is all there is,
		// so `product-brand` becomes "Product Brand" — which is a guess, and
		// a better one than "Categories".
		if ( '' === $config['singular'] ) {
			$config['singular'] = ucwords( str_replace( [ '-', '_' ], ' ', $this->taxonomy ) );
		}

		$config['labels'] = Labels::taxonomy(
			(string) $config['singular'],
			(string) $config['plural'],
			(bool) $config['hierarchical'],
			(array) $config['labels'],
			(string) $config['text_domain']
		);

		// A hierarchical taxonomy without this gets a tag box that cannot
		// express hierarchy, which is the single most common way a custom
		// taxonomy comes out wrong.
		if ( ! isset( $config['meta_box_cb'] ) && $config['hierarchical'] ) {
			$config['meta_box_cb'] = 'post_categories_meta_box';
		}

		return $config;
	}

	/**
	 * Hand it to WordPress.
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

		$config = $this->config;

		// Ours, not register_taxonomy()'s. Passing them through would put
		// unknown keys on the taxonomy object, where anything reading it
		// would find them and have to decide what they mean.
		$objects = (array) $config['objects'];

		unset( $config['singular'], $config['plural'], $config['objects'], $config['text_domain'] );

		register_taxonomy( $this->taxonomy, $objects, $config );
	}

	/**
	 * The slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return $this->taxonomy;
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
