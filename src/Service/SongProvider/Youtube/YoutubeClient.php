<?php

namespace App\Service\SongProvider\Youtube;

use App\DTO\SongDTO;
use App\Service\SongProvider\Exception\SongNotFoundException;
use App\Service\SongProvider\SongProviderInterface;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;

class YoutubeClient implements SongProviderInterface
{
    public function __construct(
        private YouTube $youtubeService
    ) {
    }

    /**
     * @throws \Exception
     */
    public function getSong(mixed $resource): SongDTO
    {
        $videoListResponse = $this->youtubeService->videos->listVideos(
            'snippet,contentDetails',
            [
                'id' => $resource,
                'maxResults' => 1,
            ]
        );

        if (!isset($videoListResponse->getItems()[0])) {
            throw new SongNotFoundException();
        }

        $video = $videoListResponse->getItems()[0];

        return $this->videoToSongDTO($video);
    }

    /**
     * @return array<SongDTO>
     */
    public function getSongsFromPlaylist(mixed $resource): array
    {
        $playlistItems = $this->youtubeService->playlistItems->listPlaylistItems(
            'snippet,contentDetails',
            [
                'playlistId' => $resource,
                'maxResults' => 50,
            ]
        );

        if (!isset($playlistItems->getItems()[0])) {
            throw new SongNotFoundException();
        }

        $ids = implode(',', array_map(function ($playlistItem) {
            return $playlistItem->getContentDetails()->getVideoId();
        }, $playlistItems->getItems()));

        $videoListResponse = $this->youtubeService->videos->listVideos(
            'snippet,contentDetails',
            [
                'id' => $ids,
                'maxResults' => 50,
            ]
        );

        $songs = [];

        foreach ($videoListResponse->getItems() as $video) {
            $songs[] = $this->videoToSongDTO($video);
        }

        return $songs;
    }

    private function videoToSongDTO(Video $video): SongDTO
    {
        $duration = $video->getContentDetails()->getDuration();
        $duration = new \DateInterval($duration);
        $duration = $duration->days * 86400 + $duration->h * 3600 + $duration->i * 60 + $duration->s;

        return new SongDTO(
            $video->getId(),
            $duration,
            $video->getSnippet()->getTitle(),
            $video->getSnippet()->getChannelTitle()
        );
    }
}
