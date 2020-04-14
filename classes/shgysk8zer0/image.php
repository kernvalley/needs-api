<?php
namespace shgysk8zer0;

final class Image extends \shgysk8zer0\PHPAPI\Image
{
	public function __construct(...$args)
	{
		trigger_error(
			sprintf('%s is deprecated. Please update use %s.', __CLASS__, parent::class),
			E_USER_DEPRECATED
		);
	}
}
