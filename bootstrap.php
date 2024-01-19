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

/**
 *  Environment preparation
 */
\date_default_timezone_set( 'UTC' );
\ignore_user_abort( true );
\ob_end_clean();



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
		
		die( 'error', 'Unable to read file: ' . $file );
	}
}

// Start controller
$controller	= new \Events\Controller();

