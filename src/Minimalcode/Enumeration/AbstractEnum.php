<?php

namespace Minimalcode\Enumeration;

use InvalidArgumentException;
use LogicException;
use ReflectionClass;

/**
 * Enum for PHP
 *
 * @author Piro Fabio <pirofabio@gmail.com>
 */
abstract class AbstractEnum
{
	/**
	 * The selected enumerator name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The selected enumerator value
	 *
	 * @var null|bool|int|float|string
	 */
	private $value;

	/**
	 * The selected enumerator ordinal
	 *
	 * @var int
	 */
	private $ordinal;

	/**
	 * Already instantiated enumerators.
	 *
	 * @var array ["$class" => ["$name" => $instance, "$name" => $instance, ...], ...]
	 */
	private static $instances = [];

	/**
	 * Constructor
	 *
	 * @param string $name
	 * @param null|bool|int|float|string $value The value of the enumerator
	 * @param int $ordinal
	 */
	final private function __construct($name, $value, $ordinal)
	{
		$this->name = $name;
		$this->value = $value;
		$this->ordinal = $ordinal;
	}
	/**
	 * Get an enumerator instance by the given name.
	 *
	 * This will be called automatically on calling a method
	 * with the same name of a defined enumerator.
	 *
	 * @param string $method The name of the enumeraotr (called as method)
	 * @param array $args There should be no arguments
	 * @return static
	 * @throws \LogicException On ambiguous constant values
	 * @throws InvalidArgumentException On an invalid or unknown name
	 */
	final public static function __callStatic($method, array $args)
	{
		return static::forName($method);
	}

	/**
	 * Get an enumerator instance by the given name
	 *
	 * @param string $name The name of the enumerator
	 * @return static
	 * @throws \LogicException On ambiguous constant values
	 * @throws InvalidArgumentException On an invalid or unknown name
	 */
	final public static function forName($name)
	{
		$class = get_called_class();
		$instances = static::enumeratorsOf($class);

		/** @noinspection UnSafeIsSetOverArrayInspection */
		if(!isset($instances[$name])) {
			throw new InvalidArgumentException($class . ' has not an enum defined with name \'' . $name . '\'');
		}

		return $instances[$name];
	}

	/**
	 * Get an enumerator instance by the given value
	 *
	 * @param null|bool|int|float|string $value
	 * @return static
	 * @throws \LogicException On ambiguous constant values
	 * @throws \InvalidArgumentException On an invalid or unknown value
	 */
	final public static function forValue($value)
	{
		$class = get_called_class();

		foreach (static::enumeratorsOf($class) as $instance) {
			if($instance->value === $value) {
				return $instance;
			}
		}

		throw new InvalidArgumentException($class . ' has not an enum defined with value \'' . $value . '\'');
	}

	/**
	 * Get an enumerator instance by the given ordinal
	 *
	 * @param int $ordinal
	 * @return static
	 * @throws \LogicException On ambiguous constant values
	 * @throws \InvalidArgumentException On an invalid or unknown value
	 */
	final public static function forOrdinal($ordinal)
	{
		$class = get_called_class();

		foreach (static::enumeratorsOf($class) as $instance) {
			if($instance->ordinal === $ordinal) {
				return $instance;
			}
		}

		throw new InvalidArgumentException($class . ' has not an enum defined with ordinal \'' . $ordinal . '\'');
	}

	/**
	 * @return array
	 * @throws \LogicException On ambiguous constant values
	 */
	final public static function all()
	{
		return static::enumeratorsOf(get_called_class());
	}

	/**
	 * Store and return all available enums by the given class
	 *
	 * @param string $class
	 * @return static[]
	 * @throws LogicException On ambiguous constant values
	 */
	final private static function enumeratorsOf($class)
	{
		if (!array_key_exists($class, self::$instances)) {
			$reflection = new ReflectionClass($class);
			$constants  = $reflection->getConstants();

			// values needs to be unique
			$ambiguous = [];
			foreach ($constants as $value) {
				$names = array_keys($constants, $value, true);
				if (count($names) > 1) {
					$ambiguous[var_export($value, true)] = $names;
				}
			}

			// ambiguous values are not permitted
			if (count($ambiguous) > 0) {
				throw new LogicException(
					'All possible values needs to be unique. The following are ambiguous: '
					. implode(', ', array_map(function ($names) use ($constants) {
						return implode('/', $names) . '=' . var_export($constants[$names[0]], true);
					}, $ambiguous))
				);
			}

			// cache instances
			$ordinal = 0;
			foreach ($constants as $name => $value) {
				self::$instances[$class][$name] = new $class($name, $value, $ordinal++);
			}
		}

		return self::$instances[$class];
	}

	/**
	 * Get the name of the enumerator
	 *
	 * @return string
	 */
	final public function getName()
	{
		return $this->name;
	}

	/**
	 * Get the value of the enumerator
	 *
	 * @return null|bool|int|float|string
	 */
	final public function getValue()
	{
		return $this->value;
	}

	/**
	 * Get the ordinal of the enumerator
	 *
	 * @return int
	 */
	final public function getOrdinal()
	{
		return $this->ordinal;
	}

	/**
	 * Get the name of the enumerator
	 *
	 * @return string
	 * @see getName()
	 */
	public function __toString()
	{
		return $this->name;
	}

	/**
	 * @throws LogicException Enums are not cloneable because instances are implemented as singletons
	 */
	final private function __clone()
	{
		throw new LogicException('Enums are not cloneable');
	}

	/**
	 * @throws LogicException Enums are not serializable because instances are implemented as singletons
	 */
	final public function __wakeup()
	{
		throw new LogicException('Enums are not serializable');
	}
}