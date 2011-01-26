<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	'modules' => array(

		// This should be the path to this modules userguide pages, without the 'guide/'. Ex: '/guide/modulename/' would be 'modulename'
		'auto-modeler' => array(

			// Whether this modules userguide pages should be shown
			'enabled' => TRUE,
			
			// The name that should show up on the userguide index page
			'name' => 'Auto Modeler',

			// A short description of this module, shown on the index page
			'description' => 'Auto-Modeler CRUD/ORM library',
			
			// Copyright message, shown in the footer for this module
			'copyright' => '&copy; 2008–2011 Kohana Team',
		)	
	)
);
