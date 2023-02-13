<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Shareholder as Entity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Shareholder|null find($id, $lockMode = null, $lockVersion = null)
 * @method Shareholder|null findOneBy(array $criteria, array $orderBy = null)
 * @method Shareholder[]    findAll()
 * @method Shareholder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShareholderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entity::class);
    }

    public function add(Entity $entity, bool $flush = false): void
    {
        if (!count($entity->getData())) {
            $data = [
                'country' => $entity->getSubsidiary()->getCountry(),
                'name' => $entity->getSubsidiary()->getFullname(),
                'direct' => $entity->getDirect(),
                'total' => $entity->getTotal()
            ];
            $entity->setData($data);
        }
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function remove(Entity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->flush();
        }
    }

    public function findSubsidiariesByHolder(Company $company)
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.subsidiary', 'c')
            ->andWhere('s.holder = :holder')
            ->andWhere('c.active = :active')
            //->andWhere('s.percent >= :percent')
            ->setParameter('active', 1)
            ->setParameter('holder', $company)
            //->setParameter('percent', 50)
            ->orderBy('s.subsidiary', 'ASC');

        if ($company->isInList()) {
            $qb = $qb->andWhere('c.country = :country')
            ->setParameter('country', 'ES')
            ;
        }
        return $qb->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getFullCount()
    {
        // Número de registros para ahorrar memoria
        return $this->createQueryBuilder('c')
            ->select('count(c.id)')
            ->getQuery()->getSingleScalarResult();
    }

    public function getHolderCount()
    {
        // Número de registros para ahorrar memoria
        return $this->createQueryBuilder('c')
            ->select('count(DISTINCT c.holder)')
            ->getQuery()->getSingleScalarResult();
    }

    public function getSubsidiaryCount()
    {
        // Número de registros para ahorrar memoria
        return $this->createQueryBuilder('c')
            ->select('count(DISTINCT c.subsidiary)')
            ->getQuery()->getSingleScalarResult();
    }

    // /**
    //  * @return Shareholder[] Returns an array of Shareholder objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Shareholder
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
