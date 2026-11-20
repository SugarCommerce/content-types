# Content Types

Register a post type or taxonomy from its singular name, and get all
thirty-odd labels written for you.

## What it does

`register_post_type()` takes a `labels` array with more than thirty entries.
Nobody fills it in. The usual result is a plugin whose admin says "Add New
Post" on a screen for books, "No posts found" when there are no books, and
"Post updated" after saving one.

Give this the singular (and the plural, if it is irregular) and every label is
generated with the right word in it -- including the ones nobody remembers,
like the media frame's "Insert into book" and the block editor's "Book
published" toast.

## Features

* Get every label written from a singular and plural name, not just the obvious ones
* Register taxonomies alongside their post type, in the same call
* Keep the labels translatable, in your plugin's own text domain
* Pick a menu icon by an ordinary word, and a menu position by the item it follows
* Override any individual label that needs different wording
* Get a hierarchical taxonomy's checkbox list without remembering `meta_box_cb`
* Give a post type capabilities of its own, granted on activation and revoked on uninstall
* Add post statuses, REST-ready meta and sortable meta columns in the same call
* Flush rewrite rules once when a slug changes, never on every request

## Installation

```bash
composer require sugarcommerce/content-types
```

## Quick start

```php
use SugarCommerce\ContentTypes\PostType;

PostType::register( 'book', [
	'singular'   => __( 'Book', 'my-plugin' ),
	'icon'       => 'book-alt',
	'taxonomies' => [
		'genre' => [ 'singular' => __( 'Genre', 'my-plugin' ), 'hierarchical' => true ],
		'mood'  => [ 'singular' => __( 'Mood', 'my-plugin' ) ],
	],
] );
```

That is a working post type with correct labels throughout the admin, and two
taxonomies attached to it. Call it whenever suits: before `init` it waits for
`init`, during or after it registers at once.

## Post types

Everything is `register_post_type()`'s own argument list, with these on top:

| Key | Default | |
|-----|---------|---|
| `singular` | the slug, words capitalised (`product_brand` → Product Brand) | builds every label |
| `plural` | guessed: Book → Books, Box → Boxes, Story → Stories | for anything irregular, give it |
| `labels` | `[]` | any label to use as given, over the generated one |
| `text_domain` | `''` | translate the label templates in this domain (see below) |
| `icon` | | a dashicon (`cart`, `dashicons-cart`), a word (`books`, `event`, `team`, `shop`), a URL or a data URI |
| `menu_position` | | a number, or the core item to follow: `dashboard`, `posts`, `media`, `pages`, `comments`, `appearance`, `plugins`, `users`, `tools`, `settings` |
| `taxonomies` | `[]` | slugs of existing taxonomies, or configurations of new ones by slug, attached to this post type |
| `template` | | blocks a new post starts with; short names (`'heading'`, `[ 'image', [ 'align' => 'wide' ] ]`) become core's form |
| `grant_to` | `[ 'administrator' ]` | roles `grant_capabilities()` gives a custom `capability_type`'s capabilities to |
| `statuses` | `[]` | post statuses, by slug: a label, or `register_post_status()` arguments |
| `meta` | `[]` | post meta for this type, by key: `register_meta()` arguments |
| `columns` | `[]` | list-table columns showing meta, sortable |
| `flush_rewrite` | false | flush the rewrite rules once when its URL settings are new or changed |

Every other key is `register_post_type()`'s own and reaches WordPress
untouched -- `rewrite`, `capability_type`, `capabilities`, `map_meta_cap`,
`template_lock`, the `rest_*` and `*_rest_controller_class` keys,
`late_route_registration`, `embeddable`, `register_meta_box_cb` and the rest;
a suite against real core checks each one.

And these defaults differ from core's: `public`, `has_archive` and
`show_in_rest` are true, and `supports` is title, editor and thumbnail.

```php
$type = PostType::register( 'event', [ 'singular' => 'Event', 'icon' => 'event', 'menu_position' => 'posts' ] );

$type->slug();         // 'event'
$type->labels();       // what WordPress was given
$type->config();       // everything, resolved
$type->taxonomies();   // [ slug => Taxonomy ] declared with it
$type->registered();   // handed to WordPress yet
```

`new PostType( 'event', $config )` resolves without registering, to read the
labels or configuration.

### What core leaves to you

```php
use SugarCommerce\ContentTypes\PostType;
use SugarCommerce\ContentTypes\Support\Rewrites;

$config = [
	'singular'        => __( 'Book', 'my-plugin' ),
	'capability_type' => [ 'book', 'books' ],
	'map_meta_cap'    => true,
	'grant_to'        => [ 'administrator', 'editor' ],
	'statuses'        => [ 'out-of-print' => __( 'Out of Print', 'my-plugin' ) ],
	'meta'            => [
		'isbn'   => [ 'description' => 'The ISBN.' ],
		'pages'  => [ 'type' => 'integer', 'default' => 0 ],
		'awards' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
	],
	'columns'         => [
		'isbn'  => [ 'label' => __( 'ISBN', 'my-plugin' ), 'after' => 'title' ],
		'pages' => [ 'label' => __( 'Pages', 'my-plugin' ), 'numeric' => true ],
	],
	'template'        => [ 'paragraph', [ 'image', [ 'align' => 'wide' ] ] ],
	'flush_rewrite'   => true,
];

PostType::register( 'book', $config );

register_activation_hook( __FILE__, static function () use ( $config ): void {
	( new PostType( 'book', $config ) )->grant_capabilities();
	Rewrites::reset(); // the next request flushes
} );

// uninstall.php
( new PostType( 'book', $config ) )->revoke_capabilities();
```

* **Capabilities.** A `capability_type` of its own (`book`) is checked against
  `edit_books` and the rest, which WordPress gives to nobody -- not even an
  administrator can open the screen. `capabilities()` lists them as core will
  build them; `grant_capabilities( $roles )` adds them to `grant_to` (or the
  roles given), `revoke_capabilities()` takes them from every role. A
  capability core's own post types use -- `edit_posts`, or `read` -- is never
  granted or revoked, so a post type left on `post` has none to touch.
* **Statuses** get a count label for the status links ("Out of Print (3)")
  and are `protected` unless told otherwise: listed in the admin, never shown
  on the site. Core turns a status with no visibility into an internal one,
  hidden from every list. Statuses are site-wide in WordPress, so name them
  as your plugin's own.
* **Meta** is registered for this post type, single and in REST by default,
  with a schema for an `array` (`items`) or `object` (`properties`) and a
  sanitizer from the type; the post type gets `custom-fields` support, without
  which core leaves meta out of REST. Keys starting `_` are protected.
* **Columns** take `label`, `meta` (default the column's key), `sortable`
  (default on for a meta column), `numeric`, `after` and `render` (a callable
  given the post id; printed through `wp_kses_post()`). A meta value prints
  escaped; sorting keeps posts with no value. Images, links and filters are
  `sugarcommerce/columns`.
* **Rewrite rules** are flushed once, on `wp_loaded`, the first time the URL
  settings (slug, archive, query var, public) are seen or after they change.

## Taxonomies

On their own, or declared in a post type's `taxonomies` (where `objects`
defaults to that post type and the post type's `text_domain` is passed down):

```php
use SugarCommerce\ContentTypes\Taxonomy;

Taxonomy::register( 'genre', [
	'singular'     => __( 'Genre', 'my-plugin' ),
	'objects'      => [ 'book', 'film' ],
	'hierarchical' => true,
] );
```

| Key | Default | |
|-----|---------|---|
| `singular`, `plural`, `labels`, `text_domain` | as for post types | |
| `objects` | `[ 'post' ]` | the post types it is attached to |
| `hierarchical` | false | true gets the "Parent Genre" labels and, unless `meta_box_cb` is given, the checkbox list instead of the tag box |
| `public`, `show_admin_column`, `show_in_rest` | true | |
| `flush_rewrite` | false | as for post types |

Every other key is `register_taxonomy()`'s own and passes through:
`default_term`, `sort`, `args`, `update_count_callback`,
`meta_box_sanitize_cb`, `show_tagcloud`, `show_in_quick_edit`, `rewrite`,
`capabilities` and the `rest_*` keys.

A flat taxonomy gets "Separate moods with commas" and "Choose from the most
used moods"; a hierarchical one gets "Parent Genre" and "Filter by genre".

## Labels and translation

Sentences use the lower-case name ("No books found"), and a word that is not
simply Capitalised keeps its capitals ("Insert into EPUB file").

Core's default labels are translated but name the wrong thing; these name the
right thing but are English until translated. Pass `text_domain` and every
template is run through `translate()` in that domain. Extraction tools cannot
see strings that are not literals, so put the templates in your POT by
writing out `Labels::templates()` as `__( '…', 'my-plugin' )` calls in a file
your extractor reads:

```php
use SugarCommerce\ContentTypes\Support\Labels;

Labels::templates();                                   // every template, e.g. 'Add New %1$s', 'No %4$s found'
Labels::post_type( 'Book', 'Books', [], 'my-plugin' ); // the labels themselves
Labels::taxonomy( 'Genre', '', true );
Labels::pluralize( 'Story' );                          // 'Stories'
```

In a template, `%1$s` is the singular, `%2$s` the plural, `%3$s` and `%4$s`
the same in lower case. `labels` always wins over a generated label.

## Security

* Sorts a list by a column's meta only when the request names a column declared sortable here, so `orderby` cannot reach the query otherwise
* Prints a column's meta escaped, and a rendered column through `wp_kses_post()`
* Never grants or revokes `read` or a capability core's own post types use, so an uninstall cannot strip editors of `edit_posts`
* Cleans meta written outside REST by its type, and leaves `_`-prefixed keys protected
* Makes a custom status protected unless told otherwise, so it is never shown on the site by accident
* Drops anything in a block template that is not a block name

## Testing

`composer test` runs both suites: `tests/` against stubs, and `tests/Core`
against WordPress and a real database (wp-phpunit; the database name must
contain "test", and `WP_CORE_DIR` points at WordPress when the package is not
inside a site).

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-2.0-or-later
