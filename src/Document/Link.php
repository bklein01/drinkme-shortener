<?php

namespace App\Document;

class Link extends BaseDocument
{
    private $id;
    private $url;
    private $shortCode;
    private $redirects;
    private $userId;
    private $createdAt;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($value)
    {
        $this->url = $value;
    }

    public function getRedirects()
    {
        return $this->redirects;
    }

    public function setRedirects($value)
    {
        $this->redirects = $value;
    }

    public function getShortCode()
    {
        return $this->shortCode;
    }

    public function setShortCode($value)
    {
        $this->shortCode = $value;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($value)
    {
        $this->userId = $value;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

}
