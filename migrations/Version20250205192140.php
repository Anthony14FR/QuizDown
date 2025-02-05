<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250205192140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE name name VARCHAR(25) NOT NULL');
        $this->addSql('ALTER TABLE penalty_quiz CHANGE time_penalty time_limit INT DEFAULT NULL');
        $this->addSql('ALTER TABLE submission_answer CHANGE user_answer user_answer JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE tag CHANGE name name VARCHAR(25) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE theme theme VARCHAR(20) DEFAULT \'emerald\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` CHANGE theme theme VARCHAR(50) DEFAULT \'emerald\'');
        $this->addSql('ALTER TABLE tag CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE submission_answer CHANGE user_answer user_answer LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE penalty_quiz CHANGE time_limit time_penalty INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category CHANGE name name VARCHAR(255) NOT NULL');
    }
}
