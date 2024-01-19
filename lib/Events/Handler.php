<?php declare( strict_types = 1 );

namespace Events;

class Handler extends Controllable implements \SplObserver {
	
	/**
	 *  Handler execution priority
	 *  @var int
	 */
	protected int $priority;
	
	/**
	 *  This handler doesn't allow priority change if true
	 *  @var bool
	 */
	protected bool $fixed_priority	= false;
	
	/**
	 *  Create handler with given controller and optional start priority
	 *  
	 *  @param \Notes\Controller	$ctrl	Main event controller
	 *  @param int			$_pri	Optional execution priority
	 */
	public function __construct( Controller	$ctrl, ?int $_pri = null ) {
		if ( null !== $_pri ) {
			$this->priority = $_pri;
		}
		
		parent::__construct( $ctrl );
	}
	
	/**
	 *  Get current handler's priority, if set, defaults to 0
	 *  
	 *  @return int
	 */
	public function getPriority() : int {
		return $this->priority ?? 0;
	}
	
	/**
	 *  Set current handler's priority, if not fixed, returns true on success
	 *  
	 *  @return bool
	 */
	public function setPriority( int $p = 0 ) : bool {
		if ( $this->fixed_priority ) {
			return false;
		}
		$this->priority = $p;
		return true;
	}
	
	/**
	 *  Accept notification from event
	 *  
	 *  @param SplSubject	$event Description for $event
	 *  @param array	$params Description for $params
	 */
	public function update( \SplSubject $event, ?array $params = null ) {}
}

