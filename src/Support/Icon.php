<?php
/**
 * Icon
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Support;

/**
 * What to put in the admin menu beside a post type.
 *
 * `menu_icon` takes a dashicon class, a URL or a base64 SVG, and nothing
 * tells you which dashicon you want — the names are not the words you would
 * search for. `products` is a shopping bag and `cart` is a trolley; `book` is
 * a closed book and `book-alt` an open one. So a handful of ordinary words
 * are mapped to the dashicon somebody asking for that word probably meant,
 * and anything unrecognised is passed through with the prefix added.
 *
 * Nothing here is validated against the dashicons that exist. A name that is
 * not one renders as an empty square, which is visible immediately and needs
 * no library to diagnose — and a list of every dashicon would be out of date
 * by the next release.
 */
final class Icon {

	/**
	 * Ordinary words that are *not* dashicon names, and what each one means.
	 *
	 * Only those. Every real dashicon already resolves through the fallback
	 * at the bottom of resolve(), so `books` is here because there is no
	 * `dashicons-books`, and `cart` is not because `dashicons-cart` exists
	 * and the fallback finds it. Listing the other three hundred and fifty
	 * would be three hundred and fifty lines doing what one line already
	 * does, and would be out of date the next time core adds one.
	 *
	 * Nothing here may shadow a real dashicon name. `calendar` used to, and
	 * quietly handed back `dashicons-calendar-alt` to somebody who had asked
	 * for the icon that exists and is called `calendar`. A test holds the
	 * line, against the list core actually ships.
	 *
	 * @var array<string, string>
	 */
	private const array SHORTCUTS = [
		'document'  => 'dashicons-media-document',
		'docs'      => 'dashicons-media-document',
		'file'      => 'dashicons-media-document',
		'shop'      => 'dashicons-store',
		'product'   => 'dashicons-products',
		'image'     => 'dashicons-format-image',
		'images'    => 'dashicons-format-gallery',
		'gallery'   => 'dashicons-format-gallery',
		'video'     => 'dashicons-format-video',
		'audio'     => 'dashicons-format-audio',
		'books'     => 'dashicons-book-alt',
		'event'     => 'dashicons-calendar',
		'events'    => 'dashicons-calendar',
		'map'       => 'dashicons-location-alt',
		'team'      => 'dashicons-groups',
		'people'    => 'dashicons-groups',
		'users'     => 'dashicons-admin-users',
		'star'      => 'dashicons-star-filled',
		'mail'      => 'dashicons-email-alt',
		'comment'   => 'dashicons-admin-comments',
		'comments'  => 'dashicons-admin-comments',
		'tags'      => 'dashicons-tagcloud',
		'post'      => 'dashicons-admin-post',
		'page'      => 'dashicons-admin-page',
		'media'     => 'dashicons-admin-media',
		'link'      => 'dashicons-admin-links',
	];

	/**
	 * Resolve whatever was configured into something menu_icon takes.
	 *
	 * @param string $icon A dashicon, a shortcut, a URL or a data URI.
	 *
	 * @return string
	 */
	public static function resolve( string $icon ): string {
		$icon = trim( $icon );

		if ( '' === $icon ) {
			return '';
		}

		// Already what WordPress wants.
		if ( str_starts_with( $icon, 'dashicons-' ) || str_starts_with( $icon, 'data:image' ) ) {
			return $icon;
		}

		// A path or a URL. Checked before the shortcut table so a file called
		// book.svg is not mistaken for the word.
		if ( str_contains( $icon, '/' ) || str_contains( $icon, '.' ) ) {
			return $icon;
		}

		return self::SHORTCUTS[ strtolower( $icon ) ] ?? 'dashicons-' . $icon;
	}

	/**
	 * The words that resolve to something.
	 *
	 * @return string[]
	 */
	public static function shortcuts(): array {
		return array_keys( self::SHORTCUTS );
	}
}
