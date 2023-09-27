<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => '',
	'description' => '',
	'author' => 'Amadeus Kiener',
	'author_email' => 'a.kiener@unibrand.de',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '0.1.0',
	'constraints' => array(
		'depends' => array(
            'typo3' => '11.5 - 12.4',
            'container' => '^2.2'
    ),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);
