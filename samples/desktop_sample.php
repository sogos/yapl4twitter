<?php

$cs_key = '...';
$cs_secret = '...';

$request_token_url = 'https://api.twitter.com/oauth/request_token';
$access_token_url = 'https://api.twitter.com/oauth/access_token';
$authorize_url = 'https://api.twitter.com/oauth/authorize';

require_once('OAuth.class.php');

$oa = new OAuth($cs_key, $cs_secret);

// En utilisant une url de callback 'oob', vous obtiendrez un PIN pour compléter.
// Le PIN sera à considérer comme la valeur de l'oauth_verifier que l'on aurait obtenu
// si l'on avait utilisé une url de callback à la place.
$request_token = $oa->getRequestToken($request_token_url, 'oob');
/* Cela renvoie:
array(3) {
  ["oauth_token"]=>
  string(40) "..."
  ["oauth_token_secret"]=>
  string(42) "..."
  ["oauth_callback_confirmed"]=>
  string(4) "true"
}
*/
echo "You must go on " . $authorize_url . '?oauth_token=' . $request_token['oauth_token'] . "\n";

echo "Please give PIN number: ";
$fp = fopen('php://stdin', 'r');
$pin = trim(fgets($fp));

$access_token = $oa->getAccessToken($access_token_url, $request_token['oauth_token'], $pin);

echo "Result:\n";
print_r($access_token);

/*
array(4) {
  ["oauth_token"]=>
  string(50) "id-..."
  ["oauth_token_secret"]=>
  string(41) "..."
  ["user_id"]=>
  string(9) "..."
  ["screen_name"]=>
  string(7) "..."
}
*/

