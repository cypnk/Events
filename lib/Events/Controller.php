<?php declare( strict_types = 1 );

namespace Events;

class Controller {
	
	/**
	 *  Loaded events
	 *  @var array
	 */
	protected array $events	= [];
	
	/**
	 *  Shared parameters
	 *  @var array
	 */
	protected array $params;
	
	/**
	 *  Initialize controller
	 *  
	 *  @param array	$_params	Startup classes by fully qualified name
	 */
	public function __construct( ?array $_params = null ) {
		if ( empty( $_params ) ) { return; }
		
		$this->addParams( $_params );
	}
	
	/**
	 *  Added shared parameters by class name
	 *  
	 *  @param array	$_params	Loading parameters
	 */
	public function addParams( array $_params ) {
		
		set_error_handler( function( 
			$eno, $emsg, $efile, $eline 
		) {
			$str	= 
			'Error adding controller parameters ' .  
				'Message: {msg} File: {file} Line: {line}';
			
			logException( 
				new \ErrorException( 
					$emsg, 0, $eno, $efile, $eline 
				), $str 
			);
		}, E_WARNING | E_ERROR );
		
		
		foreach ( $_params as $p ) {
			// Only handle strings and objects
			if ( !\is_string( $p ) || !\is_object( $p ) ) {
				continue;
			}
			
			// Filter type
			$t = match( \strtolower( \gettype( $p ) ) ) {
				'string'	=> 'string', 
				'object', 'resource', 'resource (closed)' 
						=> 'object', 
				// Skip all else
				default		=> 'skip'
			}
			
			// Shouldn't be handled
			if ( 0 == \strcmp( 'skip', $t ) ) {
				continue;
			}
			
			// Create search key
			$k = ( 0 == \strcmp( 'object', $t ) ) ? 
				\spl_object_hash( $p ) : \rtrim( $p, '\\' );
			
			// Already added?
			if ( \array_key_exists( $k, $this->params ) ) {
				continue;
			}
			
			// Add as-is, if object
			if ( 0 == \strcmp( 'object', $t ) ) {
				$this->params[$k] = $p;
				continue;
			}
			
			
			$this->params[$k]	= match( true ) {
				// Type controllable
				\is_subclass_of( 
					'\\Events\\Controllable', $p 
				)			=> new $p( $this ),
				
				// Other type of class
				\class_exists( $p )	=> new $p(),
				
				// Something else
				default			=> $p
			}
		}
		
		\restore_error_handler();
	}
	
	/**
	 *  Current parameter return helper
	 *  
	 *  @param string	$name		Property label
	 *  @return mixed
	 */
	public function getParam( string $name ) {
		return $this->params[$name] ?? null;
	}
	
	/**
	 *  Add to list of running events
	 *  
	 *  @param string	$name		New event name
	 *  @param Handler	$handler	
	 */
	public function listen( string $name, Handler $handler ) {
		$this->addEvent( $name );
		$this->events[$name]->attach( $handler );
	}
	
	/**
	 *  Remove handler from given event
	 *  
	 *  @param string	$name		Event name
	 *  @param object	$handler	Event handler
	 *  @reutrn bool
	 */
	public function dismiss( string $name, Handler $handler ) : bool {
		if ( \array_key_exists( $name, $this->events ) ) {
			$this->events[$name]->detach( $handler );
			return true;
		}
		return false;
	}
	
	/**
	 *  Completely remove event from running list of events
	 *  
	 *  @param string	$name	Event name
	 *  @return bool
	 */
	public function removeEvent( string $name ) : bool {
		if ( \array_key_exists( $name, $this->events ) ) {
			unset( $this->events[$name] );
			return true;
		}
		return false;
	}
	
	/**
	 *  
	 *  
	 *  @param string	$name	Description for $name
	 */
	public function addEvent( string $name ) {
		if ( \array_key_exists( $name, $this->events ) ) {
			return;
		}
		
		$this->events[$name] = new Event( $this, $name );
	}
	
	/**
	 *  
	 *  
	 *  @param string	$name		Description for $name
	 *  @param array	$params		Description for $params
	 */
	public function run( string $name, ?array $params = null ) {
		if ( \array_key_exists( $name, $this->events ) ) {
			$this->events[$name]->notify( $params );
		}
	}
}

