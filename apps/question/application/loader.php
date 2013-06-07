<?php

spl_autoload_register(function($class)
{
	if (strpos($class, 'DK\\Question\\') === 0) {
		require __DIR__ . '/models/' . str_replace('\\', '/', substr($class, 12)) . '.php';
	}

}, false, true);

