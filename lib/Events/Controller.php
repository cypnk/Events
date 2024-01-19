<?php declare( strict_types = 1 );

namespace Events;

class Controller {
	
	/**
	 *  Application error file
	 *  @var string
	 */
	protected readonly string $errfile;
	
	/**
	 *  Visitor error logging file
	 *  @var string
	 */
	protected readonly string $verrfile;
	
	/**
	 *  Logging notice file
	 *  @var string
	 */
	protected readonly string $notefile;
	
	/**
	 *  Storage directory path
	 *  @var string
	 */
	protected readonly string $writable;
	
	/**
	 *  Maximum log file size before rolling over
	 */
	const	MAX_LOG_SIZE		= 5000000;
	
	/**
	 *  Shared parameters
	 *  @var array
	 */
	protected array $params		= [];
	
	/**
	 *  Loaded events
	 *  @var array
	 */
	protected array $events		= [];
	
	/**
	 *  Log data storage
	 *  @var array
	 */
	protected array $logs		= [];
	
	public function __construct( 
		string	$_write, 
		?string	$_errfile	= null,
		?string	$_verrfile	= null,
		?string $_notefile	= null,
	) {
		$this->writable	= \rtrim( $_write, '/\\' ) . '/';
		
		$this->errfile	= $_errfile	?? 'errors.log';
		$this->verrfile	= $_verrfile	?? 'visitor_errors.log';
		$this->notefile	= $_notefile	?? 'notices.log';
		
		\set_exception_handler( [ $this, 'exceptionHandler' ] );
		\set_error_handler( [ $this, 'errorHandler' ] );
	}
	
	public function __destruct() {
		\restore_exception_handler();
		\restore_error_handler();
		
		$this->logMessages();
	}
	
	public function storage( ?string $_path = null ) {
		return 
		empty( $_path ) ? 
			$this->writable : 
			$this->writable . \ltrim( $_path, '/\\' );
	}
	
	/**
	 *  Added shared parameters by class name
	 *  
	 *  @param array	$_params	Loading parameters
	 */
	public function addParam( array $_params ) {
		foreach ( $_params as $p ) {
			// Only handle strings and objects
			if ( !\is_string( $p ) || !\is_object( $p ) ) {
				continue;
			}
			
			// Filter type
			$t = 
			match( \strtolower( \gettype( $p ) ) ) {
				'string'	=> 'string', 
				'object', 'resource', 'resource (closed)' 
						=> 'object', 
				// Skip all else
				default		=> 'skip'
			};
			
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
	 *  Add runnable event to current list
	 *  
	 *  @param string	$name	Unique event name
	 */
	public function listen( string $name, Handler $handler ) {
		if ( !\array_key_exists( $name, $this->events ) ) {
			$this->events[$name]	= new Event( $this, $name );
		}
		
		$this->events[$name]->attach( $handler );
	}
	
	/**
	 *  Remove handler from given event
	 *  
	 *  @param string	$name		Event name
	 *  @param object	$handler	Event handler
	 *  @reutrn bool
	 */
	public function dismiss( string $name, Handler $handler ) {
		if ( !\array_key_exists( $name, $this->events ) ) {
			return false;
		}
		
		$this->events[$name]->detach( $handler );
		return true;
	}
	
	/**
	 *  Completely remove event from running list of events
	 *  
	 *  @param string	$name	Event name
	 *  @return bool
	 */
	public function unregister( string $name ) : bool {
		if ( !\array_key_exists( $name, $this->events ) ) {
			return false;
		}
		
		unset( $this->events[$name] );
		return true;
	}
	
	/**
	 *  Run handlers in given event
	 *  
	 *  @param string	$name		Unique event name
	 *  @param array	$params		Runtime parameters
	 */
	public function run( string $name, ?array $params = null ) {
		if ( !\array_key_exists( $name, $this->events ) ) {
			return;
		}
		
		$this->events[$name]->notify( $params );
	}
	
	/**
	 *  User input and environment data filtering helper
	 *  
	 *  @param string	$source		Data source type, defaults to 'get'
	 *  @param array	$filter		Input processing filters
	 */
	public function inputData( string $source, array $filter ) : array {
		$dtype	= 
		match( \strtolower( $source ) ) {
			'post'			=> \INPUT_POST,
			'cookie'		=> \INPUT_COOKIE,
			'server'		=> \INPUT_SERVER,
			'env', 'environment'	=> \INPUT_ENV,
			default			=> \INPUT_GET
		};
		
		$data	= \filter_input_array( $dtype, $filter, true );
		return empty( $data ) ? [] : $data;
	}
	
	/**
	 *  Error recording helper
	 *  
	 *  @param int		$ecode		Error code
	 *  @param string	$estr		Error message
	 *  @param string	$efile		Origininating file name
	 *  @param int		$eline		Error file line location
	 */
	public function errorHandler( 
		int	$ecode, 
		string	$estr, 
		string	$efile	= null, 
		int	$eline 
	) {
		$this->error( 
			new \ErrorException( $estr, -1, $ecode, $efile, $line ),
			true
		);
	}
	
	/**
	 *  Exception recording helper
	 *  
	 *  @param Exception	$e	Thrown error
	 */
	public function exceptionHandler( \Throwable $e ) {
		$this->error( $e, true );
	}
	
	/**
	 *  Capture error for logging
	 *  
	 *  @param mixed	$err	Error message or exception to store
	 *  @param bool		$app	Application error if true, visitor error if false	
	 */
	public function error( $err, bool $app = true ) : void {
		$msg	= 
		match( true ) {
			
			// Thrown exception
			( $err instanceof \Exception )	=> 
			\strtr( 'Exception: {msg} in {file} on line {line}', [
				'{msg}'		=> $err->getMessage(),
				'{file}'	=> $err->getFile(),
				'{line}'	=> $err->getLine()
			] ), 
			
			// Generic capture from E.G. error_get_last()
			\is_array( $err )		=> 
			\strtr( '{type}: {msg} in {file} on line {line}', [
				'{type}'	=> $err['type']		?? 'Unkown type',
				'{msg}'		=> $err['message']	?? 'No message',
				'{file}'	=> $err['file']		?? 'Unknown file',
				'{line}'	=> $err['line']		?? 'Unkown line'
			] ), 
			
			default				=> ( string ) $err	
		};
		
		if ( $app ) {
			$this->message( 'error', $msg );
			return;
		}
		
		$filter	= [
			'SERVER_ADDR'		=> [
				'filter'	=> \FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags'		=> \FILTER_REQUIRE_SCALAR
			],
			'REQUEST_METHOD'	=> [
				'filter'	=> \FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags'		=> \FILTER_REQUIRE_SCALAR
			],
			
			'REQUEST_URI'		=> [
				'filter'	=> \FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags'		=> \FILTER_REQUIRE_SCALAR
			],
			
			'HTTP_USER_AGENT'	=> [
				'filter'	=> \FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags'		=> \FILTER_REQUIRE_SCALAR
			]
		];
		
		$data	= $this->inputData( 'server', $filter );
		$msg	.= ' ' . 
			$data['SERVER_ADDR']		?? 'unknown-host ' . 
			$data['REQUEST_METHOD']		?? 'unknown-method ' . 
			$data['REQUEST_URI']		?? 'unknown-uri ' . 
			$data['HTTP_USER_AGENT']	?? 'unknown-user-agent';
		
		$this->message( 'visitor', $msg );
	}
	
	/**
	 *  Check log file size and rollover, if needed
	 *  
	 *  @param string	$file	Log file name
	 */
	protected function logRollover( $file ) {
		// Nothing to rollover
		if ( !\file_exists( $file ) ) {
			return;
		}
		
		$fs	= \filesize( $file );
		
		// Empty file
		if ( false === $fs ) {
			return;
		}
		
		if ( $fs > static::MAX_LOG_SIZE ) {
			$f = \rtrim( \dirname( $file ), '/\\' ) . 
				\DIRECTORY_SEPARATOR . 
				\basename( $file ) . '.' . 
				\gmdate( 'Ymd\THis' ) . '.log';
			\rename( $file, $f );
		}
	}
	
	function logToFile( string $msg, string $dest ) {
		$this->logRollover( $dest );
		\error_log( 
			\gmdate( 'D, d M Y H:i:s T', time() ) . "\n" . 
				$msg . "\n\n\n\n", 
			3, 
			$this->storage( $dest )
		);
	}
	
	/**
	 *  Message holder
	 *  
	 *  @param string	$type		Message type, determines storage location
	 *  @param string	$message	Log content body
	 */
	public function message( string $type, string $message ) {
		if ( $ret && $message ) {
			return $this->logs;
		}
		
		if ( !isset( $this->logs[$type] ) ) {
			$this->logs[$type] = [];	
		}
		
		// Clean message to file safe format
		$this->logs[$type][] = 
		\preg_replace( 
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F' . 
				'[\x{fdd0}-\x{fdef}\p{Cs}\p{Cf}\p{Cn}]/u', 
			'', 
			$message 
		);
	}
	
	public function logMessages() : void {
		if ( empty ( $this->logs ) ) {
			return;
		}
		
		foreach ( $this->logs as $k => $v ) {
			switch ( $k ) {
				case 'error':
				case 'errors':
					foreach( $v as $m ) {
						logToFile( $m, $this->errfile );
					}
					break;
				
				case 'visistor':
				case 'visitors':
					foreach( $v as $m ) {
						logToFile( $m, $this->notefile );
					}
					break;
					
				case 'notice':
				case 'notices':
					foreach( $v as $m ) {
						logToFile( $m, $this->notefile );
					}
					break;
			}
		}
	}
}
