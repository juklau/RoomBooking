<?php

namespace App\Controller;

use App\Repository\ClasseRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\StudentRepository;

use App\Entity\Classe;
use App\Entity\User;
use App\Entity\Student;
use App\Entity\Room;
use App\Entity\Reservation;

use App\Form\AddStudentToClasseType;
use App\Form\CoordinatorReservationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/coordinator')]
#[IsGranted('ROLE_COORDINATOR')]
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
        // le @var dit à PHP/PHPStorm que c'est un User
        /** @var User $user */ //=> sans ça php voit UserInterface et pas entité User!!
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

    /******************************** Classes **************************************/

    /**
     * lister les classes d'un coordinateur
     */
    #[Route('/classes', name: 'app_coordinator_classes')]
    public function classes(): Response
    {
        /** @var \App\Entity\User $user */
        $user        = $this->getUser();
        $coordinator = $user->getCoordinator();
        $myClasses   = $coordinator ? $coordinator->getClasses() : [];

        return $this->render('coordinator/classes/index.html.twig', [
            'myClasses' => $myClasses,
        ]);
    }


     /**
     * détails d'une d'un coordinateur
     */
    #[Route('/classes/{id}', name: 'app_coordinator_classe_show', requirements: ['id' => '\d+'])]
    public function classeShow(Classe $classe): Response
    {
        // Symfony injecte automatiquement l'objet Classe correspondant à l'id passé dans l'URL
        // si l'id n'existe pas => 404 automatique

        /** @var \App\Entity\User $user */              //=> @ est important!!!!!!!
        $user = $this->getUser();
        $coordinator = $user->getCoordinator();

        //vérif si la classe appartient au coordinateur => erreur 403 
        if(!$coordinator || !$coordinator->getClasses()->contains($classe)){
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette classe.');
        }

        return $this->render('coordinator/classes/show.html.twig', [
            'classe' => $classe,
        ]);
    }

    /******************************** Students **************************************/

    /**
     * ajouter un étudiant à une classe
     */
    #[Route('/classes/{id}/students/add', name: 'app_coordinator_student_add', requirements: ['id' => '\d+'])]
    public function studentAdd(
        Classe                 $classe,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        /** @var \App\Entity\User $user */              //=> @ est important!!!!!!!
        $user = $this->getUser();
        $coordinator = $user->getCoordinator();

        //vérif si la classe appartient au coordinateur => erreur 403 
        if(!$coordinator || !$coordinator->getClasses()->contains($classe)){
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette classe.');
        }

        // passer l'id de la classe au formulaire pour filtrer les étudiants qui ne sont pas déjà dans cette classe
        $form = $this->createForm(AddStudentToClasseType::class, null, [
            'classe_id' => $classe->getId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var Student $student */
            $student = $form->get('student')->getData();

            // assigner la nouvelle classe à l'étudiant
            $student->setClasse($classe);
            $em->flush();

            $this->addFlash('success',
                $student->getUser()->getFirstname() . ' ' . $student->getUser()->getLastname()
                . ' ajouté(e) à la classe "' . $classe->getName() . '".'
            );

            return $this->redirectToRoute('app_coordinator_classe_show', ['id' => $classe->getId()]);
        }

        return $this->render('coordinator/students/add.html.twig', [
            'form'   => $form,
            'classe' => $classe,
        ]);
    }

    /**
     * retirer un étudiant d'une classe => MAIS pas supprimer de la bdd
     */
    #[Route('/classes/{classeId}/students/{id}/remove', name: 'app_coordinator_student_remove', requirements: ['classeId' => '\d+', 'id' => '\d+' ], methods: ['POST'])]
    public function studentRemove(
        int                              $classeId,
        \App\Entity\Student              $student,
        Request                          $request,
        EntityManagerInterface           $em,
        \App\Repository\ClasseRepository $classeRepo
    ): Response {

        /** @var \App\Entity\User $user */              //=> @ est important!!!!!!!
        $user = $this->getUser();
        $coordinator = $user->getCoordinator();
        $classe = $classeRepo->find($classeId);

        if(!$classe){
            throw $this->createNotFoundException('Classe introuvable.');
        }

        //vérif si la classe appartient au coordinateur => erreur 403 
        if(!$coordinator || !$coordinator->getClasses()->contains($classe)){
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette classe.');
        }

        //vérif le token CSRF => évite la suppression malveillant
        if(!$this->isCsrfTokenValid('remove_student_' . $student->getId(), $request->request->get('_token'))){
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_coordinator_classe_show', ['id' => $classeId]);
        }


        $name = $student->getUser()->getFirstname() . ' ' . $student->getUser()->getLastname();

        //retirer la classe sans supprimer le compte
        $student->setClasse(null);
        
        $em->flush();

        $this->addFlash('success', 'Étudiant "' . $name . '" a été retiré(e) de la classe ' . $classe->getName() . ' avec succès.');

        return $this->redirectToRoute('app_coordinator_classe_show', ['id' => $classeId]);
    }


      /************************************ Salles ********************************************/


    /**
     * lister les salles
     */
    #[Route('/rooms', name: 'app_coordinator_rooms')]
    public function rooms(Request $request, RoomRepository $roomRepo): Response
    {
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

        //comme CreateReservationType.php =>  // Créneaux 08:00 → 20:00 par 30 min
        $timeSlots = [];
        for ($hour = 8; $hour <= 20; $hour++) {
            foreach ([0, 30] as $min) {

                if ($hour === 20 && $min === 30) break;             // => pas de 20:30

                $label = sprintf('%02d:%02d', $hour, $min);
                $timeSlots[$label] = $label;
            }
        }

        /** @var \App\Entity\User $user */              //=> @ est important!!!!!!!
        $user = $this->getUser();
        $roomsWithStats = $roomRepo->findAllWithStats($user);

        return $this->render('coordinator/rooms/index.html.twig', [
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
    #[Route('/rooms/{id}', name: 'app_coordinator_room_show', requirements: ['id' => '\d+'])]
    public function roomShow(Room $room): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('coordinator/rooms/show.html.twig', [
            'room' => $room,
            'user' => $user,
        ]);
    }

     /************************************ Réservations ********************************************/

     /**
      * créer new reservation pour lui même ou pour ses étudiants
      */
    #[Route('/reservations/new', name: 'app_coordinator_reservation_new')]
    public function reservationNew(
        Request                     $request,
        EntityManagerInterface      $em,
        RoomRepository              $roomRepo
    ):Response {

        /** @var \App\Entity\User  $user*/
        $user = $this->getUser();
        $coordinator = $user->getCoordinator();

        //préselectionner la salle => si passée en GET (?room=4)
        $preselectedRoom = null;
        $roomId = $request->query->get('room');
        if($roomId){
            $preselectedRoom = $roomRepo->find($roomId);
        }

        //créer un formulaire lié à l'entité CoordinatorReservation
        $form = $this->createForm(CoordinatorReservationType::class, null, [
            'preselected_room' => $preselectedRoom,
            'coordinator'      => $coordinator,
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
                return $this->render('coordinator/reservations/new.html.twig', [
                    'form' => $form
                ]);
            }

            // date fin avant ou égal au début
            if($end <= $start){
                $this->addFlash('error', 'La date de fin doit être après la date de début.');
                return $this->render('coordinator/reservations/new.html.twig', [
                    'form' => $form
                ]);
            }

            // salle est déjà réservé pour ce créneau
            if(!$roomRepo->isAvailable($room, $start, $end)){
                $this->addFlash('error', 'La salle "' . $room->getName() . '" n\'est pas disponible sur ce créneau.');
                return $this->render('coordinator/reservations/new.html.twig', [
                    'form' => $form
                ]);
            }

            //déterminer le bénéficiaire=> étudiant choisi ou lui même

            $beneficiary = $data['beneficiary'] ?: $user;

            $reservation = new \App\Entity\Reservation();
            $reservation->setRoom($room);
            $reservation->setUser($beneficiary);
            $reservation->setReservationStart($start);
            $reservation->setReservationEnd($end);

            $em->persist($reservation);
            $em->flush();

            $isSelf = ($beneficiary->getId() === $user->getId());

            $this->addFlash('success', 
                'Réservation créé : ' . $room->getName() . '"le '
                . $start->format('d/m/Y') . ' de '
                . $start->format('H:i') . ' à ' . $end->format('H:i')
                . ($isSelf ? '' : ' pour ' .$beneficiary->getFirstname() . ' ' . $beneficiary->getLastname())
                . '.'
            );

            return $this->redirectToRoute('app_coordinator_rooms');
        }

        return $this->render('coordinator/reservations/new.html.twig', [
            'form' => $form
        ]);
    }

     /**
     * annuler une réservation
     */
    #[Route('/reservations/{id}/cancel', name: 'app_coordinator_reservation_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
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
            return $this->redirectToRoute('app_coordinator_rooms');
        }

         // vérif si la réservation appartient au coordinateur connecté
        if ($reservation->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez annuler que vos propres réservations.');
        }

        // vérif si la réservation n'est pas déjà annulée
        if ($reservation->getStatus() === 'canceled') {
            $this->addFlash('error', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_coordinator_rooms');
        }

        // vérif si la réservation n'a pas encore commencé
        if ($reservation->getReservationStart() <= new \DateTime()) {
            $this->addFlash('error', 'Impossible d\'annuler une réservation déjà commencée ou passée.');
            return $this->redirectToRoute('app_coordinator_rooms');
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
        return $this->redirectToRoute('app_coordinator_dashboard');

    }

}