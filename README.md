# Events
A set of event-handling helpers

This is a set of files for implementing a basic level of event handling in some of my projects, extracted here for reuse.

Contents:
* /bootstrap.php - Prepares the environment and adds helpers for Events class auto-loading and error and notice logging
* /lib/Events - Folder containing Events classes
* /data - Writable storage directory for errors and notices

Usage:
* Give write and execute permissions to /data (chmod 0755 on unix-like systems)
* Extend Events/Event, Events/Handler, and Events/Controllable as needed 
* Include bootstrap.php in the index file or the script being executed and add other source directories in the class-loader, if needed

