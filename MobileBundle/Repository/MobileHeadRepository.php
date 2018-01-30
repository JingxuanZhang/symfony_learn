<?php

namespace MobileBundle\Repository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use MobileBundle\Entity\MobileBody;
use MobileBundle\Entity\MobileHead;

/**
 * MobileHeadRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MobileHeadRepository extends EntityRepository
{
    public function getListByName($cityName) {
        $builder = $this->createQueryBuilder('h');
        $builder->where('h.domain = :main')->setParameter('main', $cityName);
        $return = $builder->getQuery()->getResult();
        return $return;
    }
    public function saveDomainNum($domain, $nums) {
        $em = $this->getEntityManager();
        try{
            foreach ($nums as $num){
                //检测组合存在则提示不通过
                $qb = $this->createQueryBuilder('h');
                $total = $qb->where('h.code = ?1')->andWhere('h.domain = :domain')
                    ->setParameter(1, $num)
                    ->setParameter('domain', $domain)
                    ->select($qb->expr()->count('h.id'))
                    ->getQuery()->getResult();
                if($total > 0){
                    continue;
                }
                $body = new MobileHead();
                $body->setCode($num);
                $body->setDomain($domain);
                $em->persist($body);
                $em->flush();
            }
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    public function loadByCodeAndDomain($code, $domain) {
        $qb = $this->createQueryBuilder('h');
        $qb->where('h.code = :code and h.domain = :domain')
            ->setParameter('code', $code)
            ->setParameter('domain', $domain);
        $query = $qb->getQuery();
        return $query->getSingleResult();
    }
}
