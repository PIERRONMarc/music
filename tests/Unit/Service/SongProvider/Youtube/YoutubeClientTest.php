<?php

namespace App\Tests\Unit\Service\SongProvider\Youtube;

use App\DTO\SongDTO;
use App\Service\SongProvider\Exception\SongNotFoundException;
use App\Service\SongProvider\Youtube\YoutubeClient;
use Generator;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Videos;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoContentDetails;
use Google\Service\YouTube\VideoListResponse;
use Google\Service\YouTube\VideoSnippet;
use PHPUnit\Framework\TestCase;

class YoutubeClientTest extends TestCase
{
    public function provideSongDTO(): Generator
    {
        yield [
            'expectedSong' => new SongDTO(
                '1',
                1,
                'title',
                'author'
            ),
        ];
        yield [
            'expectedSong' => new SongDTO(
            '2',
            1,
            'anotherTitle',
            'anotherAuthor'
            ),
        ];
    }

    /**
     * @dataProvider provideSongDTO
     */
    public function testWhenGetSongThenSongIsReturned(SongDTO $expectedSong): void
    {
        $youtubeService = $this->createMock(YouTube::class);
        $snippet = new VideoSnippet();
        $snippet->setTitle($expectedSong->title);
        $snippet->setChannelTitle($expectedSong->author);
        $contentDetails = new VideoContentDetails();
        $contentDetails->setDuration('PT0M'.$expectedSong->lengthInSeconds.'S');
        $video = new Video();
        $video->setSnippet($snippet);
        $video->setContentDetails($contentDetails);
        $expectedVideoResponse = $this->createMock(VideoListResponse::class);
        $expectedVideoResponse
            ->method('getItems')
            ->willReturn([$video]);
        $youtubeService->videos = $this->createMock(Videos::class);
        $youtubeService->videos
            ->method('listVideos')
            ->willReturn($expectedVideoResponse);
        $youtubeClient = new YoutubeClient($youtubeService);

        $song = $youtubeClient->getSong($expectedSong->id);

        $this->assertEquals($song, $expectedSong);
    }

    public function testWhenGetUnexistingSongThenSongNotFoundExceptionIsThrowed(): void
    {
        $youtubeService = $this->createMock(YouTube::class);
        $expectedVideoResponse = $this->createMock(VideoListResponse::class);
        $expectedVideoResponse
            ->method('getItems')
            ->willReturn([]);
        $youtubeService->videos = $this->createMock(Videos::class);
        $youtubeService->videos
            ->method('listVideos')
            ->willReturn($expectedVideoResponse);
        $youtubeClient = new YoutubeClient($youtubeService);

        $this->expectException(SongNotFoundException::class);

        $youtubeClient->getSong('some_id');
    }
}
