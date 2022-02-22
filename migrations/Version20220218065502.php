<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220218065502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery_note ADD CONSTRAINT FK_1E21328EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1E21328EA76ED395 ON delivery_note (user_id)');
        $this->addSql('ALTER TABLE provider ADD activity VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery_note DROP FOREIGN KEY FK_1E21328EA76ED395');
        $this->addSql('DROP INDEX IDX_1E21328EA76ED395 ON delivery_note');
        $this->addSql('ALTER TABLE delivery_note CHANGE number number VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE machine CHANGE designation designation VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE constructor constructor VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE serial_number serial_number VARCHAR(100) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE internal_code internal_code VARCHAR(15) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE model model VARCHAR(100) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE organisation CHANGE designation designation VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE part CHANGE code code VARCHAR(20) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE designation designation VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE reference reference VARCHAR(100) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE remark remark LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE provider DROP activity, CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE address address VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE postal_code postal_code VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE city city VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE email email VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE phone phone VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE code code VARCHAR(30) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE reset_password_request CHANGE selector selector VARCHAR(20) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE hashed_token hashed_token VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE stock CHANGE place place VARCHAR(5) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE template CHANGE request request VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE remark remark LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE token CHANGE token token VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE username username VARCHAR(180) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE first_name first_name VARCHAR(100) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE last_name last_name VARCHAR(100) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE phone_number phone_number VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE email email VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE image_name image_name VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE workorder CHANGE remark remark LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE request request VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE implementation implementation VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE workorder_status CHANGE name name VARCHAR(20) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE workshop CHANGE name name VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}