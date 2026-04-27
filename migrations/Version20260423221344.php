<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423221344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD classe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_reservation_classe FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_reservation_classe ON reservation (classe_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_reservation_classe');
        $this->addSql('ALTER TABLE reservation DROP INDEX IDX_reservation_classe');
        $this->addSql('ALTER TABLE reservation DROP COLUMN classe_id');
    }
}
