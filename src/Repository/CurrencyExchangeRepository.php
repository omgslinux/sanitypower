<?php

namespace App\Repository;

use App\Entity\CompanyIncoming as CI;
use App\Entity\CurrencyExchange as Entity;
use App\Entity\Currency as CU;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CurrencyExchange|null find($id, $lockMode = null, $lockVersion = null)
 * @method CurrencyExchange|null findOneBy(array $criteria, array $orderBy = null)
 * @method CurrencyExchange[]    findAll()
 * @method CurrencyExchange[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyExchangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entity::class);
    }

    public function add(Entity $entity, bool $flush = false): void
    {
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

    // /**
    //  * @return CurrencyExchange[] Returns an array of CurrencyExchange objects
    //  */

    public function getExchange(CI $incoming)
    {
        /* SELECT ce.amount,i.amount,(i.amount * ce.amount) AS result FROM sanitypower.currency_exchange ce
            JOIN sanitypower.incomings i USING(currency_id)-- ON ce.currency_id = i.currency_id
        WHERE ce.year <= i.year
        AND i.id=5
        LIMIT 1; */
        /*
            $query = $em->createQuery("SELECT u FROM CmsUser u LEFT JOIN u.articles a WITH a.topic LIKE :foo");
        */
        $query = $this->getEntityManager()->createQuery(
            'SELECT ce.amount FROM ' . Entity::class . ' ce
            JOIN ' . CI::class . ' i
            WHERE ce.currency = i.currency
            AND ce.year <= i.year
            AND i = :incoming
            ORDER BY ce.year DESC
            '
        );
        return $query
            ->setParameter('incoming', $incoming)
            ->setMaxResults(1)
            ->getResult();

        return $this->createQueryBuilder('ce')
            ->select('ce.amount')
            ->join('ce.currency', 'c')
            ->join('ce.currency', 'i')
            ->andWhere('ce.year <= i.year')
            ->andWhere('i = :incoming')
            ->setParameter('incoming', $incoming)
            ->orderBy('ce.year', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?CurrencyExchange
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
