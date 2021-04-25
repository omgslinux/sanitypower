<?php

namespace App\Repository;

use App\Entity\Incoming;
use App\Entity\CurrencyExchange;
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
        parent::__construct($registry, CurrencyExchange::class);
    }

    // /**
    //  * @return CurrencyExchange[] Returns an array of CurrencyExchange objects
    //  */

    public function getExchange(Incoming $incoming)
    {
        /* SELECT ce.amount,i.amount,(i.amount * ce.amount) AS result FROM sanitypower.currency_exchange ce
            JOIN sanitypower.incomings i USING(currency_id)-- ON ce.currency_id = i.currency_id
        WHERE ce.year <= i.year
        AND i.id=5
        LIMIT 1; */
        /*
            $query = $em->createQuery("SELECT u FROM CmsUser u LEFT JOIN u.articles a WITH a.topic LIKE :foo");
            $query = $em->createQuery('SELECT u, a, p, c FROM CmsUser u JOIN u.articles a JOIN u.phonenumbers p JOIN a.comments c');
        */
        $query = $this->getEntityManager()->createQuery(
            'SELECT ce.amount FROM App:CurrencyExchange ce
            JOIN App:Incoming i
            WHERE ce.currency = i.currency
            AND ce.year <= i.year
            AND i = :incoming
            ORDER BY ce.year ASC'
        );
        return $query
            ->setParameter('incoming', $incoming)
            ->setMaxResults(1)
            ->getResult();

        return $this->createQueryBuilder('ce')
            ->join('ce.currency', 'c')
            ->join('ce.currency', 'i')
            ->andWhere('ce.year <= i.year')
            ->andWhere('i = :incoming')
            ->setParameter('incoming', $incoming)
            ->orderBy('c.year', 'ASC')
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
