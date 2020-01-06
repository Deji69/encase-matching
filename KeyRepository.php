<?php
namespace Encase\Matching;

use Encase\Matching\Key;

/**
 * A repository for key clauses.
 */
class KeyRepository
{
	/** @var Key[] */
	protected static $items = [];

	/**
	 * Add a key.
	 *
	 * @param  Key $key
	 * @return string Unique identifier string for the key.
	 */
	public static function add(Key $key)
	{
		$id = \spl_object_hash($key);
		static::$items[$id] = $key;
		return $id;
	}

	/**
	 * Retrieve a key.
	 *
	 * @param  string $id ID of the pattern object to get.
	 * @return Key|null The object associated with the ID or `null`.
	 */
	public static function get(string $id)
	{
		return static::$items[$id] ?? null;
	}

	/**
	 * Remove a key.
	 *
	 * @param  string $id ID of the pattern object to remove.
	 * @return void
	 */
	public static function remove(string $id)
	{
		unset(static::$items[$id]);
	}
}
