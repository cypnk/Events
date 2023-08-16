<?php declare( strict_types = 1 );

namespace Events;

abstract class NamedControllable extends Controllable {
	/**
	 *  Current event name
	 *  @var string
	 */
	protected readonly string $name;
	
	/**
	 *  Create new runnable with controller and unique name
	 *  
	 *  @param \Events\Controller	$ctrl	Event controller
	 *  @param string		$name	Current controllable's name
	 */
	public function __construct( 
		\Events\Controller	$ctrl, 
		string			$name 
	) {
		$this->name		= $name;
		parent::__construct( $ctrl );
	}
	
	/**
	 *  Current controllable's name (read-only)
	 *  @return string
	 */
	public function getName() : string {
		return $this->name ?? '';
	}
}

