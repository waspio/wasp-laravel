<?php
/**
 * helpers.php
 *
 * @version 1.0
 * @date 11/26/17 11:12 PM
 * @package wasp-laravel
 */
use Wasp\Reporting\Wasp;

if( !function_exists( 'wasp' ) )
{
	/**
	 * @return mixed
	 */
	function wasp()
	{
		return app(Wasp::class);
	}
}