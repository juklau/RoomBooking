<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/student')]
final class StudentController extends AbstractController
{
    #[Route('/dashboard', name: 'app_student_dashboard')]
    public function dashboard(
        RoomRepository        $roomRepo,
        ReservationRepository $reservationRepo,
    ): Response {
        
        $user    = $this->getUser();

        //pour garantir que c'est une instance of User!! (sinon selon Simfony => retourne UserInterface|null)
        assert($user instanceof \App\Entity\User);
        $student = $user->getStudent();

        // Classe de l'étudiant
        $classe = $student?->getClasse();

        $stats = [
            'rooms'            => $roomRepo->count([]),
            'reservations'     => $reservationRepo->countByUser($user),
            'classmates'       => $classe ? count($classe->getStudents()) - 1 :0,
        ];

        //prochains réservations de l'étudiant concerné
        $myReservations = $reservationRepo->findUpcomingByUser($user, 5);

        //toutes les salles
        $rooms = $roomRepo->findAll();

        return $this->render('student/dashboard.html.twig', [
            'stats'          => $stats,
            'rooms'          => $rooms,
            'myReservations' => $myReservations,
            'classe'         => $classe,
        ]);
    }
}