<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * @return Company[] Returns an array of Company objects
     */

    public function findByGroup($value)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('ownedSubsidiaries s')
            ->andWhere('c.active = :active')
            ->andWhere('s.percent >= :percent')
            ->setParameter('active', 1)
            ->setParameter('percent', 50)
            ->orderBy('s.', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Our new getAllPosts() method
     *
     * 1. Create & pass query to paginate method
     * 2. Paginate will return a `\Doctrine\ORM\Tools\Pagination\Paginator` object
     * 3. Return that object to the controller
     *
     * @param integer $currentPage The current page (passed from controller)
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getAllPaginated($currentPage = 1, $limit = 5)
    {
        $this->limit = $limit;
        // Create our query
        $query = $this->createQueryBuilder('c')
            ->orderBy('c.fullname', 'ASC')
            ->getQuery();

        // No need to manually get get the result ($query->getResult())

        return $this->paginate($query, $currentPage);
    }

    /*
    public function findOneBySomeField($value): ?Company
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * Paginator Helper
     *
     * Pass through a query object, current page & limit
     * the offset is calculated from the page and limit
     * returns an `Paginator` instance, which you can call the following on:
     *
     *     $paginator->getIterator()->count() # Total fetched (ie: `5` posts)
     *     $paginator->count() # Count of ALL posts (ie: `20` posts)
     *     $paginator->getIterator() # ArrayIterator
     *
     * @param Doctrine\ORM\Query $dql   DQL Query Object
     * @param integer            $page  Current page (defaults to 1)
     * @param integer            $limit The total number per page (defaults to 5)
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function paginate($dql, $page)
    {
        $limit = $this->limit;
        $paginator = new Paginator($dql);

        $query = $paginator->getQuery();
        $query->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limit

        return $paginator;
    }
}
