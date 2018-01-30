<?php

namespace MobileBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\PreUpdateEventArgs;

/**
 * MobileHead
 */
class MobileHead
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int 手机号前3位
     */
    private $code;

    /**
     * @var \DateTime
     */
    private $createTime;

    /**
     * @var \DateTime
     */
    private $updateTime;
    /**
     * @var string 城市缩写
     */
    private $domain;

    /**
     * @var ArrayCollection
     */
    private $bodies;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param integer $code
     *
     * @return MobileHead
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     *
     * @return MobileHead
     */
    public function setCreateTime($createTime)
    {
        $this->createTime = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return \DateTime
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * Set updateTime
     *
     * @param \DateTime $updateTime
     *
     * @return MobileHead
     */
    public function setUpdateTime($updateTime)
    {
        $this->updateTime = $updateTime;

        return $this;
    }

    /**
     * Get updateTime
     *
     * @return \DateTime
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }
    public function syncCreateTime(LifecycleEventArgs $av) {
        $date = new \DateTime();
        $this->setCreateTime($date);
        $this->setUpdateTime($date);
    }
    public function syncUpdateTime(PreUpdateEventArgs $av) {
        $date = new \DateTime();
        $this->setUpdateTime($date);
    }

    /**
     * @return string
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain) {
        $this->domain = $domain;
    }
    public function __construct() {
        $this->bodies = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getBodies() {
        return $this->bodies;
    }
}

