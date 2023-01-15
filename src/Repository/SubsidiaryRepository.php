<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Subsidiary as Entity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Subsidiary|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subsidiary|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subsidiary[]    findAll()
 * @method Subsidiary[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubsidiaryRepository extends ServiceEntityRepository
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
    //  * @return Subsidiary[] Returns an array of Subsidiary objects
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
    public function getFullCount()
    {
        // Número de registros para ahorrar memoria
        return $this->createQueryBuilder('c')
            ->select('count(c.id)')
            ->getQuery()->getSingleScalarResult();
    }

    public function findOwnedSubsidiaries(Company $owner)
    {
        $owned = $this->createQueryBuilder('s')
        ->leftJoin('s.owned', 'c')
        ->andWhere('s.owner = :company')
        //->andWhere('c.country = :country')
        ->andWhere('c.active = :active')
        //->andWhere('s.percent >= :percent')
        ->setParameter('active', 1)
        ->setParameter('company', $owner)
        //->setParameter('country', 'ES')
        //->setParameter('percent', 50)
        ->orderBy('s.owned', 'ASC')
        //->setMaxResults(10)
        ->getQuery()
        ->getResult()
        ;

        return $owned;
    }

    public function findByCompanyGroup(Company $company)
    {
        // Recuperamos primero la matriz
        $owner = $this->findSubsidiaryOwner($company);

        if (null!==$owner) {
            $groupmembers = $this->createQueryBuilder('s')
            ->leftJoin('s.owned', 'c')
            ->andWhere('s.owner = :company')
            //->andWhere('c.country = :country')
            ->andWhere('c.active = :active')
            ->andWhere('s.percent >= :percent')
            ->setParameter('active', 1)
            ->setParameter('company', $owner->getOwner())
            //->setParameter('country', 'ES')
            ->setParameter('percent', 50)
            ->orderBy('s.owned', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
            $group = [
                'owner' => $owner,
                'owned' => [],
            ];
            foreach ($groupmembers as $member) {
                $group['owned'][] = $member;
            }
        } else {
            $group = [];
        }

        return $group;
    }

    public function findByCompanyOwner(Company $company)
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.owned', 'c')
            ->andWhere('s.owner = :company')
        //->andWhere('c.active = :active')
            //->andWhere('s.percent >= :percent')
            //->setParameter('active', 1)
            ->setParameter('company', $company)
            //->setParameter('percent', 50)
            ->orderBy('s.owned', 'ASC');

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

    public function findSubsidiaryOwner(Company $company)
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.owner', 'c')
            ->andWhere('s.owned = :company')
            ->andWhere('c.active = :active')
            //->andWhere('s.percent >= :percent')
            ->setParameter('active', 1)
            ->setParameter('company', $company)
            //->setParameter('country', 'ES')
            //->setParameter('percent', 50)
            //->orderBy('s.owned', 'ASC')
            //->setMaxResults(10)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?Subsidiary
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
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
