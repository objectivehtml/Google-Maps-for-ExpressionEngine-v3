<?php

if (! defined('MATRIX_VER'))
{
	define('MATRIX_NAME', 'Matrix');
	define('MATRIX_VER',  '2.2.4');
}

$config['name']    = MATRIX_NAME;
$config['version'] = MATRIX_VER;
$config['nsm_addon_updater']['versions_xml'] = 'http://pixelandtonic.com/matrix/releasenotes.rss';
