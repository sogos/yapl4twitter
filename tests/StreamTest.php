<?php

require_once("../Twitter.class.php");
require_once("config.inc.php");

class StreamTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        global $consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret;
        $this->twitter = new Twitter($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
    }

    public function testFirehoseSample()
    {
        $fd = $this->twitter->getFirehoseSample();

        $this->assertInternalType('resource', $fd);

        $tweet = json_decode(trim(fgets( $fd )), TRUE);

        /* XXX test 'tweet' ? */

        $this->twitter->closeStreamSocket($fd);
    }
}
