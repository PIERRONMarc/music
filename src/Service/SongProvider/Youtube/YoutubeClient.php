<?php

namespace App\Service\SongProvider\Youtube;

use App\DTO\SongDTO;
use App\Service\SongProvider\SongProviderInterface;
use DateInterval;
use Exception;
use Google\Service\YouTube;

class YoutubeClient implements SongProviderInterface
{
    public function __construct(
       private  YouTube $youtubeService
    ) {}

    /**
     * @inheritDoc
     *
     * @param $resource
     * @return SongDTO
     * @throws Exception
     */
    public function getSong($resource): SongDTO
    {
        $videoListResponse = $this->youtubeService->videos->listVideos(
            'snippet,contentDetails',
            [
                'id' => $resource,
                'maxResults' => 1,
            ]
        );
        $video = $videoListResponse->getItems()[0];

        $duration = $video->getContentDetails()->getDuration();
        $duration = new DateInterval($duration);
        $duration = $duration->days * 86400 + $duration->h * 3600 + $duration->i * 60 + $duration->s;

        return new SongDTO(
            $resource,
            $duration,
            $video->getSnippet()->getTitle(),
            $video->getSnippet()->getChannelTitle()
        );
    }
}
