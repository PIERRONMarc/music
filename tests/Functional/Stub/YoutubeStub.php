<?php

namespace App\Tests\Functional\Stub;

class YoutubeStub extends \Google_Service_YouTube
{
    /**
     * @var VideosStub
     */
    public $videos;

    public function __construct()
    {
        $this->videos = new VideosStub();
    }
}
