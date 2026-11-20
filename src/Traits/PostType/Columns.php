<?php
/**
 * List-table columns that show a post's meta, sortable.
 *
 * @package   SugarCommerce\ContentTypes
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Traits\PostType;

use WP_Query;

/**
 * Trait Columns
 *
 * The one column a post type nearly always wants and core makes four hooks
 * of: a meta value in the list, sortable. For anything richer -- images,
 * links, filters, columns on other list tables -- the columns library is the
 * tool; this is the common case without it.
 *
 *     'columns' => [
 *         'isbn'  => [ 'label' => __( 'ISBN', 'my-plugin' ), 'meta' => 'isbn' ],
 *         'pages' => [ 'label' => __( 'Pages', 'my-plugin' ), 'meta' => 'pages', 'numeric' => true ],
 *         'stock' => [ 'label' => __( 'Stock', 'my-plugin' ), 'render' => fn( int $post_id ) => … ],
 *     ]
 *
 * Each column takes `label`, `meta` (the key it shows; default the column's
 * own), `sortable` (default true when there is a meta key), `numeric`
 * (sort as numbers rather than text), `after` (the column it follows;
 * default before the date) and `render` (a callable given the post id,
 * whose return is printed through wp_kses_post()). A meta value is printed
 * escaped.
 *
 * Sorting reads `orderby` from the request, so only a column declared
 * sortable here ever reaches the query -- and a post with no value for the
 * key still lists, last or first, rather than vanishing as it would with a
 * plain `meta_key`.
 *
 * @since 1.0.0
 */
trait Columns {

	/**
	 * The columns declared, resolved.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{label: string, meta: string, sortable: bool, numeric: bool, after: string, render: callable|null}>
	 */
	public function columns(): array {
		$columns = [];

		foreach ( (array) $this->config['columns'] as $key => $column ) {
			$key = sanitize_key( (string) $key );

			if ( '' === $key ) {
				continue;
			}

			$column = is_array( $column ) ? $column : [ 'label' => (string) $column ];
			$meta   = (string) ( $column['meta'] ?? $key );
			$render = $column['render'] ?? null;

			$columns[ $key ] = [
				'label'    => (string) ( $column['label'] ?? ucwords( str_replace( [ '-', '_' ], ' ', $key ) ) ),
				'meta'     => $meta,
				'sortable' => (bool) ( $column['sortable'] ?? ( '' !== $meta && null === $render ) ),
				'numeric'  => (bool) ( $column['numeric'] ?? false ),
				'after'    => (string) ( $column['after'] ?? '' ),
				'render'   => is_callable( $render ) ? $render : null,
			];
		}

		return $columns;
	}

	/**
	 * The list table's columns, with these in their places.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $columns What the list table has.
	 *
	 * @return array<string, string>
	 */
	public function add_columns( array $columns ): array {
		foreach ( $this->columns() as $key => $column ) {
			// After the column named, or before the date, or at the end.
			$after    = '' !== $column['after'] && isset( $columns[ $column['after'] ] ) ? $column['after'] : null;
			$position = null === $after
				? ( isset( $columns['date'] ) ? (int) array_search( 'date', array_keys( $columns ), true ) : count( $columns ) )
				: (int) array_search( $after, array_keys( $columns ), true ) + 1;

			$columns = array_slice( $columns, 0, $position, true ) + [ $key => $column['label'] ] + array_slice( $columns, $position, null, true );
		}

		return $columns;
	}

	/**
	 * Print one of these columns for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column  The column.
	 * @param int    $post_id The post.
	 *
	 * @return void
	 */
	public function render_column( string $column, int $post_id ): void {
		$columns = $this->columns();

		if ( ! isset( $columns[ $column ] ) ) {
			return;
		}

		if ( null !== $columns[ $column ]['render'] ) {
			echo wp_kses_post( (string) call_user_func( $columns[ $column ]['render'], $post_id ) );

			return;
		}

		$value = get_post_meta( $post_id, $columns[ $column ]['meta'], true );
		$value = is_scalar( $value ) ? (string) $value : '';

		echo '' === $value ? '<span aria-hidden="true">&#8212;</span>' : esc_html( $value );
	}

	/**
	 * The sortable columns, these added.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $sortable What the list table has.
	 *
	 * @return array<string, mixed>
	 */
	public function sortable_columns( array $sortable ): array {
		foreach ( $this->columns() as $key => $column ) {
			if ( $column['sortable'] ) {
				$sortable[ $key ] = $key;
			}
		}

		return $sortable;
	}

	/**
	 * Sort the admin list by a column's meta, when it asked to.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query about to run.
	 *
	 * @return void
	 */
	public function sort_by_column( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || $this->post_type !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		$columns = $this->columns();

		// Only a column declared sortable: the value is from the request.
		if ( ! is_string( $orderby ) || ! isset( $columns[ $orderby ] ) || ! $columns[ $orderby ]['sortable'] ) {
			return;
		}

		$clause = [
			'key'     => $columns[ $orderby ]['meta'],
			'compare' => 'EXISTS',
		];

		if ( $columns[ $orderby ]['numeric'] ) {
			$clause['type'] = 'NUMERIC';
		}

		// EXISTS or NOT EXISTS, so a post with no value is still listed; the
		// named clause is what is sorted on.
		$query->set(
			'meta_query',
			[
				'relation'         => 'OR',
				'sugarcommerce_ct' => $clause,
				[
					'key'     => $columns[ $orderby ]['meta'],
					'compare' => 'NOT EXISTS',
				],
			]
		);
		$query->set( 'orderby', 'sugarcommerce_ct' );
	}

	/**
	 * Hook the columns in. Part of on_init().
	 *
	 * @return void
	 */
	private function hook_columns(): void {
		if ( [] === $this->columns() ) {
			return;
		}

		add_filter( "manage_{$this->post_type}_posts_columns", [ $this, 'add_columns' ] );
		add_action( "manage_{$this->post_type}_posts_custom_column", [ $this, 'render_column' ], 10, 2 );
		add_filter( "manage_edit-{$this->post_type}_sortable_columns", [ $this, 'sortable_columns' ] );
		add_action( 'pre_get_posts', [ $this, 'sort_by_column' ] );
	}
}
