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

    /**
     * @MongoDB\Field(type="string")
     */
    private string $title;

    /**
     * @MongoDB\Field(type="string")
     */
    private string $author;

    /**
     * @MongoDB\Field(type="int")
     */
    private int $lengthInSeconds;

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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getLengthInSeconds(): int
    {
        return $this->lengthInSeconds;
    }

    public function setLengthInSeconds(int $lengthInSeconds): self
    {
        $this->lengthInSeconds = $lengthInSeconds;

        return $this;
    }
}
