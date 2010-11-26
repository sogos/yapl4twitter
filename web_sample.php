<?php

/* Twitter api key and secret key */
$cs_key = 'REPLACE WITH YOUR API KEY/CONSUMER KEY';
$cs_secret = 'REPLACE WITH YOUR CONSUMER SECRET';

/* Callback url points to this script: */
$callback_url = 'http://www.example.com/web_sample.php';
// Ex:
// $callback_url = 'http://www.mkz.me/~mycroft/twitter-test/web_sample.php';


/* Do not modify following code */

require_once('OAuth.class.php');
require_once('Twitter.class.php');

$request_token_url = 'https://api.twitter.com/oauth/request_token';
$access_token_url = 'https://api.twitter.com/oauth/access_token';
$authorize_url = 'https://api.twitter.com/oauth/authorize';

session_start();

$oa = new OAuth($cs_key, $cs_secret);

$_verifier = $_REQUEST['oauth_verifier'];
$_token = $_SESSION['token'];
$_key = $_SESSION['key'];

if( $_REQUEST['clean'] == 1)
{
    session_destroy();
    $_token = $_key = NULL;

    echo "Session cleaned."; die();
}

/* 1st step: ask for token and redirect to twitter for authorization */
if( ! $_verifier && ! $_key )
{
    $request_token = $oa->getRequestToken($request_token_url, $callback_url);
    $_SESSION['token'] = $request_token['oauth_token'];

    header('Location: ' . $authorize_url . '?oauth_token=' . $request_token['oauth_token']);
}
/* 2nd step: validate given token from twitter after user accepts */
elseif( $_verifier && ! $_key )
{
    $access_token = $oa->getAccessToken($access_token_url, $_token, $_verifier);
    $_SESSION['token'] = $access_token['oauth_token'];
    $_SESSION['key'] = $access_token['oauth_token_secret'];
    $_SESSION['nick'] = $access_token['screen_name'];

    header('Location: ' . $callback_url);
}
elseif( $_key && $_SERVER['REQUEST_METHOD'] == 'GET')
{
    /* Do a twitter call */
    $twitter = new Twitter($cs_key, $cs_secret, $_token, $_key);

    $timeline = $twitter->user_timeline();

    if($timeline['code'] != "200")
    {
        echo "Error:";
        var_dump($timeline);
        die();
    }

    $statuses = json_decode($timeline['response'], TRUE);

    foreach($statuses as $status)
    {
        echo $status['text'] . "<br />";
    }
}


