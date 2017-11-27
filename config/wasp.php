<?php
/**
 * wasp.php
 *
 * @version 1.0
 * @date 11/26/17 11:28 PM
 * @package wasp-laravel
 */

return [

	'api_key' => env( 'WASP_API_KEY' ),

	'redirect' => false,

	'display' => false,

	'environment' => env( 'APP_ENV', 'local' ),

	'open' => '',

	'close' => '',

	'code' => false,

	'ignore' => array(),

	'ignored_domains' => array(),

	'generate_log' => false,

	'filters' => false,

	'full_backtrace' => false,
];