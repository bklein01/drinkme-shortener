<?php

namespace App\Document;

class Link extends BaseDocument
{
    protected $_id;
    protected $url;
    protected $shortCode;
    protected $redirects;
    protected $createdAt;

    public function getId()
    {
        return $this->_id;
    }

    public function setId($value)
    {
        $this->_id = $value;
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

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

}
