<?php

namespace Parser;

class Config
{
	public static function getConfig()
	{
		$json_decode = json_decode(file_get_contents(CONFIG));
		if (json_last_error() > 0) {
			echo json_last_error_msg() . ' ' . CONFIG . PHP_EOL;
			exit;

		}
		return $json_decode;
	}

}
