<?php

namespace DK\Question\Output;

class Oppose extends \DK_Controller
{
	function onFailure($input, $valid)
	{
		echo '这里出错了';
	}

	function onSuccess($input, $action)
	{
		echo '这里是index';
	}
}
