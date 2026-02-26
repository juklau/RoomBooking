<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Reservation;

use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;

use App\Form\StudentReservationType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
 
#[Route('/student')]
// #[IsGranted('ROLE_STUDENT')] 
final class StudentController extends AbstractController
{
    #[Route('/dashboard', name: 'app_student_dashboard')]
    public function dashboard(
        RoomRepository        $roomRepo,
        ReservationRepository $reservationRepo,
    ): Response {
        
        $user    = $this->getUser();

        //pour garantir que c'est une instance of User!! (sinon selon Simfony => retourne UserInterface|null)
        // assert($user instanceof \App\Entity\User);

        /** @var \App\Entity\User $user */  //=> indique au seul IDE/PHPStan, pas de vérification runtime
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


      /************************************ Salles ********************************************/


    /**
     * lister les salles
     */
    #[Route('/rooms', name: 'app_student_rooms')]
    public function rooms(Request $request, RoomRepository $roomRepo): Response
    {
         /** @var \App\Entity\User $user */              //=> @ est important!!!!!!!
        $user = $this->getUser();

        //filtrer par créneau => paramètres GET
        $filterStart = null;
        $filterEnd = null;
        $filteredRooms = null;

        $dateParam = $request->query->get('date');
        $startTimeParam = $request->query->get('startTime');
        $endTimeParam = $request->query->get('endTime');

        if($dateParam && $startTimeParam && $endTimeParam){
            try{

                $filterStart     = new \DateTime($dateParam . ' ' . $startTimeParam);    // "2026-03-10 09:30"
                $filterEnd       = new \DateTime($dateParam . ' ' . $endTimeParam);      // "2026-03-10 11:00"

                if($filterEnd <= $filterStart){
                    $this->addFlash('error', 'L\'heure de fin doit être après l\'heure de début.');
                    $filterStart = $filterEnd = null;
                }else{
                    $filteredRooms = $roomRepo->findAvailableForPeriod($filterStart, $filterEnd);
                }

            }catch(\Exception $e){
                $this->addFlash('error', 'Format de date invalide.');
            }
        }

        //comme AdminReservationType.php =>  // Créneaux 08:00 → 20:00 par 30 min
        $timeSlots = [];
        for ($hour = 8; $hour <= 20; $hour++) {
            foreach ([0, 30] as $min) {

                if ($hour === 20 && $min === 30) break;             // => pas de 20:30

                $label = sprintf('%02d:%02d', $hour, $min);
                $timeSlots[$label] = $label;
            }
        }

        $roomsWithStats = $roomRepo->findAllWithStats($user);

        return $this->render('student/rooms/index.html.twig', [
            'roomsWithStats'  => $roomsWithStats,
            'filteredRooms'   => $filteredRooms,
            'filterStart'     => $filterStart,
            'filterEnd'       => $filterEnd,
            'timeSlots'       => $timeSlots,
            'filterDate'      => $dateParam,
            'filterStartTime' => $startTimeParam,
            'filterEndTime'   => $endTimeParam,
        ]);
    }

    /**
     * voir le détail d'une salle
     */
    #[Route('/rooms/{id}', name: 'app_student_room_show', requirements: ['id' => '\d+'])]
    public function roomShow(Room $room): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('student/rooms/show.html.twig', [
            'room' => $room,
            'user' => $user,
        ]);
    }


     /************************************ Réservations ********************************************/

     /**
      * créer new reservation pour lui même ou pour ses étudiants
      */
    #[Route('/reservations/new', name: 'app_student_reservation_new')]
    public function reservationNew(
        Request                     $request,
        EntityManagerInterface      $em,
        RoomRepository              $roomRepo
    ):Response {

        /** @var \App\Entity\User  $user*/
        $user = $this->getUser();

        //préselectionner la salle => si passée en GET (?room=4)
        $preselectedRoom = null;
        $roomId = $request->query->get('room');
        if($roomId){
            $preselectedRoom = $roomRepo->find($roomId);
        }

        //créer un formulaire lié à l'entité CoordinatorReservation
        $form = $this->createForm(StudentReservationType::class, null, [
            'preselected_room' => $preselectedRoom,
        ]); 

        // analyse la requête HTTP => POST (formulaire)
        $form->handleRequest($request);

        //si le formulaire est soumise et valide
        if($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();
            $room = $data['room'];

            // reconstituer les DateTime depuis date + heure choisie:
                    // $data['date'] = objet DateTimeInterface (DateType renvoie un DateTime) => 2026-03-10
                    // $data['startTime'] = string '09:30' -> $data['endTime']   = string "11:00"
            $dateStr   = $data['date']->format('Y-m-d');
            $start     = new \DateTime($dateStr . ' ' . $data['startTime']);    // "2026-03-10 09:30"
            $end       = new \DateTime($dateStr . ' ' . $data['endTime']);      // "2026-03-10 11:00"
            $now       = new \DateTime();

            $now = new \DateTime();

            // date de début dans le passé
            if($start <= $now){
                $this->addFlash('error', 'La date de début doit être dans le futur.');
                return $this->render('student/reservations/new.html.twig', [
                    'form' => $form
                ]);
            }

            // date fin avant ou égal au début
            if($end <= $start){
                $this->addFlash('error', 'L\'heure de fin doit être après l\'heure de début.');
                return $this->render('student/reservations/new.html.twig', [
                    'form' => $form
                ]);
            }

            // salle est déjà réservé pour ce créneau
            if(!$roomRepo->isAvailable($room, $start, $end)){
                $this->addFlash('error', 'La salle "' . $room->getName() . '" n\'est pas disponible sur ce créneau.');
                return $this->render('student/reservations/new.html.twig', [
                    'form' => $form
                ]);
            }

            $reservation = new \App\Entity\Reservation();
            $reservation->setRoom($room);
            $reservation->setUser($user);
            $reservation->setReservationStart($start);
            $reservation->setReservationEnd($end);

            $em->persist($reservation);
            $em->flush();

            $this->addFlash('success',
                'Réservation créée : "' . $room->getName() . '" le '
                . $start->format('d/m/Y') . ' de '
                . $start->format('H:i') . ' à ' . $end->format('H:i') . '.'
            );

            return $this->redirectToRoute('app_student_rooms');
        }

        return $this->render('student/reservations/new.html.twig', [
            'form' => $form
        ]);
    }


     /**
     * annuler une réservation
     */
    #[Route('/reservations/{id}/cancel', name: 'app_student_reservation_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reservationCancel(
        Reservation            $reservation,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        /** @var \App\Entity\User  $user*/
        $user = $this->getUser();

        // CSRF
        if (!$this->isCsrfTokenValid('cancel_reservation_' . $reservation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_student_dashboard');
        }

         // vérif si la réservation appartient au coordinateur connecté
        if ($reservation->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez annuler que vos propres réservations.');
        }

        // vérif si la réservation n'est pas déjà annulée
        if ($reservation->getStatus() === 'canceled') {
            $this->addFlash('error', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_student_dashboard');
        }

        // vérif si la réservation n'a pas encore commencé
        if ($reservation->getReservationStart() <= new \DateTime()) {
            $this->addFlash('error', 'Impossible d\'annuler une réservation déjà commencée ou passée.');
            return $this->redirectToRoute('app_student_dashboard');
        }

        // marquer comme annulée — ne pas supprimer pour historique => soft delete
        $reservation->setStatus('canceled');
        $em->flush();

        $this->addFlash('success',
            'Réservation de "' . $reservation->getRoom()->getName() . '" du '
            . $reservation->getReservationStart()->format('d/m/Y H:i')
            . ' annulée avec succès.'
        );

        // rediriger vers la page d'où vient la requête si possible !!!!!
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        // ??????????????????
        return $this->redirectToRoute('app_student_dashboard');

    }

}