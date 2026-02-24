<?php

namespace App\Controller;

use App\Repository\ClasseRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\StudentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/coordinator')]
final class CoordinatorController extends AbstractController
{

    #[Route('/dashboard', name: 'app_coordinator_dashboard')]
    public function dashboard(
        RoomRepository        $roomRepo,
        ClasseRepository      $classeRepo,
        StudentRepository     $studentRepo,
        ReservationRepository $reservationRepo,
    ): Response {

        //récup le coordinateur connecté
        $user = $this->getUser();
        $coordinator = $user->getCoordinator();

        //classe de coordinator connecte
        $myClasses = $coordinator ? $coordinator->getClasses() : [];

        //nbre total d'étudiant dans ses classes
        $totalStudents = 0;

        foreach($myClasses as $classe){
            $totalStudents += count($classe->getStudents());
        }

        //statistiques 
        $stats = [
            'my_classes'       => count($myClasses),
            'my_students'      => $totalStudents,
            'rooms'            => $roomRepo->count([]),
            'reservations'     => $reservationRepo->countByUser($user),
        ];

        //prochains réservations du coordinateur concerné
        $myReservations = $reservationRepo->findUpcomingByUser($user, 5);

        //toutes les salles
        $rooms = $roomRepo->findAll();

        return $this->render('coordinator/dashboard.html.twig', [
            'stats'          => $stats,
            'myClasses'      => $myClasses,
            'myReservations' => $myReservations,
            'rooms'          => $rooms,
        ]);

    }
}