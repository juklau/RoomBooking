<?php

namespace App\Controller;

use App\Repository\ClasseRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\StudentRepository;

use App\Entity\Classe;
use App\Entity\User;
use App\Entity\Student;

use App\Form\AddStudentToClasseType;

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
    public function classAddStudent(
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

}