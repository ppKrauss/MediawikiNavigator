<?php
/**
 * Mediawiki Navigor class. Simple and manutenable cookie buffer and POST/GET access;
 * oriented to file_get_contents() use.
 * @version 1.0 2015-01-30
 * @licence MIT
 * @author ppkrauss
 * @see  https://github.com/ppKrauss/MediawikiNavigor
 *
 * EXAMPLE:
 *  $wbuff = new MediawikiNavigor('http://www.mywiki.org/wiki', 'MyUser', 'MyPass');
 *  var_dump($wbuff->cookies);
 *  $wikipage = $wbuff->get('/Test_page'); // or '/index.php?action=render&title=Test_page';
 */
class MediawikiNavigor {
	var $base_url = ''; // default wiki or empty
	var $options = NULL; // refresh with options() method
	var $cookies = NULL; // refresh with login() method

	/**
	 * Constructor.
	 * @param $base_url string base_url (starting with HTTP).
	 * @param $lgname string username.
	 * @param $lgpassword string password.
	 * @return boolean false on error.
	 */
	function __construct($base_url='',$lgname='',$lgpassword='') {
		$ok = true;
		if ($base_url) $this->base_url=$base_url;
		if ($lgname)
			$ok = $this->login($lgname,$lgpassword);
		if (!$ok)
			die("LOGIN ERROR"); 
	}

	/**
	 * Login with api.php in the base_url.
	 * @param $lgname string username.
	 * @param $lgpassword string password.
	 * @return boolean false on error.
	 */
	function login($lgname,$lgpassword) {
		$api = '/api.php';
		$data = array('action'=>'login', 'lgname'=>$lgname, 'lgpassword'=>$lgpassword,'format'=>'php');
		$this->cookies = NULL; // clean
		$wikisec = unserialize( $this->post($data,$api) );
		if ($wikisec['login']['result']=='NeedToken'){ // see http://www.mediawiki.org/wiki/API:Login#Confirm_token
			$data['lgtoken'] = $wikisec['login']['token'];
			$ck_prefix = $wikisec['login']['cookieprefix'];
			$this->cookies = array("{$ck_prefix}_session"=>$wikisec['login']['sessionid']);
			$wikisec = unserialize( $this->post($data,$api) );
		}
		if ($wikisec['login']['result']!='Success')
			return false; //die("LOGIN ERROR");
		else {
			$ck_prefix = $wikisec['login']['cookieprefix'];
			$this->cookies = array(
				"{$ck_prefix}_session"	=>$wikisec['login']['sessionid'], 
				"{$ck_prefix}UserName"	=>$wikisec['login']['lgusername'], 
				"{$ck_prefix}UserID"	=>$wikisec['login']['lguserid'], 
				"{$ck_prefix}Token"	=>$wikisec['login']['lgtoken']
			);
			return true;
		}
	} // func

	function options($data=NULL){
		if ( ($cookies = $this->cookies) !== NULL ) {
			$cookies = join('; ', array_map(
				function($key) use ($cookies) { return "$key={$cookies[$key]}"; },
				array_keys($cookies)
			));
			$cookies = "Cookie: $cookies\r\n";
		}
		$this->options = $data?
			array(
			    'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n".((string) $cookies),
				'method'  => 'POST',
				'content' => http_build_query($data),
			))
			: ($cookies? array('http'=>array('header'=>$cookies)): NULL);  // GET
	} // func

	/**
	 * Execute file_get_contents() with POST method in the base_url. Use $this->cookies.
	 * @param $data array or NULL.
	 * @param $relat_url string (optional) URL relative for base_url.
	 * @param $refreshOptions boolean (default=true) for refresh the options.
	 * @return string with contents, or FALSE on failure.
	 */
	function post($data,$relat_url='',$refreshOptions=true) {
		$url = $this->base_url.$relat_url;
		if ($refreshOptions) $this->options($data); // $data can be NULL
		return is_array($this->options)? 
			file_get_contents($url, false, stream_context_create($this->options) ): // GET or POST
			file_get_contents($url); // GET
	}

	/**
	 * Wrap method for post(NULL,$relat_url).
	 * Execute file_get_contents() with GET method in the base_url. Use $this->cookies.
	 * @param $relat_url string (optional) URL relative for base_url.
	 * @return string with contents, or FALSE on failure.
	 */
	function get($relat_url='',$refreshOptions=true) {
		return $this->post(NULL,$relat_url,$refreshOptions);
	}



	// // // // // // // // // // // // // //
	// // (less important) BEGIN:UTIL_METHODS

	/**
REVISAR: gerenciar array e compor saídas, conferir com outras libs e usar $this->assoc_join($a,'&')


	 * Wrap method for Mediawiki's api.php and index.php, with one title at a time.
	 * @param $cmd string local convention about commands.
	 * @param $title string article's title.
	 * @param $format string to be returned (xml, html, json, php, wikitext).
	 * @return string with contents, or FALSE on failure.
	 */
	function getByTitle($cmd,$title='',$format='') {
		$article = $this->pageInUse_check($title);
			// falta api.php?format=xml&action=query&prop=extracts&titles=
			//api.php?action=query&prop=revisions&format=json&titles=Main_page&rvprop=timestamp|user|comment|content
		if ($cmd=='raw') $format='wikitext';
		$ret = FALSE;
		if (!$format || $format=='html') switch ($cmd) {

		case 'full':  $ret = $this->get("/index.php?$article");
			break;
		case 'render': $ret = $this->get("/index.php?action=render&$article");
			break;
		case 'pageCategs':
			$ret = $this->get("/api.php?action=query&format=html&prop=categories&titles={$this->pageInUse}");
			break;
		default: die("\nERRO getByTitle($cmd com $format)");

		} else switch ($cmd) {
		case 'raw': // wikitext, see http://www.mediawiki.org/wiki/Manual:Parameters_to_index.php#Raw
			$ret = $this->get("/index.php?action=raw&$article");
			break;
		// raw-expanded de api.php?action=expandtemplates ... ver http://www.mediawiki.org/wiki/API:Parsing_wikitext
		case 'pageCategs':
			$ret = $this->get("/api.php?action=query&prop=categories&format=$format&titles={$this->pageInUse}");
			break;
		default: die("\nERRO getByTitle($cmd com $format)");
		}

		if ($format=='php')
			return unserialize($ret);
		else
			return $ret;
	}

	/**
	 * Extracts only the category names (ns=14) from requested data.
	 * @return array of names.
	 */
	function getPageCategsNames($title='') {
		$all = $this->getByTitle('pageCategs',$title,'xml');
		preg_match_all('/ns="14" title="[^:"]+:([^"]+)"/s', $all, $m);
		return $m[1];
	}

	/**
	 * Wrap method for Mediawiki's "api.php?prop=info" with one title at a time.
	 * @see http://www.mediawiki.org/wiki/API:Properties#info_.2F_in
	 * @param $title string article's title.
	 * @param $prop string '' (to return all props) or the prop name (ex. 'touched').
	 * @return array, value or NULL on error.
	 */
	function info($title='',$prop='') {
		$article = $this->pageInUse_check($title,'s');
		$r = unserialize( $this->get("/api.php?format=php&action=query&prop=info&$article") );
		if (isset($r['query']['pages'])) {
			$page = array_pop( $r['query']['pages'] );
			return $prop? $page[$prop]: $page;
		} else
			return NULL;
	} // func


	/**
	AQUI remover Rapidom e usar lib mais simples para carregar HTML livre e devolver XML.
	 * Lists all the main-ns items of a category.
	 * @param $categName string category's name.
	 * @return array with all items.
	 */
	function category_listOfItems($categName, $classOfDiv='mw-content-ltr') {
		$list = array();
		$wikipage = $this->getByTitle('render',"Category:$categName");
		//print "\n$wikipage";
		$rdoc = new RapiDOM("<html>$wikipage</html>");
		$xp = new DOMXpath($rdoc->dom);
		// $nodes = $xp->query("//div[@class='$classOfDiv']//table//ul/li//a/@title[not(contains(.,':'))]");
		$nodes = $xp->query("//div[@dir='ltr']//ul/li//a/@title[not(contains(.,':'))]");
		foreach (iterator_to_array($nodes) as $i)
			$list[] = $i->nodeValue;
		return $list;
	} // func


	/**
	 * Edits an article.
	 * @param $newWikiText string text to be replaced.
	 * @param $title string text to be replaced.
	 * @return array with all items.
	 */
	function edit($newWikiText, $title='', $summary='#mn-edit') {
		$article = $this->pageInUse_check($title);
		$r = unserialize( $this->get("/api.php?action=query&format=php&meta=tokens") );
		$token = $r['query']['tokens']['csrftoken'];
		$data = array( 'summary'=>$summary, 'text'=>$newWikiText, 'token'=>$token );
		$r = unserialize( $this->post($data,"/api.php?action=edit&format=php&$article") );
		return isset($r['edit']['result'])? ($r['edit']['result']=='Success'): -1;
		//<edit new="" result="Success" pageid="166" title="..." contentmodel="wikitext" oldrevid="0" newrevid="403" newtimestamp="2015-02-17T21:50:54Z" />
	}

	function pageInUse_check($title,$title_sufix='') {
		$title=str_replace(' ','_',trim($title));
		if ($title)
			list($this->pageInUse, $this->pageInUse_idtype) = array($title,"title$title_sufix");
		return "{$this->pageInUse_idtype}={$this->pageInUse}";
	}

	// // END:UTIL_METHODS
	// // // // // // // // // // // // //


	// // // // // // // // // // // // // //
	// // BEGIN:ARRAY_TOOLS

	/**
	 * Joins a set of key-value pairs by $pairSep, and all pairs of the set by $sep.
	 * NOTE: assoc_join($a,'&') is similar to http_build_query($a).
	 * @param $a mix NULL or array (handdled as reference) to be joined.
	 * @param $sep string (default '; ') separator in the final string.
	 * @param $pairSep string (default '=') pair separator (joins key-val pair).
	 * @return mix NULL if $a is NULL, string if $a is array. 
	 */
	private function assoc_join(&$a,$sep='; ',$pairSep='=') {
		return is_array($a)? 
			join($sep, array_map(
				function($key) use ($a,$pairSep) {
					$key = trim($key);
					return "$key$pairSep{$a[$key]}"; 
				},
				array_keys($a)
			)):
			NULL;
	}

	/**
	 * Replaces or appends array. On key-conflicts, use the appended values.
	 * @param $a mix NULL or array (handdled as reference) to be changed.
	 * @param $append mix, string in the form "key=value", or associative array.
	 */
	private function assoc_merge(&$a,$append) {
		if ( is_string($append) && count($append=explode('=',$append)) )
			$append = array($append[0]=>$append[1]);
		elseif (!is_array($append))
			$append = array();
		$a = ($a===NULL)? $append: array_merge($append,$a);
	}

	/**
	 * Rename keys of an associative array. On key-conflicts, use the flag to decide.
	 * @param $a mix NULL or array (handdled as reference) to be changed.
	 * @param $rename associative array with the pairs oldKey-newKey.
	 * @param $renameOverride boolean (default true) to decide override on conflicts with new keys.
	 */
	private function assoc_rename(&$a, $rename, $renameOverride=true) {
		if (!is_array($a) || !is_array($rename))
			return false;
		foreach ($rename as $key=>$newKey) 
			if ( array_key_exists($key, $a) && ($renameOverride || !array_key_exists($newKey, $a)) ) {
				$a[$newKey] = $a[$key];
				unset($a[$key]);
			}
		return true;
	} // func

	/**
	 * Removes key-value pairs from array, by keys.
	 * @param $a mix NULL or array (handdled as reference) to be changed.
	 * @param $keys mix, string with the key, or array of keys.
	 */
	private function assoc_unset(&$a,$keys) {
		if ( $a!==NULL ) {
			if (!is_array($keys))
				$keys=array($keys);
			foreach ($keys as $key) if (array_key_exists($key,$a))
				unset($a[$key]);
		}
	}
	// // END:ARRAY_TOOLS
	// // // // // // // // // // // // //

} // class

?>
