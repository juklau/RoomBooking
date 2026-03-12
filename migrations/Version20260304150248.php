<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304150248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insertion des classes par défaut de MediaSchool IRIS Nice';
    }

    public function up(Schema $schema): void
    {
        $classes = [
            'ECS1', 'ECS2', 'ECS3 A Brand Digit', 'ECS3 B Com Event', 
            'ECS4 A Brand Digit', 'ECS4 B Com Event', 'ECS4 DA',
            'ECS5 Com Digit', 'ECS5 Com Event', 'NSS 1', 'NSS 2', 'PSL 1',
            'PSL 2', 'PSL 3', 'Iris SLAM 1', 'Iris SISR 1',
            'Iris SLAM 2', 'Iris SISR 2'
        ];

        foreach($classes as $classe){
            $this->addSql('INSERT INTO classe (name) VALUES (:name)',
                            ['name' => $classe]
            );
        }

    }

    public function down(Schema $schema): void
    {
        $classes = [
            'ECS1', 'ECS2', 'ECS3 A Brand Digit', 'ECS3 B Com Event', 
            'ECS4 A Brand Digit', 'ECS4 B Com Event', 'ECS4 DA',
            'ECS5 Com Digit', 'ECS5 Com Event', 'NSS 1', 'NSS 2', 'PSL 1',
            'PSL 2', 'PSL 3', 'Iris SLAM 1', 'Iris SISR 1',
            'Iris SLAM 2', 'Iris SISR 2'
        ];

        foreach($classes as $classe){
            $this->addSql('DELETE FROM classe WHERE name = :name',
                            ['name' => $classe]
            );
        }

    }
}
