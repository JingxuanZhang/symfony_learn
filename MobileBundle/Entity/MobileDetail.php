<?php
/**
 * Created by Moonpie Studio
 * User: Administrator
 * Date: 2017/9/29
 * Time: 10:53
 */

namespace MobileBundle\Entity;


use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class MobileDetail {
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $content;
    /**
     * @var bool
     */
    private $hasCheck;
    /**
     * @var bool
     */
    private $hasWechat;
    /**
     * @var MobileBody
     */
    private $body;
    /**
     * @var \DateTime
     */
    private $createTime;
    /**
     * @var \DateTime
     */
    private $updateTime;
    public function syncCreateTime(LifecycleEventArgs $args){
        $date = new \DateTime();
        $this->setCreateTime($date);
        $this->setUpdateTime($date);
    }
    public function syncUpdateTime(PreUpdateEventArgs $args) {
        $date = new \DateTime();
        $this->setUpdateTime($date);
    }

    /**
     * @return mixed
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * @return boolean
     */
    public function isHasWechat() {
        return $this->hasWechat;
    }

    /**
     * @param boolean $hasWechat
     */
    public function setHasWechat($hasWechat) {
        $this->hasWechat = $hasWechat;
    }



    /**
     * @return \DateTime
     */
    public function getCreateTime() {
        return $this->createTime;
    }

    /**
     * @param \DateTime $createTime
     */
    public function setCreateTime($createTime) {
        $this->createTime = $createTime;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateTime() {
        return $this->updateTime;
    }

    /**
     * @param \DateTime $updateTime
     */
    public function setUpdateTime($updateTime) {
        $this->updateTime = $updateTime;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return MobileBody
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @param MobileBody $body
     */
    public function setBody($body) {
        $this->body = $body;
    }

    /**
     * @return boolean
     */
    public function isHasCheck() {
        return $this->hasCheck;
    }

    /**
     * @param boolean $hasCheck
     */
    public function setHasCheck($hasCheck) {
        $this->hasCheck = $hasCheck;
    }



}