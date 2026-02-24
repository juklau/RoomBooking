<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();

        //rediriger selon le role
        if(in_array('ROLE_ADMIN', $user->getRoles())){
            return $this->redirectToRoute('app_admin_dashboard');
        }

        if(in_array('ROLE_COORDINATOR', $user->getRoles())){
            return $this->redirectToRoute('app_coordinator_dashboard');
        }

        return $this->redirectToRoute('app_student_dashboard');
    }
}
