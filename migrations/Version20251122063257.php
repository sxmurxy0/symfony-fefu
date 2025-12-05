<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251122063257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE access_tokens (id SERIAL NOT NULL, user_id INT NOT NULL, value VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_58D184BC1D775834 ON access_tokens (value)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_58D184BCA76ED395 ON access_tokens (user_id)');
        $this->addSql('COMMENT ON COLUMN access_tokens.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN access_tokens.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE access_tokens ADD CONSTRAINT FK_58D184BCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users ADD password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE users ADD roles JSON NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E96B01BC5B ON users (phone_number)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE access_tokens DROP CONSTRAINT FK_58D184BCA76ED395');
        $this->addSql('DROP TABLE access_tokens');
        $this->addSql('DROP INDEX UNIQ_1483A5E96B01BC5B');
        $this->addSql('ALTER TABLE users DROP password');
        $this->addSql('ALTER TABLE users DROP roles');
    }
}
