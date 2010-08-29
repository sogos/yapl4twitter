<?php

$cs_key = '...';
$cs_secret = '...';

$oauth_token = 'id-...';
$oauth_token_secret = '...';

require_once 'Twitter.class.php';

$twitter = new Twitter($cs_key, $cs_secret, $oauth_token, $oauth_token_secret);

$rep = $twitter->update('Hello world');

var_dump($rep);
