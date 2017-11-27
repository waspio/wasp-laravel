<?php

/**
 * Wasp.php
 *
 * @version 1.0
 * @date 11/27/17 5:26 AM
 * @package wasp-laravel
 */

namespace Wasp\Reporting\Facades;

use Illuminate\Support\Facades\Facade;

class Wasp extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'wasp';
	}
}