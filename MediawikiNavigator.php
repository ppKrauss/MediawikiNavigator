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

} // class

?>
