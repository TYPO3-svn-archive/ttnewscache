<?php

########################################################################
# Extension Manager/Repository config file for ext: "ttnewscache"
#
# Auto generated 06-05-2008 12:15
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'tt_news - extended cache management',
	'description' => 'This extension produces an array with information which part of tt_news cached views has become not up to date after latest tt_news record update/create/delete operation.',
	'category' => 'be',
	'author' => 'Krystian Szymukowicz',
	'author_email' => 'typo3@prolabium.com',
	'shy' => '',
	'dependencies' => 'tt_news',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'tt_news' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:11:{s:9:"ChangeLog";s:4:"ab2a";s:36:"class.tx_ttnewscache_tcemainproc.php";s:4:"1e08";s:21:"ext_conf_template.txt";s:4:"51b2";s:12:"ext_icon.gif";s:4:"1b31";s:17:"ext_localconf.php";s:4:"91d7";s:15:"ext_php_api.dat";s:4:"0feb";s:14:"ext_tables.php";s:4:"9ca1";s:14:"doc/manual.sxw";s:4:"2e2b";s:19:"doc/wizard_form.dat";s:4:"4654";s:20:"doc/wizard_form.html";s:4:"9a1a";s:20:"res/pageTSConfig.txt";s:4:"651e";}',
);

?>