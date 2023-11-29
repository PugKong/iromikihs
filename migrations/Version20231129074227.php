<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231129074227 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                CREATE TABLE anime (
                    id INT NOT NULL,
                    series_id UUID DEFAULT NULL,
                    name VARCHAR(255) NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    kind VARCHAR(255) DEFAULT NULL,
                    status VARCHAR(255) NOT NULL,
                    aired_on DATE DEFAULT NULL,
                    released_on DATE DEFAULT NULL,
                    PRIMARY KEY(id)
                )
                SQL,
        );
        $this->addSql('CREATE INDEX IDX_130459425278319C ON anime (series_id)');
        $this->addSql('COMMENT ON COLUMN anime.series_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN anime.aired_on IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN anime.released_on IS \'(DC2Type:date_immutable)\'');

        $this->addSql(
            <<<'SQL'
                CREATE TABLE anime_rate (
                    id UUID NOT NULL,
                    user_id UUID NOT NULL,
                    anime_id INT NOT NULL,
                    shikimori_id INT DEFAULT NULL,
                    score INT NOT NULL,
                    status VARCHAR(255) NOT NULL,
                    PRIMARY KEY(id)
                )
                SQL,
        );
        $this->addSql('CREATE INDEX IDX_7019ED6DA76ED395 ON anime_rate (user_id)');
        $this->addSql('CREATE INDEX IDX_7019ED6D794BBE89 ON anime_rate (anime_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7019ED6DA76ED395B189EEEB ON anime_rate (user_id, shikimori_id)');
        $this->addSql('COMMENT ON COLUMN anime_rate.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN anime_rate.user_id IS \'(DC2Type:uuid)\'');

        $this->addSql(
            <<<'SQL'
                CREATE TABLE series (
                    id UUID NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                    PRIMARY KEY(id)
                )
                SQL,
        );
        $this->addSql('COMMENT ON COLUMN series.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN series.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(
            <<<'SQL'
                CREATE TABLE series_rate (
                    id UUID NOT NULL,
                    user_id UUID NOT NULL,
                    series_id UUID NOT NULL,
                    score DOUBLE PRECISION NOT NULL,
                    state VARCHAR(255) NOT NULL,
                    PRIMARY KEY(id)
                )
                SQL,
        );
        $this->addSql('CREATE INDEX IDX_3AABBEFBA76ED395 ON series_rate (user_id)');
        $this->addSql('CREATE INDEX IDX_3AABBEFB5278319C ON series_rate (series_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3AABBEFBA76ED3955278319C ON series_rate (user_id, series_id)');
        $this->addSql('COMMENT ON COLUMN series_rate.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN series_rate.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN series_rate.series_id IS \'(DC2Type:uuid)\'');

        $this->addSql(
            <<<'SQL'
                CREATE TABLE "user" (
                    id UUID NOT NULL,
                    username VARCHAR(180) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    PRIMARY KEY(id)
                )
                SQL,
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');

        $this->addSql(
            <<<'SQL'
                CREATE TABLE user_sync (
                    user_id UUID NOT NULL,
                    token TEXT DEFAULT NULL,
                    account_id INT DEFAULT NULL,
                    state VARCHAR(255) DEFAULT NULL,
                    synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                    PRIMARY KEY(user_id)
                )
                SQL,
        );
        $this->addSql('COMMENT ON COLUMN user_sync.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_sync.synced_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(
            <<<'SQL'
                CREATE TABLE messenger_messages (
                    id BIGSERIAL NOT NULL,
                    body TEXT NOT NULL,
                    headers TEXT NOT NULL,
                    queue_name VARCHAR(190) NOT NULL,
                    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                    available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                    delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                    PRIMARY KEY(id)
                )
                SQL,
        );
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                    BEGIN
                        PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                        RETURN NEW;
                    END;
                $$ LANGUAGE plpgsql;
                SQL,
        );
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql(
            <<<'SQL'
                CREATE TRIGGER notify_trigger
                    AFTER INSERT OR UPDATE ON messenger_messages
                    FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();
                SQL,
        );

        $this->addSql(
            <<<'SQL'
                ALTER TABLE anime ADD CONSTRAINT FK_130459425278319C
                    FOREIGN KEY (series_id) REFERENCES series (id) NOT DEFERRABLE INITIALLY IMMEDIATE
                SQL,
        );
        $this->addSql(
            <<<'SQL'
                ALTER TABLE anime_rate ADD CONSTRAINT FK_7019ED6DA76ED395
                    FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
                SQL,
        );
        $this->addSql(
            <<<'SQL'
                ALTER TABLE anime_rate ADD CONSTRAINT FK_7019ED6D794BBE89
                    FOREIGN KEY (anime_id) REFERENCES anime (id) NOT DEFERRABLE INITIALLY IMMEDIATE
                SQL,
        );
        $this->addSql(
            <<<'SQL'
                ALTER TABLE series_rate ADD CONSTRAINT FK_3AABBEFBA76ED395
                    FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
                SQL,
        );
        $this->addSql(
            <<<'SQL'
                ALTER TABLE series_rate ADD CONSTRAINT FK_3AABBEFB5278319C
                    FOREIGN KEY (series_id) REFERENCES series (id) NOT DEFERRABLE INITIALLY IMMEDIATE
                SQL,
        );
        $this->addSql(
            <<<'SQL'
                ALTER TABLE user_sync ADD CONSTRAINT FK_56AAB9B3A76ED395
                    FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
                SQL,
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE anime DROP CONSTRAINT FK_130459425278319C');
        $this->addSql('ALTER TABLE anime_rate DROP CONSTRAINT FK_7019ED6DA76ED395');
        $this->addSql('ALTER TABLE anime_rate DROP CONSTRAINT FK_7019ED6D794BBE89');
        $this->addSql('ALTER TABLE series_rate DROP CONSTRAINT FK_3AABBEFBA76ED395');
        $this->addSql('ALTER TABLE series_rate DROP CONSTRAINT FK_3AABBEFB5278319C');
        $this->addSql('ALTER TABLE user_sync DROP CONSTRAINT FK_56AAB9B3A76ED395');
        $this->addSql('DROP TABLE anime');
        $this->addSql('DROP TABLE anime_rate');
        $this->addSql('DROP TABLE series');
        $this->addSql('DROP TABLE series_rate');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_sync');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
