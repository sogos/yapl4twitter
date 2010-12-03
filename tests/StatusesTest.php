<?php

require_once("../Twitter.class.php");
require_once("config.inc.php");

class StatusesTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        global $consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret;
        $this->twitter = new Twitter($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
    }

    public function testPublicTimeline()
    {
        $rep = $this->twitter->statuses_public_timeline();

        $this->assertArrayHasKey('code', $rep);
        $this->assertEquals($rep['code'], "200");

        $this->assertArrayHasKey('response', $rep);
        $tweets = json_decode($rep['response'], TRUE);

        $this->assertGreaterThan(10, count($tweets));

        $rep = $this->twitter->statuses_public_timeline(TRUE, TRUE);

        $this->assertArrayHasKey('code', $rep);
        $this->assertEquals($rep['code'], "200");

        $this->assertArrayHasKey('response', $rep);
        $tweets = json_decode($rep['response'], TRUE);

        $this->assertGreaterThan(10, count($tweets));
    }

    public function testHomeTimeline()
    {
        $rep = $this->twitter->statuses_home_timeline();
    }

    public function testUserTimeline()
    {
        $rep = $this->twitter->user_timeline();

        $this->assertArrayHasKey('code', $rep);
        $this->assertEquals($rep['code'], "200");

        $this->assertArrayHasKey('response', $rep);
        $tweets = json_decode($rep['response'], TRUE);

        $this->assertGreaterThan(10, count($tweets));
    }
}

?>
