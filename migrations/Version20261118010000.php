<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add editable note to stock_movements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_movements ADD note VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_movements DROP note');
    }
}
