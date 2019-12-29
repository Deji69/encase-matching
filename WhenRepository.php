<?php
namespace Encase\Matching;

use Encase\Matching\When;
use Encase\Matching\Patterns\Patternable;

use function Encase\Functional\assertType;

/**
 * A repository for when clauses.
 */
class WhenRepository
{
	/** @var When[] */
	protected static $patterns = [];

	/**
	 * Add a pattern.
	 *
	 * @param  When $pattern
	 * @return string Unique identifier string for the pattern.
	 */
	public static function add(When $pattern)
	{
		$id = \spl_object_hash($pattern);
		static::$patterns[$id] = $pattern;
		return $id;
	}

	/**
	 * Retrieve a pattern.
	 *
	 * @param  string $id ID of the pattern object to get.
	 * @return When|null The object associated with the ID or `null`.
	 */
	public static function get(string $id)
	{
		return static::$patterns[$id] ?? null;
	}

	/**
	 * Remove a pattern.
	 *
	 * @param  string $id ID of the pattern object to remove.
	 * @return void
	 */
	public static function remove(string $id)
	{
		unset(static::$patterns[$id]);
	}
}
