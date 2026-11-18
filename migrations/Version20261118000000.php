<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stock_movements (history of stock postings and removals)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE stock_movements (id INT AUTO_INCREMENT NOT NULL, product_name VARCHAR(255) NOT NULL, size VARCHAR(2) NOT NULL, type VARCHAR(10) NOT NULL, quantity INT NOT NULL, quantity_before INT NOT NULL, quantity_after INT NOT NULL, actor_name VARCHAR(180) NOT NULL, actor_role VARCHAR(20) NOT NULL, source VARCHAR(30) DEFAULT 'adjustment' NOT NULL, created_at DATETIME NOT NULL, product_id INT DEFAULT NULL, actor_id INT DEFAULT NULL, INDEX IDX_A0BE93C94584665A (product_id), INDEX IDX_A0BE93C910DAF24A (actor_id), INDEX IDX_STOCK_MOVEMENT_CREATED (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4");
        $this->addSql('ALTER TABLE stock_movements ADD CONSTRAINT FK_A0BE93C94584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stock_movements ADD CONSTRAINT FK_A0BE93C910DAF24A FOREIGN KEY (actor_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE stock_movements');
    }
}
