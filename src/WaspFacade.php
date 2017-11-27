<?php

/**
 * WaspFacade.php
 *
 * @version 1.0
 * @date 11/27/17 9:35 AM
 * @package wasp-laravel
 */

namespace Wasp\WaspLaravel;

use Illuminate\Support\Facades\Facade;

class WaspFacade extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'wasp';
	}

}