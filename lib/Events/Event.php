<?php declare( strict_types = 1 );

namespace Events;

class Event implements \SplSubject extends NamedControllable  {
	
	/**
	 *  Registered handlers
	 *  @var array
	 */
	protected array $handlers	= [];
	
	
	/**
	 *  Stored event data
	 *  @var array
	 */
	protected array $output	= [];
	
	/**
	 *  Add a handler to this event
	 *  
	 *  @param \SplObserver	$handler	Event handler
	 */
	public function attach( \SplObserver $handler ) {
		$name = $handler->getName();
		if ( \array_key_exists( $name, $this->handlers ) ) {
			return;
		}
		
		$this->handlers[$name] = [ $handler->getPriority(), $handler ];
	}
	
	/**
	 *  Unregister handler from this event's notify list
	 *  
	 *  @param \SplObserver	$handler	Event handler
	 */
	public function detach( \SplObserver $handler ) {
		if ( \array_key_exists( $handler->getName(), $this->handlers ) ) {
			unset( $this->handlers[$name] );
		}
	}
	
	/**
	 *  Return notify results from handlers
	 *  
	 *  @return array
	 */
	public function getOutput() : array {
		return $this->output;
	}
	
	/**
	 *  Get handler by name if currently registered
	 *  
	 *  @param string	$name	Raw handler name
	 *  @return mixed 
	 */
	public function getHandler( string $name ) {
		return 
		\array_key_exists( $name, $this->handlers ) ? 
			$this->handlers[$name] : null;
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
	 *  Reorder handler priority
	 *  
	 *  @param object	$handler	Event handler
	 *  @param int		$priority	New handler priority
	 */
	public function priority( Handler $handler, int $priority ) {
		$name = $handler->getName();
		if ( \array_key_exists( $name, $this->handlers ) ) {
			$this->handlers[$name][0] = $priority;
		}
		$this->sortHandlers();
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
		
		foreach ( $this->handlers as $h ) {
			$h[1]->update( $this, $this->params );
			
			$this->output = 
			\array_merge( 
				$this->output, 
				$h[1]->data( $this->name ) ?? []
			);
		}
	}
}

