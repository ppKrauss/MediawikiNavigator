<?php
/**
 * use: php shell_demo1.php | more
 * use: php shell_demo1.php html | more
 */

// CONFIGS:
  $urlWiki = 'http://example.org/wiki';
  $P       = 'TEST';

include "MediawikiNavigator.php";
$mn = new MediawikiNavigor($urlWiki);

if ($argc>1)
	echo $mn->get("/$P");
else
	echo $mn->getByTitle('raw',$P); 


