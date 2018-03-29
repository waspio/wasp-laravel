<?php
/**
 * WaspHandler.php
 *
 * @version 1.0
 * @date 11/27/17 9:30 AM
 * @package samsung
 */

namespace Wasp\WaspLaravel;


class WaspHandler
{
	static $user_data = array();
	private $timeout = 2;
	private $ip_address = '';
	private $wasp_version = '2.2.5';
	private $notification_uri = 'https://wasp.io/requests/datastore/v3/';
	private $php_version = \PHP_VERSION;
	private $browser = array();
	static $settings = array();
	private static $requests = array();
	private static $display_errors = array();
	private $config_keys = array(
		'redirect' => false,
		'display' => false,
		'environment' => 'production',
		'open' => '',
		'close' => '',
		'code' => false,
		'ignore' => array(),
		'ignored_domains' => array(),
		'generate_log' => false,
		'filters' => false,
		'full_backtrace' => false
	);
	protected $error_levels = array(
		\E_NOTICE => 1,
		\E_USER_NOTICE => 2,
		\E_STRICT => 3,
		\E_WARNING => 4,
		\E_CORE_WARNING => 5,
		\E_COMPILE_WARNING => 6,
		\E_USER_WARNING => 7,
		\E_DEPRECATED => 8,
		\E_USER_DEPRECATED => 9,
		\E_USER_ERROR => 10,
		\E_RECOVERABLE_ERROR => 11,
		\E_ERROR => 12,
		\E_PARSE => 13,
		\E_COMPILE_ERROR => 14,
		\E_CORE_ERROR => 15,
		'Database Error' => 16,
		'Exception' => 17,
		'GitHub Commit' => 20,
		'JavaScript' => 21,
		'Ajax' => 22,
		404 => 23
	);


	/**
	 * Wasp constructor.
	 * @param string $api_key
	 * @param array $vars
	 * @throws \Exception
	 */
	public function __construct( $api_key = '', $vars = array() )
	{
		//Make sure we have a new enough version of PHP for Wasp to do it's job
		if( version_compare( $this->php_version, '5.3.0' ) < 0 )
		{
			throw new \Exception( 'Wasp requires at least PHP 5.3.0' );
		}

		//Set the environmental error reporting
		error_reporting( -1 );
		ini_set( 'display_errors', 0 );

		set_error_handler( array( $this, 'error_handler' ) );
		set_exception_handler( array( $this, 'exception_handler' ) );
		register_shutdown_function( array( $this, 'shutdown_handler' ) );

		//Make sure the keys have been set for the user and project
		if( is_array( $api_key ) || empty( $api_key ) )
		{
			error_log( 'Unable to initialize Wasp without an api key.' );
		}
		//Make sure curl exists
		if( !function_exists( 'curl_version' ) )
		{
			error_log( 'Please install and enable cURL in your PHP server to use Wasp.' );
		}

		self::$settings['api_key'] = $api_key;
		self::$settings['wasp_version'] = $this->wasp_version;
		self::$settings['php_version'] = $this->php_version;
		//self::$settings['environment'] = config( 'app.env' );

		foreach( $this->config_keys as $key => $value )
		{
			if( isset( $vars[$key] ) && !empty( $vars[$key] ) )
			{
				self::$settings[$key] = $vars[$key];
			}
			else
			{
				self::$settings[$key] = $value;
			}
		}
		$startup = $this->startup();
		self::$settings = array_merge( self::$settings, $vars, $startup );
	}
	//end __construct()


	/**
	 * More reliable function to acquire actual user IP address
	 * @access private
	 * @param none
	 * @return string
	 */
	private function get_ip()
	{
		$this->ip_address = '';
		if( isset( $_SERVER ) )
		{
			if( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) )
			{
				$this->ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
			}
			elseif( isset( $_SERVER["HTTP_CLIENT_IP"] ) )
			{
				$this->ip_address = $_SERVER["HTTP_CLIENT_IP"];
			}
			elseif( isset( $_SERVER["REMOTE_ADDR"] ) )
			{
				$this->ip_address = $_SERVER["REMOTE_ADDR"];
			}
		}
		else
		{
			if( getenv( 'HTTP_X_FORWARDED_FOR' ) )
			{
				$this->ip_address = getenv( 'HTTP_X_FORWARDED_FOR' );
			}
			elseif( getenv( 'HTTP_CLIENT_IP' ) )
			{
				$this->ip_address = getenv( 'HTTP_CLIENT_IP' );
			}
			else
			{
				$this->ip_address = getenv( 'REMOTE_ADDR' );
			}
		}
		return $this->ip_address;
	}
	//End get_ip()


	/**
	 * Function to extract better data from the user agent
	 * Adapted from http://www.php.net/manual/en/function.get-browser.php#91655
	 * @access private
	 * @param none
	 * @return array
	 */
	private function browser_data()
	{
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'none';

		$base_output = array(
			'operating_system' => 'unknown',
			'name' => 'unknown',
			'version' => '0.0.0',
			'user_agent' => $user_agent
		);

		$browsers = array(
			'firefox', 'msie', 'opera', 'chrome', 'safari',
			'mozilla', 'seamonkey',    'konqueror', 'netscape',
			'gecko', 'navigator', 'mosaic', 'lynx', 'amaya',
			'omniweb', 'avant', 'camino', 'flock', 'aol', 'robot',
			'spider', 'bot', 'crawl', 'w3c_validator', 'jigsaw', 'search'
		);
		$operating_systems = array(
			'linux' => 'Linux',
			'macintosh' => 'Macintosh',
			'mac' => 'Macintosh',
			'windows' => 'Windows',
			'win32' => 'Windows'
		);

		foreach( $browsers as $browser )
		{
			if( preg_match("#($browser)[/ ]?([0-9.]*)#", strtolower( $user_agent ), $match ) )
			{
				$base_output['name'] = ucwords( $match[1] );
				$base_output['version'] = $match[2];
				break ;
			}
		}
		foreach( $operating_systems as $os => $label )
		{
			if( preg_match( "/$os/i", strtolower( $user_agent ), $match ) )
			{
				$base_output['operating_system'] = $operating_systems[$match[0]];
				break ;
			}
		}

		return $base_output;
	}
	//End browser_data()


	/**
	 * Function to provide clean http/s link to the requested script
	 * @access private
	 * @param none
	 * @return string
	 */
	private function current_url()
	{
		$host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : 'No Host';
		$uri = 'http';
		//Check to see if we're on SSL or not to determine https vs http
		if( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ||
			( isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == 443 ) )
		{
			$uri = 'https';
		}
		$uri .= '://';
		$uri .= $host . $_SERVER['REQUEST_URI'];
		return $uri;
	}
	//End current_url()


	/**
	 * One of the few static functions to add user data after Wasp
	 * has been initialized
	 * Expects an array of data, and will turn any data INTO an array
	 * Must be called after Wasp has been initialized, but at any point
	 * before __destroy statically or procedurally
	 * Wasp::add_user( array( 'Company' => 'Wasp.io', 'Awesome' => 'Yes' ) );
	 * $wasp->add_user( array( 'Company' => 'Wasp.io', 'Awesome' => 'Yes' ) );
	 * @access public
	 * @param mixed $data
	 * @return none
	 */
	public static function add_user( $data )
	{
		if( !empty( $data ) )
		{
			if( is_object( $data ) )
			{
				$data = json_decode( json_encode( $data ), true );
			}
			elseif( is_string( $data ) )
			{
				$data = (array)$data;
			}
			self::$settings['user_configuration']['User'] = $data;
		}
	}
	//End add_user()


	/**
	 * Get startup vars for server, session, get and posts
	 */
	protected function startup()
	{
		$return = array();

		//Server configuration
		$this->server = (array)$_SERVER;

		//User IP address
		$return['user_configuration']['IP Address'] = $this->get_ip();

		//HTTP_USER_AGENT
		if( isset( $this->server['HTTP_USER_AGENT'] ) )
		{
			//Browser info
			$this->browser = $this->browser_data();

			$return['user_configuration']['Browser'] = $this->browser['name'];
			$return['user_configuration']['Browser Version'] = $this->browser['version'];
			$return['user_configuration']['OS'] = $this->browser['operating_system'];
			$return['user_configuration']['User Agent'] = $this->browser['user_agent'];
		}

		//Requested script
		$return['user_configuration']['Script'] = isset( $this->server['REDIRECT_URL'] ) ? $this->cleanfile( $this->server['REDIRECT_URL'] ) : $this->cleanfile( $this->server['SCRIPT_NAME'] );

		//Request URI
		if( isset( $this->server['REQUEST_URI'] ) )
		{
			self::$settings['uri'] = $this->current_url();
		}

		//Hostname
		self::$settings['hostname'] = isset( $this->server['HTTP_HOST'] ) ? $this->server['HTTP_HOST'] : 'No Host';

		//Referring URI
		if( isset( $this->server['HTTP_REFERER'] ) )
		{
			$return['user_configuration']['Referer'] = $this->server['HTTP_REFERER'];
		}

		//Method
		if( isset( $this->server['REQUEST_METHOD'] ) )
		{
			$return['user_configuration']['Request Method'] = $this->server['REQUEST_METHOD'];
		}

		//GET
		if( isset( $_GET ) && !empty( $_GET ) )
		{
			$return['user_configuration']['Get'] = $_GET;
		}

		//POST
		if( isset( $_POST ) && !empty( $_POST ) )
		{
			$return['user_configuration']['Post'] = $this->sensitive_filter( $_POST );
		}

		//Session Vars; must first start the session if not already started
		if( !isset( $_SESSION ) )
		{
			session_start();
		}
		if( !empty( $_SESSION ) )
		{
			$return['user_configuration']['Session'] = $this->sensitive_filter( $_SESSION );
		}

		//Cookies
		if( isset( $_COOKIE ) && !empty( $_COOKIE ) )
		{
			$return['user_configuration']['Cookies'] = $_COOKIE;
		}

		return $return;
	}
	//end startup()


	/**
	 * Function to skip ALL errors for any domain specified in the
	 * ignored_domains configuration settings
	 * @access private
	 * @param none
	 * @return bool
	 */
	private function reject_host()
	{
		if( isset( self::$settings['ignored_domains'] ) && is_array( self::$settings['ignored_domains'] ) )
		{
			foreach( self::$settings['ignored_domains'] as $domain )
			{
				if( preg_match( "/\b".strtolower( $domain ) ."\b/i", strtolower( self::$settings['hostname'] ) ) )
				{
					//Refuse this domain
					return true;
				}
			}

		}
		return false;
	}
	//end reject_host()


	/**
	 * Function to determine if an error with this level should be skipped
	 * Skips based on ignore param (error levels)
	 * @access private
	 * @param mixed
	 * @return bool
	 */
	private function skip_error( $level )
	{
		if( isset( self::$settings['ignore'] ) && is_array( self::$settings['ignore'] ) && in_array( $level, self::$settings['ignore'] ) )
		{
			return false;
		}
		return true;
	}
	//end skip_error()


	/**
	 * Function to allow known sensitive data to be stripped automatically
	 * Accepts only arrays for filtering
	 * ONLY applies to $_POST and $_SESSION data
	 * Uses filters array
	 * @access private
	 * @param array $data
	 * @param string $replacement
	 * @return mixed
	 */
	private function sensitive_filter( $data, $replacement = '[redacted]' )
	{
		if( empty( $data ) || !is_array( $data ) || !self::$settings['filters'] )
		{
			return $data;
		}

		$defined_filters = self::$settings['filters'];

		//Make the filter usable
		$recursivify = function( &$item, $key) use ( $defined_filters, $replacement ) {

			//Loop through each assigned filter and check it against the data input
			foreach( $defined_filters as $filter )
			{
				if( !empty( $item ) && preg_match( "/".strtolower( trim( $filter ) ) ."/i", strtolower( trim( $key ) ) ) )
				{
					$item = $replacement;
				}
			}

		};

		//Loop through the data filtering along the way
		array_walk_recursive( $data, $recursivify );

		return $data;
	}
	//end sensitive_filter()


	/**
	 * Allow configuration to grab LESS backtrace data to save performance
	 * Defaults to limited backtrace to save memory on the server
	 * @access private
	 * @param none
	 * @return array
	 */
	private function backtrace_retriever()
	{
		if( !self::$settings['full_backtrace'] && version_compare( $this->php_version, '5.3.6' ) >= 0 )
		{
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS & ~DEBUG_BACKTRACE_PROVIDE_OBJECT );
		}
		elseif( !self::$settings['full_backtrace'] && version_compare( $this->php_version, '5.2.5' ) >= 0 )
		{
			$backtrace = debug_backtrace( FALSE );
		}
		else
		{
			$backtrace = debug_backtrace();
		}

		return $backtrace;
	}
	//end backtrace_retriever()


	/**
	 * Function to return only tracepath values that are useful
	 * @access private
	 * @param \Exception $e
	 * @return array
	 */
	private function clean_tracepath( \Exception $e )
	{
		$data = array();
		foreach( $e->getTrace() as $trace )
		{
			//Skip this file
			if( ( isset( $trace['file'] ) && $trace['file'] == __FILE__ ) || count( $trace ) == 0 )
			{
				continue;
			}

			if( $trace['function'] == 'error_handler' && count( $data ) == 0 )
			{
				continue;
			}

			$params = [];
			if( isset( $trace['file'] ) )
			{
				$params['file'] = $trace['file'];
			}
			if( isset( $trace['line'] ) )
			{
				$params['line'] = $trace['line'];
			}
			if( isset( $trace['function'] ) )
			{
				$params['function'] = $trace['function'];
			}
			if( isset( $trace['args'] ) && !empty( $trace['args'] ) )
			{
				//Only grab the first 10 elements in args
				$params['args'] = array_splice( $trace['args'], 0, 10 );
			}
			$data[] = $params;
		}

		//Add the first error from the stacktrace
		$data[] = array(
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'class' => get_class( $e )
		);

		return $data;
	}
	//end clean_tracepath()


	/**
	 * Get debug backtrace values
	 * Return cleaned array variant
	 * @access private
	 * @param string $file
	 * @param string $line
	 * @return array
	 */
	private function get_backtrace( $file = '', $line = '' )
	{
		$data = array();

		$backtrace = $this->backtrace_retriever();

		foreach( $backtrace as $trace )
		{
			//Skip this file
			if( ( isset( $trace['file'] ) && $trace['file'] == __FILE__ ) || count( $trace ) == 0 )
			{
				continue;
			}

			if( $trace['function'] == 'error_handler' && count( $data ) == 0 )
			{
				continue;
			}

			$params = [];
			if( isset( $trace['file'] ) )
			{
				$params['file'] = $trace['file'];
			}
			if( isset( $trace['line'] ) )
			{
				$params['line'] = $trace['line'];
			}
			if( isset( $trace['function'] ) )
			{
				$params['function'] = $trace['function'];
			}
			if( isset( $trace['args'] ) && !empty( $trace['args'] ) )
			{
				//Only grab the first 10 elements in args
				$params['args'] = array_splice( $trace['args'], 0, 10 );
			}
			$data[] = $params;
		}

		//Add the originating error file and line
		if( !empty( $file ) && !empty( $line ) )
		{
			$data[] = array(
				'file' => $file,
				'line' => $line
			);
		}

		return $data;
	}
	//end get_backtrace()


	/**
	 * Function to add logtimes to log_message
	 * Generates UTC (+000) timestamps
	 * @access private
	 * @param none
	 * @return string
	 */
	private function right_now()
	{
		return gmdate( 'Y-m-d H:i:s' );
	}
	//end right_now()


	/**
	 * Function to clean filenames
	 * @param $file
	 * @return string
	 */
	private function cleanfile( $file )
	{
		/**
		 * Wordpress and other frameworks
		 * sometimes outputs errors in "functions.php(181) : regexp code" format
		 * So we'll need to remove that to get the actual filename
		 */
		if( strpos( $file, '(' ) )
		{
			$file = substr( $file, 0, strpos( $file, '(' ) );
		}
		return ltrim( $file, DIRECTORY_SEPARATOR );
	}
	//end cleanfile()


	/**
	 * Function initiated to output (or not) a list of errors
	 * Called by __destruct
	 * @access public
	 * @return mixed
	 */
	private function display_errors()
	{
		if( isset( self::$settings['display'] ) && self::$settings['display'] === true && !empty( self::$display_errors ) )
		{
			//Add the error pre-wrapper
			if( isset( self::$settings['open'] ) )
				echo self::$settings['open'];

			//Echo the compiled errors
			foreach( self::$display_errors as $e )
			{
				echo '<pre>';
				print_r( $e );
				echo '</pre>';
			}
			//Add the error post-wrapper
			if( isset( self::$settings['close'] ) )
				echo self::$settings['close'];

			//Empty the display list
			self::$display_errors = array();
		}
	}
	//end display_errors()


	/**
	 * Function initiated in the event of a fatal error
	 * Redirects users to a specific URI after displaying message
	 * The last error logged (typically the fatal one initiating the redirect)
	 * IS passed in a query parameter called "e" as a base64_encoded string for security
	 * ?e=[base64_encoded message, line and file of last message]
	 * Message is lenghty for $_GET, but averages between 200 and 300 chars
	 * and should be okay as per http://stackoverflow.com/a/7725515
	 *
	 * @access private
	 * @param none
	 * @return mixed
	 */
	private function fatal_redirect()
	{
		if( isset( self::$settings['redirect'] ) && !empty( self::$settings['redirect'] ) )
		{
			$fatal_error_count = count( self::$requests['errors'] ) - 1;

			//Default fatal message to the warning at the uri
			$redirect_message = 'Fatal error at '. $this->current_url();

			if( isset( self::$requests['errors'][$fatal_error_count]['message'] ) )
			{
				$redirect_message = self::$requests['errors'][$fatal_error_count]['message'];
			}
			if( isset( self::$requests['errors'][$fatal_error_count]['line'] ) )
			{
				$redirect_message .= ' on line '. self::$requests['errors'][$fatal_error_count]['line'];
			}
			if( isset( self::$requests['errors'][$fatal_error_count]['file'] ) )
			{
				$redirect_message .= ' of file '. self::$requests['errors'][$fatal_error_count]['file'];
			}

			echo '<html><head><META http-equiv="refresh" content="0;URL=' . self::$settings['redirect'] .'?e=';
			$redirect_message = base64_encode( $redirect_message );
			echo urlencode( $redirect_message );
			echo '"></head></html>';

			//Only real place an exit should ever happen ;)
			exit;
		}
	}
	//end fatal_redirect()


	/**
	 * Custom exception handler
	 * Makes subsequent calls to log_custom_error
	 * @access public
	 * @param $e
	 */
	public function exception_handler( $e )
	{
		$trace_path = $this->clean_tracepath( is_object( $e ) ? $e : debug_backtrace() );

		$error = array(
			'message' => $e->getMessage(),
			'generated' => $this->right_now(),
			'severity' => ( $e instanceof \ErrorException || $e instanceof \ParseError) ? $e->getSeverity() : $this->error_levels['Exception'],
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'context' => $trace_path
		);

		$this->log_custom_error( $error );
	}
	//end exception_handler()


	/**
	 * Custom error handler used classwide
	 * Makes subsequent calls to log_custom_error function
	 * @access public
	 * @param string $error_level
	 * @param string $error_message
	 * @param string $error_file
	 * @param string $error_line
	 * @param mixed $error_context
	 * @return $this->log_custom_error( $error )
	 */
	public function error_handler( $error_level, $error_message, $error_file, $error_line, $error_context )
	{
		$error = array(
			'message' => $error_message,
			'generated' => $this->right_now(),
			'file' => $error_file,
			'line' => $error_line,
			'context' => $this->get_backtrace( $error_file, $error_line )
		);

		if( isset( $this->error_levels[$error_level] ) )
		{
			$error['severity'] = $this->error_levels[$error_level];
		}
		else
		{
			$error['severity'] = $this->error_levels[\E_WARNING]; //default to E_WARNING
		}

		$this->log_custom_error( $error );
	}
	//end error_handler()


	/**
	 * Function to handle fatal errors
	 * Called when PHP script ends
	 * @access public
	 */
	public function shutdown_handler()
	{
		$lasterror = error_get_last();

		if( !is_null( $lasterror ) )
		{
			$error = array(
				'message' => $lasterror['message'],
				'generated' => $this->right_now(),
				'severity' => isset( $this->error_levels[$lasterror['type']] ) ? $this->error_levels[$lasterror['type']] : 15,
				'file' => $lasterror['file'],
				'line' => $lasterror['line'],
				'context' => $this->get_backtrace( $lasterror['file'], $lasterror['line'] )
			);

			$this->log_custom_error( $error, true );
		}
	}
	//end shutdown_handler()


	/**
	 * Dedicated function to allow handling of database errors
	 * Example usage:
	 * mysqli_query( "SELECT something FROM invalid_table" ) or $wasp->log_db_errors( mysqli_error(), 'Unable to query!' );
	 * @access public
	 * @param string $error_message
	 * @param string $query
	 */
	public function log_db_errors( $error_message, $query )
	{
		$trace = $this->get_backtrace();
		$file = isset( $trace[0]['file'] ) ? $trace[0]['file'] : $trace['file'];
		$line = isset( $trace[0]['line'] ) ? $trace[0]['line'] : $trace['line'];

		$error = array(
			'message' => $error_message,
			'generated' => $this->right_now(),
			'severity' => $this->error_levels['Database Error'],
			'file' => $file,
			'line' => $line,
			'context' => array( 'Query' => htmlentities( $query ) )
		);

		$this->log_custom_error( $error );
	}
	//end log_db_errors()


	/**
	 * Function to send any custom messages to wasp
	 * Example:
	 * $data = array( 'name' => 'WASP!!!!', 'data' => 'something awesome' );
	 * $wasp->log_message( $data );
	 * @access public
	 * @param string $label
	 * @param $metadata
	 */
	public function log_message( $label, $metadata = null )
	{
		$trace = $this->get_backtrace();
		$file = isset( $trace[0]['file'] ) ? $trace[0]['file'] : $trace['file'];
		$line = isset( $trace[0]['line'] ) ? $trace[0]['line'] : $trace['line'];

		$log = array(
			'message' => $label,
			'generated' => $this->right_now(),
			'severity' => '0',
			'file' => $file,
			'line' => $line,
			'context' => array( 'message' => $metadata )
		);

		//Send to the API
		$this->log_custom_error( $log );
	}
	//end log_message()


	/**
	 * Internal function to retrieve code AROUND the error to provide
	 * users with context for the error they've received notification about
	 *
	 * @access private
	 * @param string $file
	 * @param int $line
	 * @return string $code
	 */
	private function code_context( $file, $line )
	{

		if( empty( $file ) || empty( $line ) )
		{
			return '';
		}

		//Double check the file accessibility
		if( !is_readable( $file ) )
		{
			return '';
		}

		$output_string = array();
		$line = ( $line - 1 );
		$start = ( $line - 5 );
		$end = ( $line + 5 );

		$lines = file( $file );
		if( isset( $lines[$start] ) )
		{
			foreach( range( $start, $end ) as $l )
			{
				if( isset( $lines[$l] ) )
				{
					if( $l == $line )
					{
						$lines[$l] = '{wasp_line}'. $lines[$l] .'{/wasp_line}';
					}
					$output_string[] = $lines[$l];
				}
			}
		}
		elseif( isset( $lines[$line] ) )
		{
			$output_string[] = $lines[$line];
		}

		return $output_string;
	}
	//end code_context()


	/**
	 * Function that handles the display of errors, error logging, and error sending
	 * @access public
	 * @param array $error
	 * @param bool $fatal (fatal errors must be sent immediately otherwise __destruct never sends)
	 * @return mixed
	 */
	public function log_custom_error( $error, $fatal = false )
	{
		//If the user has indicated to skip certain error levels, do so now
		//Same goes for no file or line
		if( !$this->skip_error( $error['severity'] ) || ( empty( $error['file'] ) && empty( $error['line'] ) ) )
		{
			return;
		}

		//Assign the filename to a tempvar to check for cleanfile exclusions
		//Such as functions.php(210) : regexp code
		$pre_file = $error['file'];

		//Clean the filename
		$error['file'] = $this->cleanfile( $error['file'] );

		//Drop it to the log writer to see if a log should exist
		$this->generate_logfile( $error );

		//Get the file context if config dictates it
		if( !isset( self::$settings['code'] ) || self::$settings['code'] === true )
		{
			//functions.php(210) : regexp code type strip to get actual line
			if( preg_match( '/\((.*)\)/U', $pre_file, $matches ) && isset( $matches[1] ) )
			{
				$error['line'] = $matches[1];
			}

			$context = $this->code_context( $pre_file, $error['line'] );

			//Merge the context with the other data
			if( !empty( $context ) )
			{
				$error = array_merge( $error, array( 'surrounding_code' => $context ) );
			}
		}

		//Add to the list of errors to send with the request via __destruct, or fatal_redirect
		self::$requests['errors'][] = $error;

		/**
		 * Only add the error message if we've explicity set it that way.
		 * Output in __destruct at bottom of page to prvent breakages
		 */
		if( isset( self::$settings['display'] ) && self::$settings['display'] === true )
		{
			$copy = $error;
			if( isset( $copy['surrounding_code'] ) )
			{
				unset( $copy['surrounding_code'] );
			}
			if( isset( $copy['context'] ) )
			{
				unset( $copy['context'] );
			}
			self::$display_errors[] = $copy;
			unset( $copy );
		}

		//If the error IS fatal, send everything to wasp right away
		if( $fatal === true )
		{
			$this->notify( self::$requests );

			//Initite the fatal redirect protocol
			if( !$this->reject_host() )
			{
				$this->fatal_redirect();
			}

			//If we make it to HERE, we did not redirect but should display instead
			$this->display_errors();

			//Now empty the request array
			self::$requests = array();
		}
	}
	//end log_custom_error()


	/**
	 * Function to generate logfiles
	 * Used in conjunction with wasp logging
	 * Defaults to inactive
	 * @access private
	 * @param mixed
	 */
	private function generate_logfile( $message )
	{
		//Log the errors to a file as necessary
		if( ( !empty( self::$settings['generate_log'] ) ) &&
			is_dir( self::$settings['generate_log'] ) &&
			is_writable( self::$settings['generate_log'] )
		)
		{
			$logfile = self::$settings['generate_log'] . '/wasp-log-'.date('Y-m-d').'.php';
			$log_message = '';

			if( !file_exists( $logfile ) )
			{
				$log_message .= "<"."?php if( __FILE__ == \$_SERVER['DOCUMENT_ROOT'] . \$_SERVER['SCRIPT_NAME'] ) exit( 'No direct script access allowed.' ); ?".">\n\n";
			}
			$handle = fopen( $logfile, 'a' ) or error_log( 'Cannot open file:  '.$logfile );
			if( !$handle )
			{
				return;
			}

			//Handle varying types of potential messages here
			if( is_object( $message ) )
			{
				$message = print_r( $message, true );
				$this->generate_logfile( $message );
			}
			elseif( is_array( $message ) )
			{
				$message = print_r( $message, true );
			}

			$seperator = PHP_EOL . str_repeat( '-', 50 ) . PHP_EOL;
			$message = strip_tags( $message, '<p><br>' );
			$message = str_replace( array( '<p>', '</p>', '<br />', '<br>' ), PHP_EOL, $message ) . $seperator;
			$log_message .= $message;
			fwrite( $handle, $log_message );
			fclose( $handle );
		}
	}
	//end generate_logfile()


	/**
	 * Notification function
	 * Sends data to wasp servers
	 */
	public function notify( $data )
	{
		/**
		 * Make sure we have enough data to log; this means file and line #
		 * Also check the domain to see if we should just be skipping anything beyond this point
		 */
		if( empty( self::$settings['api_key'] ) || !is_array( $data ) || empty( $data ) || $this->reject_host() )
		{
			return false;
		}

		$configuration = array_merge( self::$settings, $data );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->notification_uri );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $configuration ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );

		$response = curl_exec( $ch );
		$response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		if( $response_code > 200 )
		{
			error_log( 'Unable to send notification to Wasp.  Please check your configuration details and API key.' );
		}

		curl_close( $ch );
		return $response;
	}
	//end notify()


	/**
	 * Only send notification requests at script completion
	 * If errors are set to be displayed, they are added to the very
	 * bottom of the page
	 */
	public function __destruct()
	{
		$this->notify( self::$requests );
		self::$requests = array();

		$this->display_errors();
	}
	//end __destruct()
}