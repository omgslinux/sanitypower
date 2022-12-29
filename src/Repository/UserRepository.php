<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface as UPHI;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    private $UPHI;

    public function __construct(ManagerRegistry $registry, UPHI $UPHI)
    {
        parent::__construct($registry, User::class);
        $this->UPHI = $UPHI;
    }

    public function add(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function formSubmit($form)
    {
        $user = $form->getData();
        $plainPassword = $user->getPlainPassword();
        if (!empty($plainPassword)) {
            $user->setPassword($this->UPHI->hashPassword($user, $plainPassword));
        }
        $this->add($user, true);
    }

    public function loadUserByIdentifier(string $username): ?UserInterface
    {
        $today = new \DateTime();
        $query = $this->createQueryBuilder('u')
            ->where('u.username = :username AND u.active = :active')
            ->andWhere('u.startDate <= :today AND u.endDate >= :today ')
            ->setParameter('username', $username)
            ->setParameter('active', 1)
            ->setParameter('today', $today)
            ->getQuery();
        $result = $query->getOneOrNullResult();
        //dump($today, $query, $result);die();
        return $result;
    }

    /** @deprecated since Symfony 5.3 */
    public function loadUserByUsername(string $username)
    {
        $result = $this->loadUserByIdentifier($username);
        //dump($result);die();
        return $result;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->add($user, true);
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
