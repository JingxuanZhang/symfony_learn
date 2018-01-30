<?php
/**
 * Created by Moonpie Studio
 * User: Administrator
 * Date: 2017/9/28
 * Time: 17:21
 */

namespace MobileBundle\Repository;


use Doctrine\ORM\EntityRepository;
use MobileBundle\Entity\MobileBody;
use MobileBundle\Entity\MobileHead;

class MobileBodyRepository extends EntityRepository {
    public function saveBodyItems(MobileHead $head, array $items) {
        $em = $this->getEntityManager();
        foreach($items as $key=>$item) {
            if($this->hasExistsByCode($head, $item['code'])){
                unset($items[$key]);
            }else{
                $body = new MobileBody();
                $body->setCode($item['code']);
                $body->setCardType($item['card_type']);
                $body->setCarrier($item['carrier']);
                $body->setCity($item['city']);
                $body->setProvince($item['province']);
                $body->setPrevCode($item['prev_code']);
                $body->setZipcode($item['zipcode']);
                $body->setHead($head);
                $em->persist($body);
                $em->flush();
            }
        }
        return $items;
    }
    public function hasExistsByCode(MobileHead $head, $code) {
        return $this->getExistsNumByHead($head, $code) > 0;
    }
    public function getExistsNumByHead(MobileHead $head, $code) {
        $qb = $this->createQueryBuilder('b');
        $qb->select($qb->expr()->count('b.id'))
            ->join('b.head', 'h')
            ->where('h.id = :id and b.code = :code')
            ->setParameter('id', $head->getId())
            ->setParameter('code', $code);
        $query = $qb->getQuery();
        return $query->getSingleScalarResult();
    }
    public function loadByPrevCode($code) {
        $qb = $this->createQueryBuilder('b');
        $qb->where('b.prevCode = :code')
            ->setParameter('code', $code);
        $query = $qb->getQuery();
        try {
            return $query->getSingleResult();
        }catch (\Exception $e){
            return false;
        }
    }

    public function hasFillAllBy(MobileBody $body) {
        $qb = $this->createQueryBuilder('b');
        $qb->join('b.details', 'd')
            ->select($qb->expr()->count('d.id'))
            ->where('b.id = :id')
            ->setParameter('id', $body->getId());
        $query = $qb->getQuery();
        return $query->getSingleScalarResult() >= 10000;
    }
}