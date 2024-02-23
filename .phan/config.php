<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

// Namespace constants
$cfg['file_list'][] = 'Wikistories.i18n.namespaces.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'../../extensions/Echo',
		'../../extensions/BetaFeatures',
		'../../extensions/MobileFrontend',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'../../extensions/Echo',
		'../../extensions/BetaFeatures',
		'../../extensions/MobileFrontend',
	]
);

return $cfg;
