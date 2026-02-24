<?php

namespace App\Controller;

use App\Repository\ClasseRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\StudentRepository;
use App\Repository\CoordinatorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        RoomRepository        $roomRepo,
        ClasseRepository      $classeRepo,
        StudentRepository     $studentRepo,
        CoordinatorRepository $coordinatorRepo,
        ReservationRepository $reservationRepo
    ): Response {

        //statistiques globales
        $stats = [
            'rooms'         => $roomRepo->count([]),
            'classes'       => $classeRepo->count([]),
            'students'      => $studentRepo->count([]),
            'coordinators'  => $coordinatorRepo->count([]),
            'reservations'  => $reservationRepo->count([]),
        ];

        //prochaines réservations => max 5
        $upcomingReservations = $reservationRepo->findUpcoming(5);

        //toutes les salles avec équipements
        $rooms = $roomRepo->findAll();

        //toutes les classe
        $classes = $classeRepo->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'stats'                 => $stats,
            'upcomingReservations'  => $upcomingReservations,
            'rooms'                 => $rooms,
            'classes'               => $classes,
        ]);
    }
}