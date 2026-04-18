<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418110519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insertion des salles par défaut de MediaSchool IRIS Nice';
    }

    public function up(Schema $schema): void
    {
       $rooms = [
            ['name' => 'Azur',                'capacity' => 32],
            ['name' => 'Nikaia',              'capacity' => 30],
            ['name' => 'La Bella',            'capacity' => 26],
            ['name' => 'La Baieta',           'capacity' => 22],
            ['name' => 'Riviera',             'capacity' => 33],
            ['name' => 'Bureau Informatique', 'capacity' => 6],
            ['name' => 'Baie des Anges',      'capacity' => 24],
            ['name' => 'La Pitchoun',         'capacity' => 22],
            ['name' => 'Saleya',              'capacity' => 31],
            ['name' => 'La Prom',             'capacity' => 30],
       ];

       foreach ($rooms as $room){
            $this->addSql(
                'INSERT INTO room (name, capacity) VALUES (:name, :capacity)',
                ['name' => $room['name'], 'capacity' => $room['capacity']]
            );
       }

    }

    public function down(Schema $schema): void
    {
        $rooms = ['Salle 101', 'Salle 102', 'Salle 103'];

        foreach ($rooms as $name) {
            $this->addSql(
                'DELETE FROM room WHERE name = :name',
                ['name' => $name]
            );
        }

    }
}
