<?php

$cs_key = '...';
$cs_secret = '...';

$oauth_token = 'id-...';
$oauth_token_secret = '...';

require_once '../Twitter.class.php';

$twitter = new Twitter($cs_key, $cs_secret, $oauth_token, $oauth_token_secret);

$stream = $twitter->getUserStream();

while($line = fgets($stream))
{
    echo $line . "\n";
}

$twitter->closeStreamSocket($stream);

var_dump($twitter->user_timeline());

