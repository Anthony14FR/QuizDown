<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250114155617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE penalty_quiz CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE penalty_quiz ADD CONSTRAINT FK_7671BC94BF396750 FOREIGN KEY (id) REFERENCES quiz (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quiz ADD type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE timed_quiz CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE timed_quiz ADD CONSTRAINT FK_D2F89C9BBF396750 FOREIGN KEY (id) REFERENCES quiz (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE penalty_quiz DROP FOREIGN KEY FK_7671BC94BF396750');
        $this->addSql('ALTER TABLE penalty_quiz CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE quiz DROP type');
        $this->addSql('ALTER TABLE timed_quiz DROP FOREIGN KEY FK_D2F89C9BBF396750');
        $this->addSql('ALTER TABLE timed_quiz CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }
}
