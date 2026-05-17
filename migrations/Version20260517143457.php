<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517143457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add title field to billing_course';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE billing_course ADD title VARCHAR(255)');

        $this->addSql("UPDATE billing_course SET title = CASE code
            WHEN 'php-basic' THEN 'Основы PHP'
            WHEN 'symfony-start' THEN 'Старт с Symfony'
            WHEN 'postgresql-base' THEN 'PostgreSQL для веб-разработки'
            ELSE code
        END WHERE title IS NULL");

        $this->addSql('ALTER TABLE billing_course ALTER title SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE billing_course DROP title');
    }
}
