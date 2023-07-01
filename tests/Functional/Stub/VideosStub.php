<?php

namespace App\Tests\Functional\Stub;

use App\Service\SongProvider\Exception\SongNotFoundException;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoContentDetails;
use Google\Service\YouTube\VideoListResponse;
use Google\Service\YouTube\VideoSnippet;

class VideosStub
{
    /**
     * @param mixed[] $optParams
     */
    public function listVideos(string $part, array $optParams = []): VideoListResponse
    {
        $videoListResponse = new VideoListResponse();

        if ('should_throw_not_found' === $optParams['id']) {
            throw new SongNotFoundException();
        }

        if ('dQw4w9WgXcQ' === $optParams['id']) {
            $snippet = new VideoSnippet();
            $snippet->setTitle('dQw4w9WgXcQ_title');
            $snippet->setChannelTitle('dQw4w9WgXcQ_author');
            $contentDetails = new VideoContentDetails();
            $contentDetails->setDuration('PT0M1S');
            $video = new Video();
            $video->setSnippet($snippet);
            $video->setContentDetails($contentDetails);
            $videoListResponse->setItems([$video]);

            return $videoListResponse;
        }

        $snippet = new VideoSnippet();
        $snippet->setTitle('default_title');
        $snippet->setChannelTitle('default_author');
        $contentDetails = new VideoContentDetails();
        $contentDetails->setDuration('PT0M1S');
        $video = new Video();
        $video->setSnippet($snippet);
        $video->setContentDetails($contentDetails);
        $videoListResponse->setItems([$video]);

        return $videoListResponse;
    }
}
