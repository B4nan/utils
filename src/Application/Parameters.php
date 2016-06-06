<?php

namespace B4nan\Application;

use Nette\Utils\ArrayHash;

/**
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
final class Parameters implements \ArrayAccess
{

	/** @var ArrayHash */
	private $parameters;

	/**
	 * @param array $parameters
	 */
	public function __construct(array $parameters)
	{
		$this->parameters = ArrayHash::from($parameters);
	}

	/**
	 * Returns parameter value.
	 * @param  string $key parameter name
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->parameters[$key];
	}


	/**
	 * Sets parameters value.
	 * @param  string  property name
	 * @param  mixed   property value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->parameters[$key] = $value;
	}

	/**
	 * @return ArrayHash
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Is property defined?
	 * @param  string  property name
	 * @return bool
	 */
	public function __isset($key)
	{
		return isset($this->parameters[$key]);
	}

	/**
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	public function offsetExists($offset)
	{
		return isset($this->parameters[$offset]);
	}

	/**
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	public function offsetGet($offset)
	{
		return $this->parameters[$offset];
	}

	/**
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetSet($offset, $value)
	{
		$this->parameters[$offset] = $value;
	}

	/**
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetUnset($offset)
	{
		unset($this->parameters[$offset]);
	}
}
