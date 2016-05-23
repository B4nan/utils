<?php

namespace B4nan\Application;

use Nette\Utils\ArrayHash;

/**
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
final class Parameters
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
	 * @param  string  parameter name
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

}
