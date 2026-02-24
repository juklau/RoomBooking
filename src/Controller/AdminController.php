<?php

namespace App\Controller;

use App\Repository\ClasseRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\StudentRepository;
use App\Repository\CoordinatorRepository;

use App\Entity\Room;
use App\Entity\Classe;
use App\Form\ClasseType;
use App\Form\RoomType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    /************************************ Salles ********************************************/

    /**
     * lister les salles
     */
    #[Route('/rooms', name: 'app_admin_rooms')]
    public function rooms(RoomRepository $roomRepo): Response
    {
        //récup toutes les salles avec leur équipements
        $rooms = $roomRepo->findAll();

        return $this->render('admin/rooms/index.html.twig', [
            'rooms' => $rooms,
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
        Room $room,
        Request $request,
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
        Room $room,
        Request $request,
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
        Classe $classe,
        Request $request,
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





   
    








}