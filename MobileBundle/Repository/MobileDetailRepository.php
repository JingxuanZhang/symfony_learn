<?php
/**
 * Created by Moonpie Studio
 * User: Administrator
 * Date: 2017/9/29
 * Time: 11:39
 */

namespace MobileBundle\Repository;


use Doctrine\ORM\EntityRepository;
use MobileBundle\Entity\MobileBody;
use MobileBundle\Entity\MobileDetail;

class MobileDetailRepository extends EntityRepository {
    public function calcTotalNum(MobileBody $body) {
        $qb = $this->createQueryBuilder('d');
        $qb->select($qb->expr()->count('d.id'))
            ->join('d.body', 'b')
            ->where('b.id = :id')
            ->setParameter('id', $body->getId());
        $query = $qb->getQuery();
        return $query->getSingleScalarResult();
    }
    public function buildDetailsFor(MobileBody $body) {
        $done = 0;
        $em = $this->getEntityManager();
        //如果有1万个，则不能继续
        $string = sprintf('INSERT INTO %s(`body_id`, `content`, `has_wechat`,`create_time`, `update_time`) VALUES', 'mobile_details');
        $now = date('Y-m-d H:i:s');
        $connect = $em->getConnection();
        foreach($this->xrange(0, 9999, $body->getPrevCode()) as $code) {
            $done++;
            $array = [
                $body->getId(), "{$code}", 0, "{$now}", "{$now}"
            ];
            $array = array_map(function($item)use($connect){
                return "'{$item}'";
            }, $array);
            $string .= '('.implode(',', $array).'),';
        }
        $string = rtrim($string, ',').';';
        $connect->beginTransaction();
        try {
            $result = $connect->exec($string);
            if ($result >= 0) {
                $connect->commit();
                return true;
            } else {
                $connect->rollBack();
                return false;
            }
        }catch (\Exception $e){
            $connect->rollBack();
            throw $e;
        }
    }
    /**
     * @param     $start
     * @param     $end
     * @param string $prev
     * @param int $step
     * @return \Generator
     */
    public function xrange($start, $end, $prev, $step = 1) {
        for($i = $start; $i <= $end; $i += $step) {
            yield self::getContactCode($prev, $i);
        }
    }
    public static function getContactCode($prev, $num) {
        return sprintf("%s%04d", $prev, $num);
    }
    public function iterateUnhandleResults($limit) {
        $qb = $this->createQueryBuilder('d');
        $qb->where('d.hasCheck = ?1')
            ->setParameter(1, 0);
        $qb->setMaxResults($limit);
        $query = $qb->getQuery();
        return $query->iterate();
    }
    public function markHasWechatById($id, $hasWechat) {
        /**
         * @var MobileDetail $record
         */
        $record = $this->find($id);
        if(!$record){
            return false;
        }
        $record->setHasWechat($hasWechat);
        $record->setHasCheck(true);
        $em = $this->getEntityManager();
        $em->persist($record);
        $em->flush();
        return true;
    }

}