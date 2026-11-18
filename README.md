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

## Moving from arraypress/wp-*

From `arraypress/wp-register-content-types` (`ArrayPress\RegisterContentTypes`).
The global functions are gone -- a library bundled into several plugins must
not declare any -- and each class has a static `register()`.

| Old | New |
|-----|-----|
| `register_content_type( 'book', $config )` | `PostType::register( 'book', $config )` |
| `register_content_taxonomy( 'genre', $config )` | `Taxonomy::register( 'genre', $config )` |
| `new PostType( … )` (registered on init) | `PostType::register( … )`; `new PostType( … )` now only resolves |
| `new Taxonomy( … )` (registered on init) | `Taxonomy::register( … )`; `new Taxonomy( … )` now only resolves |
| `$type->register()`, `$taxonomy->register()` (the init callback) | `->on_init()`, which now runs once |
| `ArrayPress\RegisterContentTypes\Support\Labels`, `Support\Icon` | `SugarCommerce\ContentTypes\Support\Labels`, `Support\Icon` |
| "No Product Brands found" for a multi-word name | "No product brands found": each word is lowered on its own |
| — | `->labels()`, `->registered()`, `Labels::templates()` |

Every configuration key, default, label and icon shortcut is unchanged.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-2.0-or-later
