<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * retourne les prochaines réservations à venir
     */
    public function findUpcoming(int $limit = 5): array
    {
        // SELECT * FROM reservation r
        return $this->createQueryBuilder('r')

            // LEFT JOIN room ON r.room_id = room.id
            ->leftJoin('r.room', 'room')

            // LEFT JOIN user ON r.user_id = user.id
            ->leftJoin('r.user', 'user')

            //charger les données de room et user en même temps
            ->addSelect('room', 'user')

            ->where('r.reservationStart >= :now')

            //now est remplacé par date/heure actuelle => sécurisation contre injection SQL
            ->setParameter('now', new \DateTime())

            //plus proches en premier
            ->orderBy('r.reservationStart', 'ASC')

            ->setMaxResults($limit)

            //exécution de la requête
            ->getQuery()

            //retourner un tableau d'objets Reservation
            ->getResult();
    }

    /**
     * retourne les prochaines réservations d'un user spécifique
     */
    public function findUpcomingByUser(User $user, int $limit = 5): array
    {
        // SELECT * FROM reservation r
        return $this->createQueryBuilder('r')

            // LEFT JOIN room ON r.room_id = room.id
            ->leftJoin('r.room', 'room')

            //charger les données de room 
            ->addSelect('room')

            ->where('r.reservationStart >= :now')
            ->andWhere('r.user = :user')

            //now est remplacé par date/heure actuelle => sécurisation contre injection SQL
            ->setParameter('now', new \DateTime())
            ->setParameter('user', $user)

            //plus proches en premier
            ->orderBy('r.reservationStart', 'ASC')

            ->setMaxResults($limit)

            //exécution de la requête
            ->getQuery()

            //retourner un tableau d'objets Reservation
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('r')

            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }















    //    /**
    //     * @return Reservation[] Returns an array of Reservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reservation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
