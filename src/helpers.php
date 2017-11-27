<?php
/**
 * helpers.php
 *
 * @version 1.0
 * @date 11/27/17 9:30 AM
 * @package samsung
 */

use Wasp\WaspLaravel\WaspHandler;
if( !function_exists( 'wasp' ) )
{
	/**
	 * @return \Illuminate\Foundation\Application|mixed
	 */
	function wasp()
	{
		return app( WaspHandler::class );
	}
}