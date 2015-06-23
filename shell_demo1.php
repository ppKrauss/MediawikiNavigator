<?php
/**
 * use: php shell_demo1.php | more
 * use: php shell_demo1.php html | more
 */

// CONFIGS:
  $urlWiki = 'http://example.org/wiki';
  $P       = 'TEST';
  $normalize = TRUE;

include "MediawikiNavigator.php";

$mn = new MediawikiNavigor($urlWiki);

if ($argc>1)
	echo $mn->get("/$P");
else{
	$mn->getByTitle('raw',$P);
	if ($normalize) {
		$mn->wikitextTpl_tokenize();
		$mn->wikitextTpl_untokenize();
	}
	echo $mn->wikitext;
}

