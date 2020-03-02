<?php
namespace User\Entity;

use \DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="token")
 */
class Token
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $userId;

    /**
     * @ORM\Column(type="string")
     */
    protected $username;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private $IP;

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $sessionID;

    /**
     * @ORM\Column(type="integer", length=35)
     */
    private $timestamp;

    /**
     * @ORM\Column(type="string", length=35)
     */
    private $token;

    public function __construct() {
        $datetime = new DateTime("now");
        $this->setTimestamp($datetime->getTimestamp());
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function getIP()
    {
        return $this->IP;
    }

    public function setIP($value)
    {
        $this->IP = $value;
        return $this;
    }

    public function getSessionID()
    {
        return $this->sessionID;
    }

    public function setSessionID($value)
    {
        $this->sessionID = $value;
        return $this;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp($value)
    {
        $this->timestamp = $value;
        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($value)
    {
        $this->token = $value;
        return $this;
    }

}
