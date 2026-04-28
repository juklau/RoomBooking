<?php

namespace App\Command;

use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:reservations:update-status',
    description: 'Passe les réservations passées en statut "passed"',
)]

class UpdateReservationStatusCommand extends Command
{

    public function __construct(
        private ReservationRepository $reservationRepo,
        private EntityManagerInterface $em
    )
    {
        return parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $reservations = $this->reservationRepo->findPassedReservations($now);

        $count = 0;

        foreach($reservations as $reservation){

            $reservation->setStatus('passed');
            $count++;
        }

        $this->em->flush();

        $output->writeln("$count réservation(s) passée(s) en statut 'passed.");

        return Command::SUCCESS;


    }





}