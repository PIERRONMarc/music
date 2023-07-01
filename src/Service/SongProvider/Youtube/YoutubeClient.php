<?php

namespace App\Service\SongProvider\Youtube;

use App\DTO\SongDTO;
use App\Service\SongProvider\Exception\SongNotFoundException;
use App\Service\SongProvider\SongProviderInterface;
use DateInterval;
use Exception;
use Google\Service\YouTube;

class YoutubeClient implements SongProviderInterface
{
    public function __construct(
       private YouTube $youtubeService
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
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

        $duration = $video->getContentDetails()->getDuration();
        $duration = new DateInterval($duration);
        $duration = $duration->days * 86400 + $duration->h * 3600 + $duration->i * 60 + $duration->s;

        return new SongDTO(
            (string) $resource,
            $duration,
            $video->getSnippet()->getTitle(),
            $video->getSnippet()->getChannelTitle()
        );
    }
}
