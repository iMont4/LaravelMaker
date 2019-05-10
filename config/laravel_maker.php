<?php

return [
	'route_kinds'      => [
		'Frontend', // views (Web, Panel)
		'Web', // Web api
		'Panel', // Panel api
		'App', // Application api
	],
	'route_kind_default' => 'Web',
	'locales'    => [
		'en',
		'fa',
	],
	'user_model' => App\User::class,
];