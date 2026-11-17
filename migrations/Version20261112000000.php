<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user.is_active/created_at, products.created_by_id/created_at; rename role employee to staff';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_active TINYINT DEFAULT 1 NOT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql("UPDATE user SET role = 'staff' WHERE role = 'employee'");
        $this->addSql('ALTER TABLE products ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5AB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B3BA5A5AB03A8386 ON products (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5AB03A8386');
        $this->addSql('DROP INDEX IDX_B3BA5A5AB03A8386 ON products');
        $this->addSql('ALTER TABLE products DROP created_at, DROP created_by_id');
        $this->addSql("UPDATE user SET role = 'employee' WHERE role = 'staff'");
        $this->addSql('ALTER TABLE user DROP is_active, DROP created_at');
    }
}
