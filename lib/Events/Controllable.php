<?php declare( strict_types = 1 );

namespace Events;

abstract class Controllable {
	
	/**
	 *  Current event name
	 *  @var string
	 */
	protected readonly string $name;
	
	/**
	 *  Main event controller
	 *  @var \Events\Controller
	 */
	protected readonly object $controller;
	
	/**
	 *  Event parameters on execution
	 */
	protected array $_params	= [];
	
	/**
	 *  Error storage
	 *  @var array
	 */
	protected array $errors		= [];
	
	/**
	 *  Notification storage
	 *  @var array
	 */
	protected array $notices	= [];
	
	/**
	 *  Create new runnable with controller
	 *  
	 *  @param \Events\Controller	$ctrl	Event controller
	 */
	public function __construct( \Events\Controller	$ctrl ) {
		$this->controller	= $ctrl;
	}
	
	public function __destruct() {
		foreach ( $this->errors as $e ) {
			\messages( 'error', \get_called_class() . ' ' . $e );
		}
	}	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'id':
				$this->_id = ( int ) $value;
				break;
			
			case 'params':
				$this->_params = 
				static::formatSettings( $value );
		}
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'id':
				return $this->_id ?? 0;
			
			case 'params':
				return $this->_params;
		}
		return null;
	}
	
	/**
	 *  Current controllable's name (read-only)
	 *  @return string
	 */
	public function getName() : string {
		return $this->name ?? '';
	}
	
	/** 
	 *  Currently set event controller
	 *  
	 *  @return \Events\Controller
	 */
	public function getController() {
		return $this->controller;
	}
	
	/**
	 *  Get while optionally loading parameter object
	 *  
	 *  @param string	$name	Loaded/loading object name
	 *  @return mixed
	 */
	public function getControllerParam( string $name ) {
		$this->controller->addParam([ $name ]);
		
		return $this->controller->getParam( $name );
	}
	
	/**
	 *  Event parameters
	 *  
	 *  @return array
	 */
	public function getParams() : array {
		return $this->_params;
	}
	
	/**
	 *  Get running errors
	 *  
	 *  @return array
	 */
	public function getErrors() : array {
		return $this->errors;
	}
	
	/**
	 *  Get running notices
	 *  
	 *  @return array
	 */
	public function getNotices() : array {
		return $this->notices;
	}
	/**
	 *  Helper to detect and parse a 'settings' data type
	 *  
	 *  @param string	$name		Setting name
	 *  @return array
	 */
	public static function formatSettings( $value ) : array {
		// Nothing to format?
		if ( \is_array( $value ) ) {
			return $value;
		}
		
		// Can be decoded?
		if ( 
			!\is_string( $value )	|| 
			\is_numeric( $value )
		) {
			return [];
		}
		$t	= \trim( $value );
		if ( empty( $t ) ) {
			return [];
		}
		if ( 
			\str_starts_with( $t, '{' ) && 
			\str_ends_with( $t, '}' )
		) {
			return static::decode( ( string ) $t );
		}
		
		return [];
	}
	
	/**
	 *  Safely encode array to JSON
	 *  
	 *  @param array	$data	Content to be encoded to string
	 *  @param bool		$pretty	Preserve whitespace between content 
	 *  @return string
	 */
	public static function encode( 
		array	$data	= [], 
		bool	$pretty	= false 
	) : string {
		if ( empty( $data ) ) {
			return '';
		}
		
		if ( $pretty ) {
			$out = 
			\json_encode( 
				$data, 
				\JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_QUOT | 
				\JSON_HEX_AMP | \JSON_UNESCAPED_UNICODE | 
				\JSON_PRETTY_PRINT 
			);
		} else {
			$out = 
			\json_encode( 
				$data, 
				\JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_QUOT | 
				\JSON_HEX_AMP | \JSON_UNESCAPED_UNICODE
			);
		}
		
		return ( false === $out ) ? '' : $out;
	}
	
	/**
	 *  Safely decode JSON to array
	 *  
	 *  @return array
	 */
	public static function decode( 
		string		$data	= '', 
		int		$depth	= 10 
	) : array {
		if ( empty( $data ) ) {
			return [];
		}
		$out	= 
		\json_decode( 
			\utf8_encode( $data ), true, $depth, 
			\JSON_BIGINT_AS_STRING
		);
		
		if ( empty( $out ) || false === $out ) {
			return [];
		}
		
		return $out;
	}
}


