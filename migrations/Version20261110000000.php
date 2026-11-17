<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'jkcaf store tables (user, products, product_stock, orders)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS product');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, size VARCHAR(2) NOT NULL, quantity INT DEFAULT 1 NOT NULL, payment_method VARCHAR(20) NOT NULL, payment_status VARCHAR(20) NOT NULL, status VARCHAR(20) DEFAULT \'active\' NOT NULL, card_name VARCHAR(255) DEFAULT NULL, card_last4 VARCHAR(4) DEFAULT NULL, shipping_name VARCHAR(255) NOT NULL, shipping_phone VARCHAR(50) NOT NULL, shipping_address VARCHAR(255) NOT NULL, shipping_city VARCHAR(100) NOT NULL, shipping_postal_code VARCHAR(20) NOT NULL, receipt_email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_E52FFDEEA76ED395 (user_id), INDEX IDX_E52FFDEE4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(20) DEFAULT \'customer\' NOT NULL, UNIQUE INDEX UNIQ_USER_USERNAME (username), UNIQUE INDEX UNIQ_USER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE products (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, price NUMERIC(10, 2) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_stock (id INT AUTO_INCREMENT NOT NULL, size VARCHAR(2) NOT NULL, quantity INT DEFAULT 0 NOT NULL, product_id INT NOT NULL, UNIQUE INDEX UNIQ_PRODUCT_SIZE (product_id, size), INDEX IDX_EA6A2D3C4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4584665A FOREIGN KEY (product_id) REFERENCES products (id)');
        $this->addSql('ALTER TABLE product_stock ADD CONSTRAINT FK_EA6A2D3C4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4584665A');
        $this->addSql('ALTER TABLE product_stock DROP FOREIGN KEY FK_EA6A2D3C4584665A');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE product_stock');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, productname VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }
}
