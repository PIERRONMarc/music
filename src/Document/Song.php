<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @MongoDB\EmbeddedDocument()
 */
class Song
{
    /**
     * @MongoDB\Id()
     */
    private string $id;

    /**
     * @MongoDB\Field(type="string")
     */
    private string $url;

    /**
     * @MongoDB\Field(type="bool")
     * @SerializedName("isPaused")
     */
    private bool $isPaused = false;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getIsPaused(): bool
    {
        return $this->isPaused;
    }

    public function setIsPaused(bool $isPaused): self
    {
        $this->isPaused = $isPaused;

        return $this;
    }
}
