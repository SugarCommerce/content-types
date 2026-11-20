<?php
/**
 * Shared set-up for the core suite.
 *
 * @package SugarCommerce\ContentTypes
 */

declare( strict_types=1 );

namespace SugarCommerce\ContentTypes\Tests\Core;

use WP_UnitTestCase;

/**
 * Real WordPress, each test in a transaction it rolls back.
 */
abstract class TestCase extends WP_UnitTestCase {

	/**
	 * Off: the WordPress test case reads @expectedDeprecated annotations
	 * through a PHPUnit method that PHPUnit 12 no longer has, and nothing
	 * here expects a deprecation.
	 *
	 * @return void
	 */
	public function expectDeprecated(): void {}

	/**
	 * Off, with expectDeprecated().
	 *
	 * @return void
	 */
	public function expectedDeprecated(): void {}
}
