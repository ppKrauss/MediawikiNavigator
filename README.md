# MediawikiNavigator
Simple and manutenable POST/GET access with Cookies, oriented to [file_get_contents()](http://php.net/manual/en/function.file-get-contents.php).

Minimized and easy to use: do [Mediawiki 1.16+ authentication](http://www.mediawiki.org/wiki/API:Login) (using API), store the login cookies, and manage POST and GET access.

## Usage ##

```php
<?php
include "MediawikiNavigator.php";

$mn = new MediawikiNavigor('http://www.myWiki.org/caWiki', 'myuser', 'passwd');
$wikipage = $mn->get('/My_wiki_page');

echo $wikipage;
```
#### Variations ####
```php
<?php
include "MediawikiNavigator.php";
$mn = new MediawikiNavigor();
$mn->base_url = 'http://www.myWiki.org/caWiki';
$mn->login('myuser', 'passwd'); 
var_dump($mn->cookies);

$pag1 = $mn->get('?action=render&title=My_wiki_page1');
$status = $mn->post($data,'?action=edit&title=My_wiki_page1');

$pag2 = $mn->get('/My_wiki_page2');
$pag3 = $mn->get('/api.php?query&format=xml&prop=categories&titles=My_wiki_page1');
...
// Works with HTTPS,
$mn2 = new MediawikiNavigor('https://en.wikipedia.org/wiki');
$pag4 = $mn2->get('/Commerce');
...
```
## Methods and public variables ##

The Wiki URL and buffers are plublic: `$mn->base_url`,`$mn->cookies`,`$mn->options`. 

Open Wiki not need login for GET, you can use directly `file_get_contents()`.
For "closed Wiki" and for special uses with API and/or another URLs, you need authentication, using `$wbuff->login()` or doing it at the constructor.

For GET and POST use relative URL (about `base_url`).
For simple GET use `$content = $wbuff->get($relatUrl)`.
The POST method need some `$data`,  `$content = $wbuff->post($data,$relatUrl)`.


