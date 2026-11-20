<?php
/**
 * Labels
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Support;

/**
 * Every label a post type or taxonomy takes, from a singular and a plural.
 *
 * This is the whole reason to wrap register_post_type(). Core does not
 * substitute your name into its labels — register a post type called Book and
 * WordPress gives you "Add Post", "Edit Post", "Search Posts" and "No posts
 * found", because those are the defaults for the *post* post type and it
 * simply uses them. Nobody fills in all thirty by hand, so every custom post
 * type written the plain way has "Add Post" somewhere in it for ever.
 *
 * ## Translation
 *
 * The one real cost of generating labels rather than writing them out. Core's
 * defaults are wrong for your content but they are translated; these are
 * right for your content and, by default, English.
 *
 * So `text_domain` is honoured: give one and every template below is passed
 * through translate() against it, which means a plugin that puts these
 * strings in its own POT file gets them in every language it ships.
 * templates() lists them, to be written out for the POT.
 *
 * Give none and they come out in English, which is still better than "Add
 * Post" on an English site and no worse anywhere else — the strings a reader
 * sees are wrong either way, and at least these name the right thing.
 *
 * And `labels` always wins. A plugin that would rather write its own thirty
 * translated strings can, and this gets out of the way.
 */
final class Labels {

	/**
	 * The label templates for a post type.
	 *
	 * `%1$s` is the singular, `%2$s` the plural, `%3$s` the singular in lower
	 * case, `%4$s` the plural in lower case. Every one is numbered even where
	 * only one is used, because a translator reordering them is the point of
	 * numbering and a mixed convention is how that breaks.
	 *
	 * @var array<string, string>
	 */
	private const array POST_TYPE = [
		'name'                     => '%2$s',
		'singular_name'            => '%1$s',
		'add_new'                  => 'Add New %1$s',
		'add_new_item'             => 'Add New %1$s',
		'edit_item'                => 'Edit %1$s',
		'new_item'                 => 'New %1$s',
		'view_item'                => 'View %1$s',
		'view_items'               => 'View %2$s',
		'search_items'             => 'Search %2$s',
		'not_found'                => 'No %4$s found',
		'not_found_in_trash'       => 'No %4$s found in Trash',
		'parent_item_colon'        => 'Parent %1$s:',
		'all_items'                => 'All %2$s',
		'archives'                 => '%1$s Archives',
		'attributes'               => '%1$s Attributes',
		'insert_into_item'         => 'Insert into %3$s',
		'uploaded_to_this_item'    => 'Uploaded to this %3$s',
		'featured_image'           => 'Featured Image',
		'set_featured_image'       => 'Set featured image',
		'remove_featured_image'    => 'Remove featured image',
		'use_featured_image'       => 'Use as featured image',
		'menu_name'                => '%2$s',
		'name_admin_bar'           => '%1$s',
		'filter_items_list'        => 'Filter %4$s list',
		'items_list_navigation'    => '%2$s list navigation',
		'items_list'               => '%2$s list',
		'item_published'           => '%1$s published',
		'item_published_privately' => '%1$s published privately',
		'item_reverted_to_draft'   => '%1$s reverted to draft',
		'item_scheduled'           => '%1$s scheduled',
		'item_updated'             => '%1$s updated',
		'item_link'                => '%1$s Link',
		'item_link_description'    => 'A link to a %3$s.',
		'item_trashed'             => '%1$s trashed',
		'filter_by_date'           => 'Filter by date',
		'template_name'            => 'Single item: %1$s',
	];

	/**
	 * The label templates a flat taxonomy takes.
	 *
	 * @var array<string, string>
	 */
	private const array TAXONOMY = [
		'name'                       => '%2$s',
		'singular_name'              => '%1$s',
		'search_items'               => 'Search %2$s',
		'popular_items'              => 'Popular %2$s',
		'all_items'                  => 'All %2$s',
		// Core falls back to all_items for a taxonomy; said outright, so it
		// is in the POT with the rest.
		'archives'                   => 'All %2$s',
		'edit_item'                  => 'Edit %1$s',
		'view_item'                  => 'View %1$s',
		'update_item'                => 'Update %1$s',
		'add_new_item'               => 'Add New %1$s',
		'new_item_name'              => 'New %1$s Name',
		'separate_items_with_commas' => 'Separate %4$s with commas',
		'add_or_remove_items'        => 'Add or remove %4$s',
		'choose_from_most_used'      => 'Choose from the most used %4$s',
		'not_found'                  => 'No %4$s found',
		'no_terms'                   => 'No %4$s',
		'items_list_navigation'      => '%2$s list navigation',
		'items_list'                 => '%2$s list',
		'back_to_items'              => '&larr; Go to %2$s',
		'menu_name'                  => '%2$s',
		'name_admin_bar'             => '%1$s',
		'most_used'                  => 'Most Used',
		// An archive, not a single item. Core's own fallback for a taxonomy
		// template is "%s Archives"; "Single item:" is the post type's
		// wording, and using it here mislabels the template in the site
		// editor's list.
		'template_name'              => '%1$s Archives',
		'item_link'                  => '%1$s Link',
		'item_link_description'      => 'A link to a %3$s.',

		// The help text under each field on the term editor. Core's are
		// written for categories and tags — "The name is how it appears on
		// your site" — which is right, and saying %3$s instead of "term"
		// makes them right about this taxonomy.
		'name_field_description'     => 'The name is how it appears on your site.',
		'slug_field_description'     => 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lower case and contains only letters, numbers, and hyphens.',
		'desc_field_description'     => 'The description is not prominent by default; however, some themes may show it.',
	];

	/**
	 * The labels only a hierarchical taxonomy has.
	 *
	 * A tag has no parent, so "Parent Category" would be a control that makes
	 * no sense — which is why core keeps two sets and why this does too.
	 *
	 * @var array<string, string>
	 */
	private const array HIERARCHICAL = [
		'parent_item'              => 'Parent %1$s',
		'parent_item_colon'        => 'Parent %1$s:',
		'filter_by_item'           => 'Filter by %3$s',
		'parent_field_description' => 'Assign a parent %3$s to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.',
	];

	/**
	 * The labels only a flat taxonomy has.
	 *
	 * @var array<string, string>
	 */
	private const array FLAT = [
		'popular_items'              => 'Popular %2$s',
		'separate_items_with_commas' => 'Separate %4$s with commas',
		'add_or_remove_items'        => 'Add or remove %4$s',
		'choose_from_most_used'      => 'Choose from the most used %4$s',
	];

	/**
	 * Every template, once each, for a plugin's translation file.
	 *
	 * Neither the strings nor the domain are literals where they are
	 * translated, so no extraction tool finds them. A plugin that wants the
	 * labels translated writes these out as `__( '…', 'its-domain' )` calls
	 * in a file the extractor reads, and passes `text_domain`.
	 *
	 * @return string[]
	 */
	public static function templates(): array {
		return array_values( array_unique( array_merge( array_values( self::POST_TYPE ), array_values( self::TAXONOMY ), array_values( self::HIERARCHICAL ), array_values( self::FLAT ) ) ) );
	}

	/**
	 * Labels for a post type.
	 *
	 * @param string                $singular    'Book'.
	 * @param string                $plural      'Books'. Worked out if omitted.
	 * @param array<string, string> $overrides   Labels to use as given.
	 * @param string                $text_domain The consumer's text domain.
	 *
	 * @return array<string, string>
	 */
	public static function post_type( string $singular, string $plural = '', array $overrides = [], string $text_domain = '' ): array {
		return self::build( self::POST_TYPE, $singular, $plural, $overrides, $text_domain );
	}

	/**
	 * Labels for a taxonomy.
	 *
	 * @param string                $singular     'Genre'.
	 * @param string                $plural       'Genres'. Worked out if omitted.
	 * @param bool                  $hierarchical Whether it has parents.
	 * @param array<string, string> $overrides    Labels to use as given.
	 * @param string                $text_domain  The consumer's text domain.
	 *
	 * @return array<string, string>
	 */
	public static function taxonomy( string $singular, string $plural = '', bool $hierarchical = false, array $overrides = [], string $text_domain = '' ): array {
		$templates = self::TAXONOMY;

		foreach ( array_keys( $hierarchical ? self::FLAT : self::HIERARCHICAL ) as $unwanted ) {
			unset( $templates[ $unwanted ] );
		}

		$templates = array_merge( $templates, $hierarchical ? self::HIERARCHICAL : self::FLAT );

		return self::build( $templates, $singular, $plural, $overrides, $text_domain );
	}

	/**
	 * Fill a set of templates in.
	 *
	 * @param array<string, string> $templates   The templates.
	 * @param string                $singular    Singular form.
	 * @param string                $plural      Plural form.
	 * @param array<string, string> $overrides   Labels to use as given.
	 * @param string                $text_domain The consumer's text domain.
	 *
	 * @return array<string, string>
	 */
	private static function build( array $templates, string $singular, string $plural, array $overrides, string $text_domain ): array {
		$plural = '' === $plural ? self::pluralize( $singular ) : $plural;

		$labels = [];

		foreach ( $templates as $key => $template ) {
			// translate() with two variables, which WordPress's own coding
			// standard objects to and is right to: no extraction tool can
			// find these strings, because neither the string nor the domain
			// is a literal at this point.
			//
			// That is the whole trade, and it is made here in the open. A
			// plugin that writes templates() out for its own POT gets every label in every language it ships. A plugin
			// that does not gets them in English — which is what it had
			// before, and still better than core's "Add Post" for a post
			// type called Book.
			//
			if ( '' !== $text_domain && function_exists( 'translate' ) ) {
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain, WordPress.WP.I18n.LowLevelTranslationFunction
				$template = translate( $template, $text_domain );
			}

			$labels[ $key ] = sprintf(
				$template,
				$singular,
				$plural,
				self::lower( $singular ),
				self::lower( $plural )
			);
		}

		return array_merge( $labels, $overrides );
	}

	/**
	 * Lower case, without breaking a name that is capitalised on purpose.
	 *
	 * "Insert into ebook" is right and "insert into EPUB" is not, so a word
	 * that is not simply Capitalised is left as it is.
	 *
	 * Word by word. Taken whole, "Product Brand" -- which is what a slug of
	 * `product-brand` becomes -- was not "simply Capitalised" and came out
	 * as "No Product Brands found"; now it is "No product brands found",
	 * and "EPUB File" is "EPUB file".
	 *
	 * @param string $text The word or phrase.
	 *
	 * @return string
	 */
	private static function lower( string $text ): string {
		return implode(
			' ',
			array_map(
				static fn( string $word ): string => $word === ucfirst( strtolower( $word ) ) ? strtolower( $word ) : $word,
				explode( ' ', $text )
			)
		);
	}

	/**
	 * A plural, guessed.
	 *
	 * Deliberately the three rules that cover ordinary English nouns and no
	 * more. It is a convenience, not a linguist — anything irregular is one
	 * argument away, and a longer table of exceptions would only be wrong
	 * more confidently.
	 *
	 * @param string $singular The singular form.
	 *
	 * @return string
	 */
	public static function pluralize( string $singular ): string {
		if ( preg_match( '/(s|ss|sh|ch|x|z)$/i', $singular ) ) {
			return $singular . 'es';
		}

		if ( preg_match( '/([^aeiou])y$/i', $singular ) ) {
			return (string) preg_replace( '/y$/i', 'ies', $singular );
		}

		return $singular . 's';
	}
}
