<?php declare( strict_types = 1 );
/**
 *  @file	/bootstrap.php
 *  @brief	Event loader and environment constants
 */

// Prevent direct calls
if ( 0 == \strcmp(
	\basename( \strtolower( $_SERVER['SCRIPT_NAME'] ), '.php' ), 
	'bootstrap' 
) ) { 
	\ob_end_clean();
	die();
};

/**
 *  Begin configuration
 */

// Path to this file's directory
define( 'PATH',	\realpath( \dirname( __FILE__ ) ) . '/' );

// Events core class files
define( 'EVENTS_LIB',	\PATH . 'lib/Events/' );

// Storage directory. Must be writable (chmod -R 0755 on *nix)
// This configuration implies storage is outside the web root (recommended)
define( 'WRITABLE',	\realpath( \dirname( __FILE__, 2 ) ) . '/data/' );

// Error log file
define( 'ERRORS',	\WRITABLE . 'errors.log' );

// Notification log file
define( 'NOTICES',	\WRITABLE . 'notices.log' ););

// Maximum log file size before rolling over (in bytes)
define( 'MAX_LOG_SIZE',		5000000 );




/**
 *  Environment preparation
 */
\date_default_timezone_set( 'UTC' );
\ignore_user_abort( true );
\ob_end_clean();





/**
 *  Isolated message holder
 *  
 *  @param string	$type		Message type, determines storage location
 *  @param string	$message	Log content body
 *  @param bool		$ret		Optional, returns stored log if true
 */
function messages( string $type, string $message, bool $ret = false ) {
	static $log	= [];
	
	if ( $ret && $message ) {
		return $log;
	}
	
	if ( !isset( $log[$type] ) ) {
		$log[$type] = [];	
	}
	
	// Clean message to file safe format
	$log[$type][] = 
	\preg_replace( 
		'/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F[\x{fdd0}-\x{fdef}\p{Cs}\p{Cf}\p{Cn}]/u', 
		'', 
		$message 
	);
}

/**
 *  Exception recording helper
 *  
 *  @param Exception	$e	Thrown error
 *  @param string	$msg	Optional override of default error format
 */
function logException( \Exception $e, ?string $msg = null ) {
	$msg ??= 'Error: {msg} File: {file} Line: {line}';
	
	\messages( 
		'error', 
		\strtr( $msg, [
			'{msg}'		=> $e->getMessage(),
			'{file}'	=> $e->getFile(),
			'{line}'	=> $e->getLine()
		] )
	);
}

/**
 *  Check log file size and rollover, if needed
 *  
 *  @param string	$file	Log file name
 */
function logRollover( string $file ) {
	// Nothing to rollover
	if ( !\file_exists( $file ) ) {
		return;
	}
	
	$fs	= \filesize( $file );
	
	// Empty file
	if ( false === $fs ) {
		return;
	}
	
	if ( $fs > \MAX_LOG_SIZE ) {
		$f = \rtrim( \dirname( $file ), '/\\' ) . 
			\DIRECTORY_SEPARATOR . 
			\basename( $file ) . '.' . 
			\gmdate( 'Ymd\THis' ) . '.log';
		\rename( $file, $f );
	}
}

/**
 *  Write messages to given error file
 */
function logToFile( string $msg, string $dest ) {
	logRollover( $dest );
	\error_log( 
		\gmdate( 'D, d M Y H:i:s T', time() ) . "\n" . 
			$msg . "\n\n\n\n", 
		3, 
		$dest
	);
}



/**
 *  Internal error logger
 */
\register_shutdown_function( function() {
	$msgs = messages( '', '', true );
	if ( empty( $msgs ) ) {
		return;
	}
	
	foreach ( $msgs as $k => $v ) {
		switch ( $k ) {
			case 'error':
			case 'errors':
				foreach( $v as $m ) {
					logToFile( $m, \ERRORS );
				}
				break;
				
			case 'notice':
			case 'notices':
				foreach( $v as $m ) {
					logToFile( $m, \NOTICES );
				}
				break;
		}
	}
}

/**
 *  Class loader
 */
\spl_autoload_register( function( $class ) {
	// Path replacements
	static $rpl	= [ '\\' => '/', '-' => '_' ];
	
	// Class prefix replacements
	static $prefix	= [
		'Events\\'		=> \EVENTS_LIB,
		// Add more as needed
	];
	
	foreach ( $prefix as $k => $v ) {
		// Skip non-Event classes
		if ( !\str_starts_with( $class, $k ) ) {
			continue;
		}
		
		// Build file path
		$file	= $v . 
		\strtr( \substr( $class, \strlen( $k ) ), $rpl ) . '.php';
		
		if ( \is_readable( $file ) ) {
			require $file;
			break;
		}
		
		messages( 'error', 'Unable to read file: ' . $file );
		die();
	}
}

// Start controller
$controller	= new \Events\Controller();

