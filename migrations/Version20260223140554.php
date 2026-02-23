<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223140554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE administrator (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_58DF0651A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE classe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE coordinator (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_15FE0E6AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE coordinator_classe (coordinator_id INT NOT NULL, classe_id INT NOT NULL, INDEX IDX_F3BB635FE7877946 (coordinator_id), INDEX IDX_F3BB635F8F5EA509 (classe_id), PRIMARY KEY (coordinator_id, classe_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, room_id INT NOT NULL, INDEX IDX_D338D58354177093 (room_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, reservation_start DATETIME NOT NULL, reservation_end DATETIME NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_42C8495554177093 (room_id), INDEX IDX_42C84955A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE room (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, capacity INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE student (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, classe_id INT NOT NULL, UNIQUE INDEX UNIQ_B723AF33A76ED395 (user_id), INDEX IDX_B723AF338F5EA509 (classe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, firstname VARCHAR(100) NOT NULL, lastname VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE administrator ADD CONSTRAINT FK_58DF0651A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE coordinator ADD CONSTRAINT FK_15FE0E6AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE coordinator_classe ADD CONSTRAINT FK_F3BB635FE7877946 FOREIGN KEY (coordinator_id) REFERENCES coordinator (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coordinator_classe ADD CONSTRAINT FK_F3BB635F8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipment ADD CONSTRAINT FK_D338D58354177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495554177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF338F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administrator DROP FOREIGN KEY FK_58DF0651A76ED395');
        $this->addSql('ALTER TABLE coordinator DROP FOREIGN KEY FK_15FE0E6AA76ED395');
        $this->addSql('ALTER TABLE coordinator_classe DROP FOREIGN KEY FK_F3BB635FE7877946');
        $this->addSql('ALTER TABLE coordinator_classe DROP FOREIGN KEY FK_F3BB635F8F5EA509');
        $this->addSql('ALTER TABLE equipment DROP FOREIGN KEY FK_D338D58354177093');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495554177093');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33A76ED395');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF338F5EA509');
        $this->addSql('DROP TABLE administrator');
        $this->addSql('DROP TABLE classe');
        $this->addSql('DROP TABLE coordinator');
        $this->addSql('DROP TABLE coordinator_classe');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE user');
    }
}
