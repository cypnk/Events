<?php declare( strict_types = 1 );

namespace Events;

class Event extends Controllable implements \SplSubject {
	
	/**
	 *  Registered handlers
	 *  @var array
	 */
	protected array $handlers	= [];
	
	/**
	 *  Check if the handler was already added to updates list
	 *  
	 *  @param Handler $handler Event handler
	 *  @return bool
	 */
	public function hasHandler( Handler $handler ) : bool {
		return 
		\array_key_exists( $handler->getName(), $this->handlers );
	}
	
	/**
	 *  Add a handler to this event
	 *  
	 *  @param \SplObserver	$handler	Event handler
	 *  @param int		$priority	Preset execution priority
	 */
	public function attach( \SplObserver $handler, ?int $priority = null ) {
		if ( $this->hasHandler( $handler ) ) {
			return;
		}
		
		$this->handlers[$handler->getName()] = 
		[ $priority ?? $handler->getPriority(), $handler ];
	}
	
	/**
	 *  Unregister handler from this event's notify list
	 *  
	 *  @param \SplObserver	$handler	Event handler
	 */
	public function detach( \SplObserver $handler ) {
		if ( !$this->hasHandler( $handler ) ) {
			return;
		}
		
		unset( $this->handlers[$handler->getName()] );
	}
	
	/**
	 *  Sort handlers by priority
	 */
	public function sortHandlers() {
		\usort( $this->handlers, function( $p, $h ) {
			return $h[0] <=> $p[0];
		} );
	}
	
	/**
	 *  Reset handler priority within current list
	 *  
	 *  @param Handler	$handler	Given event handler
	 *  @param int		$priority	New execution priority
	 */
	public function priority( Handler $handler, int $priority ) : void {
		if ( !$this->hasHandler( $handler ) ) {
			return;
		}
		
		$this->handlers[$handler->getName()] = [ $priority, $handler ];
		$this->sortHandlers();
	}
	
	/**
	 *  Get handler by name if currently registered
	 *  
	 *  @param string	$name	Raw handler name
	 *  @return array
	 */
	public function getHandler( string $name ) : array {
		return 
		\array_key_exists( $name, $this->handlers ) ? 
			$this->handlers[$name] : null;
 	}
	
	/**
	 *  Run event and notify handlers
	 *  
	 *  @params array	$params		Optional event data
	 */
	public function notify( ?array $params = null ) {
		
		// Reset event params if any new
		if ( null !== $params ) {
			$this->params = $params;
		}
		
		// Sort
		$this->sortHandlers();
		
		$this->output[$this->name] ??= [];
		
		foreach ( $this->handlers as $k => $v ) {
			$h[1]->update( $this, $params );
			
			$this->output[$this->name] = 
			\array_merge( 
				$this->output[$this->name], 
				$h[1]->getOutput( $this->name ) ?? []
			);
		}
	}
}
