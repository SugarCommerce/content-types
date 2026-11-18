<?php
/**
 * Label tests.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests;

use SugarCommerce\ContentTypes\Support\Labels;
use PHPUnit\Framework\TestCase;

/**
 * Labels that use the name of the thing they are about.
 *
 * The whole reason this library exists. Core does not substitute your name
 * into its labels: register a post type called Book and WordPress offers
 * "Add Post", "Edit Post", "Search Posts" and "No posts found", because those
 * are the defaults for the post post type and it uses them as they are. There
 * is no warning, and no plugin author fills in all thirty-six by hand — which
 * is why nearly every hand-written custom post type has "Add Post" somewhere
 * in it for ever.
 */
final class LabelsTest extends TestCase {

	/**
	 * Forget the last test's translations.
	 */
	protected function setUp(): void {
		ct_reset_globals();
	}

	/**
	 * The name reaches the labels that mention it.
	 */
	public function test_a_post_type_is_named_in_its_labels(): void {
		$labels = Labels::post_type( 'Book', 'Books' );

		$this->assertSame( 'Books', $labels['name'] );
		$this->assertSame( 'Book', $labels['singular_name'] );
		$this->assertSame( 'Add New Book', $labels['add_new_item'] );
		$this->assertSame( 'Search Books', $labels['search_items'] );
		$this->assertSame( 'All Books', $labels['all_items'] );
	}

	/**
	 * Sentences use the lower-case form.
	 *
	 * "No books found" reads; "No Books found" does not.
	 */
	/**
	 * Every template is listed once for a translation file, and every label
	 * a template produces is one of them.
	 */
	public function test_the_templates_are_listed_for_translation(): void {
		$templates = Labels::templates();

		$this->assertSame( $templates, array_values( array_unique( $templates ) ) );
		$this->assertContains( 'Add New %1$s', $templates );
		$this->assertContains( 'Parent %1$s', $templates );
		$this->assertContains( 'Separate %4$s with commas', $templates );

		$GLOBALS['ct_translations']['mine'] = array_combine( $templates, array_map( static fn( string $t ): string => '[' . $t . ']', $templates ) );

		foreach ( [ Labels::post_type( 'B', 'Bs', [], 'mine' ), Labels::taxonomy( 'G', 'Gs', true, [], 'mine' ), Labels::taxonomy( 'G', 'Gs', false, [], 'mine' ) ] as $labels ) {
			foreach ( $labels as $key => $label ) {
				$this->assertStringStartsWith( '[', $label, $key . ' came from a template that is not listed.' );
			}
		}
	}

	public function test_a_sentence_uses_the_lower_case_form(): void {
		$labels = Labels::post_type( 'Book', 'Books' );

		$this->assertSame( 'No books found', $labels['not_found'] );
		$this->assertSame( 'Insert into book', $labels['insert_into_item'] );
	}

	/**
	 * A name capitalised on purpose keeps its capitals.
	 *
	 * "Insert into EPUB", not "insert into epub". Lower-casing a word that
	 * was not simply Capitalised destroys something the author meant.
	 */
	public function test_a_deliberate_capital_survives(): void {
		$labels = Labels::post_type( 'EPUB', 'EPUBs' );

		$this->assertSame( 'Insert into EPUB', $labels['insert_into_item'] );
		$this->assertSame( 'No EPUBs found', $labels['not_found'] );
	}

	/**
	 * Each word is judged on its own, so a name of several Capitalised words
	 * -- every name guessed from a slug -- reads as a sentence too.
	 */
	public function test_each_word_is_lowered_on_its_own(): void {
		$this->assertSame( 'No product brands found', Labels::post_type( 'Product Brand' )['not_found'] );
		$this->assertSame( 'Insert into EPUB file', Labels::post_type( 'EPUB File' )['insert_into_item'] );
		$this->assertSame( 'Insert into iPhone case', Labels::post_type( 'iPhone Case' )['insert_into_item'] );
	}

	/**
	 * A plural is guessed when one is not given.
	 *
	 * @dataProvider pluralProvider
	 *
	 * @param string $singular The singular.
	 * @param string $expected The plural it should guess.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'pluralProvider' )]
	public function test_a_plural_is_guessed( string $singular, string $expected ): void {
		$this->assertSame( $expected, Labels::pluralize( $singular ) );
	}

	/**
	 * The rules, and the shape of each one.
	 *
	 * Three rules and no table of exceptions: it is a convenience, and a
	 * longer list would only be wrong more confidently. Anything irregular
	 * is one argument away.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function pluralProvider(): array {
		return [
			'the ordinary case' => [ 'Book', 'Books' ],
			'ending in s'       => [ 'Class', 'Classes' ],
			'ending in ch'      => [ 'Branch', 'Branches' ],
			'ending in x'       => [ 'Box', 'Boxes' ],
			'consonant then y'  => [ 'Category', 'Categories' ],
			'vowel then y'      => [ 'Day', 'Days' ],
		];
	}

	/**
	 * An irregular plural is given, not guessed.
	 */
	public function test_an_irregular_plural_is_taken_as_given(): void {
		$this->assertSame( 'People', Labels::post_type( 'Person', 'People' )['name'] );
	}

	/**
	 * A caller's own label always wins.
	 *
	 * The escape hatch. A plugin that would rather write its own thirty-six
	 * translated strings can, and this gets out of the way.
	 */
	public function test_an_override_wins(): void {
		$labels = Labels::post_type( 'Book', 'Books', [ 'add_new_item' => 'Add a new title' ] );

		$this->assertSame( 'Add a new title', $labels['add_new_item'] );
		$this->assertSame( 'Search Books', $labels['search_items'], 'The rest were lost with it.' );
	}

	/**
	 * A hierarchical taxonomy gets parent labels and a flat one does not.
	 *
	 * "Parent Tag" is a control that cannot exist, and core keeps two sets
	 * for exactly this reason.
	 */
	public function test_the_two_kinds_of_taxonomy_differ(): void {
		$flat = Labels::taxonomy( 'Tag', 'Tags' );
		$tree = Labels::taxonomy( 'Genre', 'Genres', true );

		$this->assertArrayNotHasKey( 'parent_item', $flat );
		$this->assertSame( 'Parent Genre', $tree['parent_item'] );

		// And the other way round: a hierarchical taxonomy has no comma-
		// separated input to explain.
		$this->assertSame( 'Separate tags with commas', $flat['separate_items_with_commas'] );
		$this->assertArrayNotHasKey( 'separate_items_with_commas', $tree );
	}

	/**
	 * With a text domain, every label goes through translation.
	 *
	 * The one real cost of generating labels rather than writing them out:
	 * core's defaults are wrong for your content but translated, and these
	 * are right for your content and, without this, English.
	 */
	public function test_a_text_domain_is_honoured(): void {
		$GLOBALS['ct_translations']['my-plugin'] = [
			'Add New %1$s' => 'Ajouter un %1$s',
			'No %4$s found' => 'Aucun %4$s trouvé',
		];

		$labels = Labels::post_type( 'Livre', 'Livres', [], 'my-plugin' );

		$this->assertSame( 'Ajouter un Livre', $labels['add_new_item'] );
		$this->assertSame( 'Aucun livres trouvé', $labels['not_found'] );
	}

	/**
	 * Without one, nothing is translated and nothing breaks.
	 */
	public function test_no_text_domain_is_not_an_error(): void {
		$GLOBALS['ct_translations']['my-plugin'] = [ 'Add New %1$s' => 'Ajouter un %1$s' ];

		$this->assertSame( 'Add New Book', Labels::post_type( 'Book' )['add_new_item'] );
	}

	/**
	 * Every placeholder is numbered.
	 *
	 * A translator moving the noun to the other end of the sentence is the
	 * reason numbered placeholders exist, and a template mixing `%s` with
	 * `%1$s` cannot be reordered at all — sprintf refuses the mixture.
	 */
	public function test_every_placeholder_is_numbered(): void {
		$source = (string) file_get_contents( dirname( __DIR__ ) . '/src/Support/Labels.php' );

		preg_match_all( "/^\t\t'[a-z_]+'\s*=> '([^']*)',$/m", $source, $templates );

		$this->assertNotEmpty( $templates[1], 'No templates found to check.' );

		foreach ( $templates[1] as $template ) {
			$this->assertDoesNotMatchRegularExpression(
				'/(?<!\d\$)%[sd]/',
				$template,
				sprintf( 'This template has an unnumbered placeholder: %s', $template )
			);
		}
	}
}
