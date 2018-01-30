<?php
/**
 * Created by Moonpie Studio
 * User: Administrator
 * Date: 2017/9/28
 * Time: 16:57
 */

namespace MobileBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class MobileBody {
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $code;
    /**
     * @var int
     */
    private $prevCode;
    /**
     * @var string
     */
    private $province;
    /**
     * @var string
     */
    private $city;
    /**
     * @var string
     */
    private $zipcode;
    /**
     * @var string
     */
    private $carrier;
    /**
     * @var string
     */
    private $cardType;
    /**
     * @var \DateTime
     */
    private $createTime;
    /**
     * @var \DateTime
     */
    private $updateTime;
    /**
     * @var MobileHead
     */
    private $head;
    /**
     * @var ArrayCollection
     */
    private $details;
    /**
     * @return int
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode($code) {
        $this->code = $code;
    }

    /**
     * @return int
     */
    public function getPrevCode() {
        return $this->prevCode;
    }

    /**
     * @param int $prevCode
     */
    public function setPrevCode($prevCode) {
        $this->prevCode = $prevCode;
    }

    /**
     * @return string
     */
    public function getProvince() {
        return $this->province;
    }

    /**
     * @param string $province
     */
    public function setProvince($province) {
        $this->province = $province;
    }

    /**
     * @return string
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city) {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getZipcode() {
        return $this->zipcode;
    }

    /**
     * @param string $zipcode
     */
    public function setZipcode($zipcode) {
        $this->zipcode = $zipcode;
    }

    /**
     * @return string
     */
    public function getCarrier() {
        return $this->carrier;
    }

    /**
     * @param string $carrier
     */
    public function setCarrier($carrier) {
        $this->carrier = $carrier;
    }

    /**
     * @return string
     */
    public function getCardType() {
        return $this->cardType;
    }

    /**
     * @param string $cardType
     */
    public function setCardType($cardType) {
        $this->cardType = $cardType;
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
     * @return MobileHead
     */
    public function getHead() {
        return $this->head;
    }

    /**
     * @param MobileHead $head
     */
    public function setHead($head) {
        $this->head = $head;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }
    public function __construct() {
        $this->details = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getDetails() {
        return $this->details;
    }

}