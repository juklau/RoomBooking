<?php

namespace App\Controller;

use App\Repository\ClasseRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\StudentRepository;
use App\Repository\CoordinatorRepository;
use App\Repository\UserRepository;

use App\Entity\Room;
use App\Entity\Classe;
use App\Entity\Student;
use App\Entity\User;
use App\Entity\Coordinator;
use App\Entity\Reservation;
use App\Form\ClasseType;
use App\Form\CreateStudentType;
use App\Form\CreateCoordinatorType;
use App\Form\RoomType;
use App\Form\AddStudentToClasseType;
use App\Form\AddCoordinatorToClasseType;
use App\Form\CreateReservationType;
use App\Form\ResetPasswordType;
use App\Form\RoomEquipmentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

    /************************************ Salles ********************************************/

    /**
     * lister les salles
     */
    #[Route('/rooms', name: 'app_admin_rooms')]
    // public function rooms(RoomRepository $roomRepo): Response =>ancien version
    // {
    //     //récup toutes les salles avec leur équipements
    //     $rooms = $roomRepo->findAll();

    //     return $this->render('admin/rooms/index_sans_filtre.html.twig', [ 
    //         'rooms' => $rooms,
    //     ]);
    // }

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

        //comme CreateReservationType.php
        $timeSlots = [];
        for ($hour = 8; $hour <= 20; $hour++) {
            foreach ([0, 30] as $min) {

                if ($hour === 20 && $min === 30) break;             // => pas de 20:30

                $label = sprintf('%02d:%02d', $hour, $min);
                $timeSlots[$label] = $label;
            }
        }

        $roomsWithStats = $roomRepo->findAllWithStats();

        return $this->render('admin/rooms/index.html.twig', [
            'roomsWithStats' => $roomsWithStats,
            'filteredRooms'  => $filteredRooms,
            'filterStart'    => $filterStart,
            'filterEnd'      => $filterEnd,
            'timeSlots'       => $timeSlots,
            'filterDate'      => $dateParam,
            'filterStartTime' => $startTimeParam,
            'filterEndTime'   => $endTimeParam,
        ]);
    }

    /**
     * créer un room
    */
    #[Route('/rooms/new', name: 'app_admin_room_new')]
    public function newRoom(Request $request, EntityManagerInterface $em):Response 
    {
        $room = new Room();

        //créer un formulaire lié à l'entité Room
        $form = $this->createForm(RoomType::class, $room);  //=> formulaire vide (null, null) => pas de $room

        // analyse la requête HTTP => POST (formulaire)
        $form->handleRequest($request);

        //si le formulaire est soumise et valide
        if($form->isSubmitted() && $form->isValid()) {

            //persister l'entité dans la BDD
            $em->persist($room);
            $em->flush();

            //message flush de confirmation
            $this->addFlash('success', 'Salle "' . $room->getName() . '" créée avec succès.');
            
            //redirection vers la détails de la salle créée
            return $this->redirectToRoute('app_admin_room_show', ['id' => $room->getId()]);
        }

        return $this->render('admin/rooms/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * voir les détails d'une salle
     */
    #[Route('/rooms/{id}', name: 'app_admin_room_show', requirements: ['id' => '\d+'])]
    public function roomShow(Room $room): Response
    {
        // Symfony injecte automatiquement l'objet Room correspondant à l'id passé dans l'URL
        // si l'id n'existe pas => 404 automatique

        return $this->render('admin/rooms/show.html.twig', [
            'room' => $room,
        ]);
    }

    /**
     * Modifier les données d'une salle
    */
    #[Route('/rooms/{id}/edit', name: 'app_admin_room_edit', requirements: ['id' => '\d+'])]
    public function roomEdit(
        Room                   $room,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        // réutilisation de Roomtype, MAIS avec les données préremplis

        //créer un formulaire lié à l'entité Room
        $form = $this->createForm(RoomType::class, $room);  //=> formulaire pré-rempli par Symfony

        $form->handleRequest($request);

         //si le formulaire est soumise et valide
        if($form->isSubmitted() && $form->isValid()) {

            // $em->persist($room); => il faut pas car $room est déjà dans la BDD
            $em->flush();

            //message flush de confirmation
            $this->addFlash('success', 'Salle "' . $room->getName() . '" modifié avec succès.');
            
            //redirection vers la détails de la salle créée
            return $this->redirectToRoute('app_admin_room_show', ['id' => $room->getId()]);
        }

        return $this->render('admin/rooms/edit.html.twig', [
            'form' => $form,
            'room' => $room,
        ]);
    }

    /**
     * supprimer une salle
     */
    #[Route('/rooms/{id}/delete', name: 'app_admin_room_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function roomDelete(
        Room                   $room,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        //vérif le token CSRF => évite la suppression malveillant
        if(!$this->isCsrfTokenValid('delete_room_' . $room->getId(), $request->request->get('_token'))){
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_rooms');
        }

        //vérif si la salle a des réservation à venir
        $hasUpcoming = false;
        foreach($room->getReservations() as $res){
            if($res->getReservationStart() > new \DateTime()){
                $hasUpcoming = true;
                break;
            }
        }

        if($hasUpcoming){
            $this->addFlash('error', 'Impossible de supprimer "' . $room->getName() . '" : elle a des réservations à venir.');
            return $this->redirectToRoute('app_admin_room_show', ['id' => $room->getId()]);
        }

        $name = $room->getName();
        $em->remove($room);
        $em->flush();

        $this->addFlash('success', 'Salle"' . $name . '" supprimée avec succès.');
        return $this->redirectToRoute('app_admin_rooms');

    }

     /************************************ Classes ********************************************/

     /**
     * lister les classes
     */
    #[Route('/classes', name: 'app_admin_classes')]
    public function classes(ClasseRepository $classeRepo): Response
    {
        //récup toutes les classes
        $classes = $classeRepo->findAll();

        return $this->render('admin/classes/index.html.twig', [
            'classes' => $classes,
        ]);
    }

    /**
     * créer une classe
    */
    #[Route('/classes/new', name: 'app_admin_classe_new')]
    public function newClasse(Request $request,EntityManagerInterface $em):Response
    {
        $classe = new Classe();

        //créer un formulaire lié à l'entité Classe
        $form = $this->createForm(ClasseType::class, $classe);  //=> formulaire vide (null, null) => pas de $classe

        // analyse la requête HTTP => POST (formulaire)
        $form->handleRequest($request);

        //si le formulaire est soumise et valide
        if($form->isSubmitted() && $form->isValid()) {

            //persister l'entité dans la BDD
            $em->persist($classe);
            $em->flush();

            //message flush de confirmation
            $this->addFlash('success', 'Classe "' . $classe->getName() . '" créée avec succès.');
            
            //redirection vers la détails de la classe créée
            return $this->redirectToRoute('app_admin_classe_show', ['id' => $classe->getId()]);
        }

        return $this->render('admin/classes/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * voir les détails d'une classe
     */
    #[Route('/classes/{id}', name: 'app_admin_classe_show', requirements: ['id' => '\d+'])]
    public function classeShow(Classe $classe): Response
    {
        // Symfony injecte automatiquement l'objet Classe correspondant à l'id passé dans l'URL
        // si l'id n'existe pas => 404 automatique

        return $this->render('admin/classes/show.html.twig', [
            'classe' => $classe,
        ]);
    }

    /**
     * Modifier le nom de la classe
     */
    #[Route('/classes/{id}/edit', name: 'app_admin_classe_edit', requirements: ['id' => '\d+'])]
    public function classEdit(
        Classe                 $classe,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        // u.a. ClasseType que la création => formulaire pré-rempli automatiquement
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // pas de persist()!! — Doctrine surveille déjà l'entité
            $em->flush();
            $this->addFlash('success', 'Classe "' . $classe->getName() . '" modifiée avec succès.');
            return $this->redirectToRoute('app_admin_classe_show', ['id' => $classe->getId()]);
        }

        return $this->render('admin/classes/edit.html.twig', [
            'form'   => $form,
            'classe' => $classe,
        ]);
    }


    /**
     * supprimer une classe
     */
    #[Route('/classes/{id}/delete', name: 'app_admin_classe_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function classeDelete(
        Classe                 $classe,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        //vérif le token CSRF => évite la suppression malveillant
        if(!$this->isCsrfTokenValid('delete_classe_' . $classe->getId(), $request->request->get('_token'))){
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_classes');
        }

        // règle métier => interdire la suppression si la classe a des étudiants
        if (!$classe->getStudents()->isEmpty()) {
            $this->addFlash('error', 'Impossible de supprimer "' . $classe->getName() . '" : elle contient encore des étudiants.');
            return $this->redirectToRoute('app_admin_classe_show', ['id' => $classe->getId()]);
        }

        $name = $classe->getName();
        $em->remove($classe);
        $em->flush();

        $this->addFlash('success', 'Classe"' . $name . '" supprimée avec succès.');
        return $this->redirectToRoute('app_admin_classes');

    }

    /**
     * ajouter une classe au étudiant 
     */
    #[Route('/classes/{id}/add-student', name: 'app_admin_classe_add_student', requirements: ['id' => '\d+'])]
    public function classAddStudent(
        Classe                 $classe,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

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

            return $this->redirectToRoute('app_admin_classe_show', ['id' => $classe->getId()]);
        }

        return $this->render('admin/classes/add_student.html.twig', [
            'form'   => $form,
            'classe' => $classe,
        ]);
    }


     /**
     * ajouter une classe au coordinateur 
     */
    #[Route('/classes/{id}/add-coordinator', name: 'app_admin_classe_add_coordinator', requirements: ['id' => '\d+'])]
    public function classAddCoordinator(
        Classe                 $classe,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        // passer l'id de la classe au formulaire pour filtrer les étudiants qui ne sont pas déjà dans cette classe
        $form = $this->createForm(AddCoordinatorToClasseType::class, null, [
            'classe_id' => $classe->getId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var Coordinator $coordinator */
            $coordinator = $form->get('coordinator')->getData();

            //ajoute la classe au coordinateur => ManyToMany
            $coordinator->addClasse($classe);
            $em->flush();

            $this->addFlash('success',
                $coordinator->getUser()->getFirstname() . ' ' . $coordinator->getUser()->getLastname()
                . ' ajouté(e) à la classe "' . $classe->getName() . '".'
            );

            return $this->redirectToRoute('app_admin_classe_show', ['id' => $classe->getId()]);
        }

        return $this->render('admin/classes/add_coordinator.html.twig', [
            'form'   => $form,
            'classe' => $classe,
        ]);
    }

    
     /************************************ Students ********************************************/

    /**
     * créer nouvel étudiant
     */
    #[Route('/students/new', name: 'app_admin_student_new')]
    public function studentNew(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher,
        UserRepository              $userRepo
    ): Response {

        // u.a. ClasseType que la création => formulaire pré-rempli automatiquement
        $form = $this->createForm(CreateStudentType::class);
        $form->handleRequest($request);

        if( $form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            //vérifier que l'email n'existe pas déjà
            if($userRepo->findOneBy(['email' => $data['email']])){ 
                $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                return $this->render('admin/students/new.html.twig', ['form' => $form]);
            }

            //Création User
            $user = new User();
            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->setEmail($data['email']);
            $user->setPassword($hasher->hashPassword($user, $data['password']));

            //Créer le Student lié au User
            $student = new Student();
            $student->setUser($user);
            $user->setStudent($student);

            //Assigner la classe
            $student->setClasse($data['classe']);
            
            $em->persist($user);
            $em->persist($student);
            $em->flush();

            $this->addFlash('success', 'Étudiant ' . $user->getFirstname() . ' ' . $user->getLastname() . ' créé avec succès.');

            // Redirige vers la classe 
            return $this->redirectToRoute('app_admin_classe_show', ['id' => $data['classe']->getId()]);
        }

        return $this->render('admin/students/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * réinitialiser le MDP d'un étudiant
     */
    #[Route('/students/{id}/reset-password', name: 'app_admin_student_reset_password', requirements: ['id' => '\d+'])]
    public function studentResetPassword(
        Student                     $student,
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher
    ): Response {

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $newPassword = $form->get('password')->getData();

            //hash et sauvegarde le nouveau MDP sur le User lié
            $user = $student->getUser();
            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Mot de passe de ' . $user->getFirstname() . ' ' . $user->getLastname() . ' réinitialisé avec succès.');

            //rediriger vers la classe de l'étudiant => s'il existe
            if($student->getClasse()){
                return $this->redirectToRoute('app_admin_classe_show', ['id' => $student->getClasse()->getId()]);
            }

            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/students/reset_password.html.twig', [
            'form'    => $form,
            'student' => $student,
        ]);
    }

    /**
     * supprimer un étudiant
     */
    #[Route('/students/{id}/delete', name: 'app_admin_student_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function studentDelete(
        Student                $student,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        //vérif le token CSRF => évite la suppression malveillant
        if(!$this->isCsrfTokenValid('delete_student_' . $student->getId(), $request->request->get('_token'))){
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        //garder id de la classe pour la redirection AVANT suppression
        $classeId = $student->getClasse()?->getId();

        $name = $student->getUser()->getFirstname() . ' ' . $student->getUser()->getLastname();

        //supprimer User => supprime également Student en cascade
        $user = $student->getUser();

        //il faut vérifier si l'étudiant a des réservations
        if(!$user->getReservations()->isEmpty()){
            $this->addFlash('error', 'Impossible de supprimer "' . $name . '" : il a des réservations enregistrées.');

            if($classeId){
                return $this->redirectToRoute('app_admin_classe_show', ['id' => $classeId]);
            }
            return $this->redirectToRoute('app_admin_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Étudiant "' . $name . '" supprimé avec succès.');

        // redirige vers la classe si elle existait
        if ($classeId) {
            return $this->redirectToRoute('app_admin_classe_show', ['id' => $classeId]);
        }

        return $this->redirectToRoute('app_admin_users');
    }

     /********************************************* Users ****************************************************/

     /**
      * lister tous les users
      */
    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepo): Response
    {
        $users = $userRepo->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }



    /************************************ Coordinators ********************************************/

     /**
     * créer nouveau coordinateur (prof)
     */
    #[Route('/coordinators/new', name: 'app_admin_coordinator_new')]
    public function coordinatorNew(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher,
        UserRepository              $userRepo
    ): Response {

        // u.a. ClasseType que la création => formulaire pré-rempli automatiquement
        $form = $this->createForm(CreateCoordinatorType::class);
        $form->handleRequest($request);

        if( $form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            //vérifier que l'email n'existe pas déjà
            if($userRepo->findOneBy(['email' => $data['email']])){ 
                $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                return $this->render('admin/coordinators/new.html.twig', ['form' => $form]);
            }

            //Création User
            $user = new User();
            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->setEmail($data['email']);
            $user->setPassword($hasher->hashPassword($user, $data['password']));

            //Créer le Coordinator lié au User
            $coordinator = new Coordinator();
            $coordinator->setUser($user);
            $user->setCoordinator($coordinator);

            //Assigner la classe =>ManyToMany
            foreach($data['classes'] as $classe){
                $coordinator->addClasse($classe);
            }
            
            $em->persist($user);
            $em->persist($coordinator);
            $em->flush();

            $this->addFlash('success', 'Coordinateur ' . $user->getFirstname() . ' ' . $user->getLastname() . ' créé avec succès.');

            // Redirige vers dashboard d'admin
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/coordinators/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * réinitialiser le MDP d'un coordinateur
     */
    #[Route('/coordinators/{id}/reset-password', name: 'app_admin_coordinator_reset_password', requirements: ['id' => '\d+'])]
    public function coordinatorResetPassword(
        Coordinator                 $coordinator,
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher
    ): Response {

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $newPassword = $form->get('password')->getData();

            //hash et sauvegarde le nouveau MDP sur le User lié
            $user = $coordinator->getUser();
            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Mot de passe de ' . $user->getFirstname() . ' ' . $user->getLastname() . ' réinitialisé avec succès.');

            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/coordinators/reset_password.html.twig', [
            'form'        => $form,
            'coordinator' => $coordinator,
        ]);
    }

    /**
     * supprimer un coordinateur
     */
    #[Route('/coordinators/{id}/delete', name: 'app_admin_coordinator_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function coordinatorDelete(
        Coordinator            $coordinator,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        //vérif le token CSRF => évite la suppression malveillant
        if(!$this->isCsrfTokenValid('delete_coordinator_' . $coordinator->getId(), $request->request->get('_token'))){
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        $name = $coordinator->getUser()->getFirstname() . ' ' . $coordinator->getUser()->getLastname();

        //supprimer User => supprime également Coordinator en cascade
        $user = $coordinator->getUser();

        //il faut vérifier si le coordinateur a des réservations
        if(!$user->getReservations()->isEmpty()){
            $this->addFlash('error', 'Impossible de supprimer "' . $name . '" : il a des réservations enregistrées.');

            return $this->redirectToRoute('app_admin_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Coordinateur "' . $name . '" supprimé avec succès.');

        return $this->redirectToRoute('app_admin_users');
    }


     /************************************ Réservations ********************************************/

     /**
      * créer new reservation
      */
    #[Route('/reservations/new', name: 'app_admin_reservation_new')]
    public function reservationNew(
        Request                     $request,
        EntityManagerInterface      $em,
        RoomRepository              $roomRepo
    ):Response {

        //préselectionner la salle => si passée en GET (?room=4)
        $preselectedRoom = null;
        $roomId = $request->query->get('room');
        if($roomId){
            $preselectedRoom = $roomRepo->find($roomId);
        }

        //créer un formulaire lié à l'entité CreateReservation
        $form = $this->createForm(CreateReservationType::class, null, [
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
                return $this->render('admin/reservations/new.html.twig', [
                    'form' => $form
                ]);
            }

            // date fin avant ou égal au début
            if($end <= $start){
                $this->addFlash('error', 'La date de fin doit être après la date de début.');
                return $this->render('admin/reservations/new.html.twig', [
                    'form' => $form
                ]);
            }

            // salle est déjà réservé pour ce créneau
            if(!$roomRepo->isAvailable($room, $start, $end)){
                $this->addFlash('error', 'La salle "' . $room->getName() . '" n\'est pas disponible sur ce créneau.');
                return $this->render('admin/reservations/new.html.twig', [
                    'form' => $form
                ]);
            }

            //déterminer le bénéficiaire=> si rempli ? user choisi : admin (lui même)

            /** @var User $currentAdmin */
            $currentAdmin = $this->getUser();

            $beneficiary = $data['beneficiary'] ?? $currentAdmin;
            if(!$beneficiary){
                $beneficiary = $currentAdmin;
            }

            $reservation = new Reservation();
            $reservation->setRoom($room);
            $reservation->setUser($beneficiary);
            $reservation->setReservationStart($start);
            $reservation->setReservationEnd($end);

            $em->persist($reservation);
            $em->flush();

            $isSelf = ($beneficiary->getId() === $currentAdmin->getId());

            $this->addFlash('success', 
                'Réservation créé : ' . $room->getName() . '"le '
                . $start->format('d/m/Y') . ' de '
                . $start->format('H:i') . ' à ' . $end->format('H:i')
                . ($isSelf ? '' : ' pour ' .$beneficiary->getFirstname() . ' ' . $beneficiary->getLastname())
                . '.'
            );

            return $this->redirectToRoute('app_admin_rooms');
        }

        return $this->render('admin/reservations/new.html.twig', [
            'form' => $form
        ]);
    }

    /**
     * annuler une réservation
     */
    #[Route('/reservations/{id}/cancel', name: 'app_admin_reservation_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reservationCancel(
        Reservation            $reservation,
        Request                $request,
        EntityManagerInterface $em
    ): Response {

        if (!$this->isCsrfTokenValid('cancel_reservation_' . $reservation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_rooms');
        }

        // vérif si la réservation n'est pas déjà annulée
        if ($reservation->getStatus() === 'canceled') {
            $this->addFlash('error', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_admin_rooms');
        }

        // vérif si la réservation n'a pas encore commencé
        if ($reservation->getReservationStart() <= new \DateTime()) {
            $this->addFlash('error', 'Impossible d\'annuler une réservation déjà commencée ou passée.');
            return $this->redirectToRoute('app_admin_rooms');
        }

        // marquer comme annulée — ne pas supprimer pour historique
        $reservation->setStatus('canceled');
        $em->flush();

        $this->addFlash('success',
            'Réservation de "' . $reservation->getRoom()->getName() . '" du '
            . $reservation->getReservationStart()->format('d/m/Y H:i')
            . ' annulée avec succès.'
        );

        return $this->redirectToRoute('app_admin_rooms');
    }

    /************************************ équipements ********************************************/

    /**
     * ajouter l'équipement à la salle
     */
    #[Route('/rooms/{id}/equipments', name: 'app_admin_room_equipments', requirements: ['id' => '\d+'])]
    public function roomEquipement(
        Room                   $room,
        Request                $request,
        EntityManagerInterface $em
    ):Response {

        $form = $this->createForm(RoomEquipmentType::class, null, [
            'room_id' => $room->getId()
        ]);

        $form->handleRequest($request);

        //si le formulaire est soumise et valide
        if($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();
            $existingEquipment = $data['existingEquipment'];
            $newEquipmentName = trim($data['newEquipment']);

            //cas 1 => équipement existant séléctionné
            if($existingEquipment){
                $room->addEquipment($existingEquipment);
                $em->flush();

                $this->addFlash('success', '"' . $existingEquipment->getName() . '" ajouté à la salle.');
            }

            //cas 2 => nouvel équipement à créer
            elseif($newEquipmentName !== ''){
                $equipment = new \App\Entity\Equipment();
                $equipment->setName($newEquipmentName);
                $room->addEquipment($equipment);
                
                $em->persist($equipment);
                $em->flush();

                $this->addFlash('success', 'Équipement "' . $newEquipmentName . '" créé et ajouté à la salle.');
            }
            //cas 3 =>rien rempli
            else{
                 $this->addFlash('error', 'Veuillez choisir un équipement existant ou saisir un nouveau nom.');
            }

            return $this->redirectToRoute('app_admin_room_equipments', ['id' => $room->getId()]);
        }

        return $this->render('admin/rooms/equipments.html.twig', [
            'room' => $room,
            'form' => $form,
        ]);
    }

    /**
     * 
     */
    #[Route('/rooms/{id}/equipments/{equipmentId}/remove', name: 'app_admin_room_equipment_remove', requirements: ['id' => '\d+', 'equipmentId' => '\d+'], methods: ['POST'])]
    public function roomEquipmentRemove(
        Room                   $room,
        int                    $equipmentId,
        Request                $request,
        EntityManagerInterface $em
    ):Response {
        if (!$this->isCsrfTokenValid('remove_equipment_' . $equipmentId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_room_equipments', ['id' => $room->getId()]);
        }

        //trouver l'équipement dans la collection de la salle
        $equipment = null;
        foreach($room->getEquipments() as $eq){
            if($eq->getId() === $equipmentId){
                $equipment = $eq;
                break;
            }
        }

        if(!$equipment){
            $this->addFlash('error', 'Équipement introuvable.');
            return $this->redirectToRoute('app_admin_room_equipments', ['id' => $room->getId()]);
        }

        $name = $equipment->getName();
        $room->removeEquipment($equipment);

        $em->flush();

        $this->addFlash('success', '"' . $name . '" retiré de la salle.');

        return $this->redirectToRoute('app_admin_room_equipments', ['id' => $room->getId()]);

    }



}