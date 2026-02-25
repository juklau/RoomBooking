<?php

namespace App\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }


    public function findAllWithStats(): array
    {
        $now = new \DateTime();

        $rooms = $this->createQueryBuilder('r')
            ->leftJoin('r.reservations', 'res')
            ->addSelect('res')
            ->leftJoin('res.user', 'u')
            ->addSelect('u')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];

        foreach ($rooms as $room){
            $reservations = $room->getReservations();

            //nbre total de réservation => exlure les annulés
            $totalReservations = 0;
            foreach($reservations as $res){
                if($res->getStatus() === 'canceled') continue; 
                $totalReservations++;
            }

            //réservation en cours maintenant
            $currentReservation = null;
            foreach($reservations as $res){
                if($res->getStatus() === 'canceled') continue; // => passer à l'itération suivante
                if($res->getReservationStart() <= $now && $res->getReservationEnd() >= $now){
                    $currentReservation = $res;
                    break;
                }
            }

            //prochaine réservation à venir
            $nextReservation = null;
            $minDiff = null;

            foreach ($reservations as $res){
                if($res->getReservationStart() > $now){
                    if($res->getStatus() === 'canceled') continue;
                    
                    $diff = $res->getReservationStart()->getTimestamp() - $now->getTimestamp();

                    if($minDiff === null || $diff < $minDiff){
                        $minDiff = $diff;
                        $nextReservation = $res;
                    }
                }
            }

            //Statut
            $status = $currentReservation ? 'occupied' : 'available';

            $result [] = [
                'room'               => $room,
                'status'             => $status,
                'currentReservation' => $currentReservation,
                'nextReservation'    => $nextReservation,
                'totalReservations'  => $totalReservations
            ];
        }
        return $result;
    }

    /**
     * vérif si salle est disponible sur un creneau donné
     * count = 0
     */
    public function isAvailable(Room $room, \DateTime $start, \DateTime $end): bool
    {

        $count = $this->createQueryBuilder('r')
            ->select('COUNT(res.id)')
            ->join('r.reservations', 'res')
            ->where('r.id = :roomId')
            ->andWhere('res.reservationStart < :end')
            ->andWhere('res.reservationEnd > :start')
            ->andWhere('res.status != :canceled')
            ->setParameter('roomId', $room->getId())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('canceled', 'canceled')
            ->getQuery()
            ->getSingleScalarResult();

        return $count === 0;
    }


    /**
     * retourneer les salles disponibles
     */
    public function findAvailableForPeriod(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('r')
            ->where('NOT EXISTS (
                SELECT 1 FROM App\Entity\Reservation res
                WHERE res.room = r
                AND res.reservationStart < :end
                AND res.reservationEnd > :start
                AND res.status != :canceled
            )')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('canceled', 'canceled')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }



    



    //    /**
    //     * @return Room[] Returns an array of Room objects
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

    //    public function findOneBySomeField($value): ?Room
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
