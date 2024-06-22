<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240402074026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE `activity` (id INT AUTO_INCREMENT NOT NULL, practice_level_id INT DEFAULT NULL, stream_id INT DEFAULT NULL, `title` VARCHAR(255) DEFAULT NULL, `benefit` VARCHAR(255) DEFAULT NULL, `short_description` LONGTEXT DEFAULT NULL, `long_description` LONGTEXT DEFAULT NULL, `notes` LONGTEXT DEFAULT NULL, `external_id` VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_AC74095A58D2F8A2 (practice_level_id), INDEX IDX_AC74095AD0ED463E (stream_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `answer` (id INT AUTO_INCREMENT NOT NULL, answer_set_id INT DEFAULT NULL, `text` VARCHAR(255) DEFAULT NULL, `value` NUMERIC(10, 2) NOT NULL, `weight` NUMERIC(10, 2) NOT NULL, `order` INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_DADD4A25E20237BF (answer_set_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `answer_set` (id INT AUTO_INCREMENT NOT NULL, `external_id` VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `assessment` (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F7523D70166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `assessment_answer` (id INT AUTO_INCREMENT NOT NULL, answer_id INT DEFAULT NULL, user_id INT DEFAULT NULL, question_id INT DEFAULT NULL, stage_id INT DEFAULT NULL, `type` INT NOT NULL, `criteria` JSON NOT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_2E00DB4BAA334807 (answer_id), INDEX IDX_2E00DB4BA76ED395 (user_id), INDEX IDX_2E00DB4B1E27F6BF (question_id), INDEX IDX_2E00DB4B2298D193 (stage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `assessment_stream` (id INT AUTO_INCREMENT NOT NULL, stream_id INT DEFAULT NULL, assessment_id INT DEFAULT NULL, `status` INT NOT NULL, `expiration_date` DATE DEFAULT NULL, `score` NUMERIC(10, 2) NOT NULL, `external_id` VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_4342F72D0ED463E (stream_id), INDEX IDX_4342F72DD3DD5F1 (assessment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `assignment` (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, stage_id INT DEFAULT NULL, assigned_by_id INT DEFAULT NULL, `remark` LONGTEXT DEFAULT NULL, `completed_at` DATETIME DEFAULT NULL, `target_date` DATE DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_30C544BAA76ED395 (user_id), INDEX IDX_30C544BA2298D193 (stage_id), INDEX IDX_30C544BA6E6F1246 (assigned_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `business_function` (id INT AUTO_INCREMENT NOT NULL, metamodel_id INT DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL, `description` LONGTEXT DEFAULT NULL, `color` VARCHAR(255) DEFAULT NULL, `logo` VARCHAR(255) DEFAULT NULL, `order` INT NOT NULL, `external_id` VARCHAR(255) DEFAULT NULL, `icon` VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_C29E18AB44464978 (metamodel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('CREATE TABLE `evaluation` (id INT NOT NULL, `comment` LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql(
            'CREATE TABLE `failedlogin` (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) DEFAULT NULL, ip VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `group` (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_6DC044C5727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `group_project` (id INT AUTO_INCREMENT NOT NULL, group_id INT DEFAULT NULL, project_id INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_A9B384E2FE54D947 (group_id), INDEX IDX_A9B384E2166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `group_user` (id INT AUTO_INCREMENT NOT NULL, group_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_A4C98D39FE54D947 (group_id), INDEX IDX_A4C98D39A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `improvement` (id INT NOT NULL, new_id INT DEFAULT NULL, `plan` LONGTEXT DEFAULT NULL, `status` INT NOT NULL, INDEX IDX_A0C03C5DBD06B3B3 (new_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `mail_template` (id INT AUTO_INCREMENT NOT NULL, `type` INT NOT NULL, `name` VARCHAR(255) DEFAULT NULL, `subject` VARCHAR(255) DEFAULT NULL, `message` LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `mailing` (id INT AUTO_INCREMENT NOT NULL, mail_template_id INT DEFAULT NULL, user_id INT DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL, `surname` VARCHAR(255) DEFAULT NULL, `email` VARCHAR(255) DEFAULT NULL, `subject` VARCHAR(255) DEFAULT NULL, `message` LONGTEXT DEFAULT NULL, `attachment` VARCHAR(255) DEFAULT NULL, `status` INT NOT NULL, `reply_to` VARCHAR(255) DEFAULT NULL, `mail_from` VARCHAR(255) DEFAULT NULL, `mail_from_email` VARCHAR(255) DEFAULT NULL, `sent_date` DATETIME DEFAULT NULL, `status_msg` LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_3ED9315EB1057265 (mail_template_id), INDEX IDX_3ED9315EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `maturity_level` (id INT AUTO_INCREMENT NOT NULL, `level` INT NOT NULL, `description` LONGTEXT DEFAULT NULL, `external_id` VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `maturity_level_remark` (id INT AUTO_INCREMENT NOT NULL, maturity_level_id INT DEFAULT NULL, remark_id INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_2D53024561FD714C (maturity_level_id), INDEX IDX_2D5302457FAB7F77 (remark_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `metamodel` (id INT AUTO_INCREMENT NOT NULL, `name` VARCHAR(255) DEFAULT NULL, `max_score` INT NOT NULL, `default` TINYINT(1) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `practice` (id INT AUTO_INCREMENT NOT NULL, business_function_id INT DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL, `short_name` VARCHAR(255) DEFAULT NULL, `short_description` LONGTEXT DEFAULT NULL, `long_description` LONGTEXT DEFAULT NULL, `order` INT NOT NULL, `external_id` VARCHAR(255) DEFAULT NULL, `icon` VARCHAR(255) DEFAULT NULL, `slug` VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_7FEC344E26C05169 (business_function_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `practice_level` (id INT AUTO_INCREMENT NOT NULL, maturity_level_id INT DEFAULT NULL, practice_id INT DEFAULT NULL, `objective` VARCHAR(255) DEFAULT NULL, `external_id` VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_B225843861FD714C (maturity_level_id), INDEX IDX_B2258438ED33821 (practice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `project` (id INT AUTO_INCREMENT NOT NULL, assessment_id INT DEFAULT NULL, template_project_id INT DEFAULT NULL, metamodel_id INT DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL, `validation_threshold` NUMERIC(10, 2) NOT NULL, `description` LONGTEXT DEFAULT NULL, `template` TINYINT(1) NOT NULL, `external_id` VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_2FB3D0EEDD3DD5F1 (assessment_id), INDEX IDX_2FB3D0EE34A65A53 (template_project_id), INDEX IDX_2FB3D0EE44464978 (metamodel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `question` (id INT AUTO_INCREMENT NOT NULL, activity_id INT DEFAULT NULL, answer_set_id INT DEFAULT NULL, `text` LONGTEXT DEFAULT NULL, `order` INT NOT NULL, `quality` LONGTEXT DEFAULT NULL, `external_id` VARCHAR(255) DEFAULT NULL, `weight` NUMERIC(10, 2) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_B6F7494E81C06096 (activity_id), INDEX IDX_B6F7494EE20237BF (answer_set_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `remark` (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, stage_id INT DEFAULT NULL, `text` LONGTEXT DEFAULT NULL, `title` VARCHAR(255) DEFAULT NULL, `files` JSON NOT NULL COMMENT \'(DC2Type:json)\', `file` LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_E1CAD839A76ED395 (user_id), INDEX IDX_E1CAD8392298D193 (stage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `stage` (id INT AUTO_INCREMENT NOT NULL, assessment_stream_id INT DEFAULT NULL, assigned_to_id INT DEFAULT NULL, submitted_by_id INT DEFAULT NULL, `target_date` DATE DEFAULT NULL, `completed_at` DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, dType VARCHAR(255) NOT NULL, INDEX IDX_C27C936929CB5D2 (assessment_stream_id), INDEX IDX_C27C9369F4BD7827 (assigned_to_id), INDEX IDX_C27C936979F7D87D (submitted_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `stream` (id INT AUTO_INCREMENT NOT NULL, practice_id INT DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL, `description` LONGTEXT DEFAULT NULL, `order` INT NOT NULL, `external_id` VARCHAR(255) DEFAULT NULL, `weight` NUMERIC(10, 2) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, INDEX IDX_F0E9BE1CED33821 (practice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `system_config` (id INT AUTO_INCREMENT NOT NULL, `key` VARCHAR(255) DEFAULT NULL, `value` VARCHAR(255) DEFAULT NULL, `description` LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, `email` VARCHAR(255) DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL, `surname` VARCHAR(255) DEFAULT NULL, `roles` JSON NOT NULL COMMENT \'(DC2Type:json)\', `last_login` DATETIME DEFAULT NULL, `external_id` VARCHAR(255) DEFAULT NULL, `agreed_to_terms` TINYINT(1) NOT NULL, `last_changelog` VARCHAR(255) DEFAULT NULL, `time_zone` VARCHAR(255) DEFAULT NULL, `date_format` VARCHAR(255) DEFAULT NULL, `password` VARCHAR(255) DEFAULT NULL, `salt` VARCHAR(255) DEFAULT NULL, `failed_logins` INT NOT NULL, `secret_key` VARCHAR(255) DEFAULT NULL, `trusted_version` INT NOT NULL, `backup_codes` JSON NOT NULL COMMENT \'(DC2Type:json)\', `password_reset_hash` VARCHAR(255) DEFAULT NULL, `password_reset_hash_expiration` DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE `validation` (id INT NOT NULL, `status` INT NOT NULL, `comment` LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE `activity` ADD CONSTRAINT FK_AC74095A58D2F8A2 FOREIGN KEY (practice_level_id) REFERENCES `practice_level` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `activity` ADD CONSTRAINT FK_AC74095AD0ED463E FOREIGN KEY (stream_id) REFERENCES `stream` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `answer` ADD CONSTRAINT FK_DADD4A25E20237BF FOREIGN KEY (answer_set_id) REFERENCES `answer_set` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assessment` ADD CONSTRAINT FK_F7523D70166D1F9C FOREIGN KEY (project_id) REFERENCES `project` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assessment_answer` ADD CONSTRAINT FK_2E00DB4BAA334807 FOREIGN KEY (answer_id) REFERENCES `answer` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assessment_answer` ADD CONSTRAINT FK_2E00DB4BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assessment_answer` ADD CONSTRAINT FK_2E00DB4B1E27F6BF FOREIGN KEY (question_id) REFERENCES `question` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assessment_answer` ADD CONSTRAINT FK_2E00DB4B2298D193 FOREIGN KEY (stage_id) REFERENCES `stage` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assessment_stream` ADD CONSTRAINT FK_4342F72D0ED463E FOREIGN KEY (stream_id) REFERENCES `stream` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assessment_stream` ADD CONSTRAINT FK_4342F72DD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES `assessment` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assignment` ADD CONSTRAINT FK_30C544BAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assignment` ADD CONSTRAINT FK_30C544BA2298D193 FOREIGN KEY (stage_id) REFERENCES `stage` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `assignment` ADD CONSTRAINT FK_30C544BA6E6F1246 FOREIGN KEY (assigned_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `business_function` ADD CONSTRAINT FK_C29E18AB44464978 FOREIGN KEY (metamodel_id) REFERENCES `metamodel` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `evaluation` ADD CONSTRAINT FK_1323A575BF396750 FOREIGN KEY (id) REFERENCES `stage` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `group` ADD CONSTRAINT FK_6DC044C5727ACA70 FOREIGN KEY (parent_id) REFERENCES `group` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `group_project` ADD CONSTRAINT FK_A9B384E2FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `group_project` ADD CONSTRAINT FK_A9B384E2166D1F9C FOREIGN KEY (project_id) REFERENCES `project` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `group_user` ADD CONSTRAINT FK_A4C98D39FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `group_user` ADD CONSTRAINT FK_A4C98D39A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `improvement` ADD CONSTRAINT FK_A0C03C5DBD06B3B3 FOREIGN KEY (new_id) REFERENCES `assessment_stream` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `improvement` ADD CONSTRAINT FK_A0C03C5DBF396750 FOREIGN KEY (id) REFERENCES `stage` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `mailing` ADD CONSTRAINT FK_3ED9315EB1057265 FOREIGN KEY (mail_template_id) REFERENCES `mail_template` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `mailing` ADD CONSTRAINT FK_3ED9315EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `maturity_level_remark` ADD CONSTRAINT FK_2D53024561FD714C FOREIGN KEY (maturity_level_id) REFERENCES `maturity_level` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `maturity_level_remark` ADD CONSTRAINT FK_2D5302457FAB7F77 FOREIGN KEY (remark_id) REFERENCES `remark` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `practice` ADD CONSTRAINT FK_7FEC344E26C05169 FOREIGN KEY (business_function_id) REFERENCES `business_function` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `practice_level` ADD CONSTRAINT FK_B225843861FD714C FOREIGN KEY (maturity_level_id) REFERENCES `maturity_level` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `practice_level` ADD CONSTRAINT FK_B2258438ED33821 FOREIGN KEY (practice_id) REFERENCES `practice` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `project` ADD CONSTRAINT FK_2FB3D0EEDD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES `assessment` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `project` ADD CONSTRAINT FK_2FB3D0EE34A65A53 FOREIGN KEY (template_project_id) REFERENCES `project` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `project` ADD CONSTRAINT FK_2FB3D0EE44464978 FOREIGN KEY (metamodel_id) REFERENCES `metamodel` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `question` ADD CONSTRAINT FK_B6F7494E81C06096 FOREIGN KEY (activity_id) REFERENCES `activity` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `question` ADD CONSTRAINT FK_B6F7494EE20237BF FOREIGN KEY (answer_set_id) REFERENCES `answer_set` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `remark` ADD CONSTRAINT FK_E1CAD839A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `remark` ADD CONSTRAINT FK_E1CAD8392298D193 FOREIGN KEY (stage_id) REFERENCES `stage` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `stage` ADD CONSTRAINT FK_C27C936929CB5D2 FOREIGN KEY (assessment_stream_id) REFERENCES `assessment_stream` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `stage` ADD CONSTRAINT FK_C27C9369F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `stage` ADD CONSTRAINT FK_C27C936979F7D87D FOREIGN KEY (submitted_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `stream` ADD CONSTRAINT FK_F0E9BE1CED33821 FOREIGN KEY (practice_id) REFERENCES `practice` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `validation` ADD CONSTRAINT FK_16AC5B6EBF396750 FOREIGN KEY (id) REFERENCES `stage` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `activity` DROP FOREIGN KEY FK_AC74095A58D2F8A2');
        $this->addSql('ALTER TABLE `activity` DROP FOREIGN KEY FK_AC74095AD0ED463E');
        $this->addSql('ALTER TABLE `answer` DROP FOREIGN KEY FK_DADD4A25E20237BF');
        $this->addSql('ALTER TABLE `assessment` DROP FOREIGN KEY FK_F7523D70166D1F9C');
        $this->addSql('ALTER TABLE `assessment` DROP FOREIGN KEY FK_F7523D7032C8A3DE');
        $this->addSql('ALTER TABLE `assessment_answer` DROP FOREIGN KEY FK_2E00DB4BAA334807');
        $this->addSql('ALTER TABLE `assessment_answer` DROP FOREIGN KEY FK_2E00DB4BA76ED395');
        $this->addSql('ALTER TABLE `assessment_answer` DROP FOREIGN KEY FK_2E00DB4B1E27F6BF');
        $this->addSql('ALTER TABLE `assessment_answer` DROP FOREIGN KEY FK_2E00DB4B2298D193');
        $this->addSql('ALTER TABLE `assessment_answer` DROP FOREIGN KEY FK_2E00DB4B32C8A3DE');
        $this->addSql('ALTER TABLE `assessment_stream` DROP FOREIGN KEY FK_4342F72D0ED463E');
        $this->addSql('ALTER TABLE `assessment_stream` DROP FOREIGN KEY FK_4342F72DD3DD5F1');
        $this->addSql('ALTER TABLE `assessment_stream` DROP FOREIGN KEY FK_4342F7232C8A3DE');
        $this->addSql('ALTER TABLE `assignment` DROP FOREIGN KEY FK_30C544BAA76ED395');
        $this->addSql('ALTER TABLE `assignment` DROP FOREIGN KEY FK_30C544BA2298D193');
        $this->addSql('ALTER TABLE `assignment` DROP FOREIGN KEY FK_30C544BA6E6F1246');
        $this->addSql('ALTER TABLE `assignment` DROP FOREIGN KEY FK_30C544BA32C8A3DE');
        $this->addSql('ALTER TABLE `business_function` DROP FOREIGN KEY FK_C29E18AB44464978');
        $this->addSql('ALTER TABLE `evaluation` DROP FOREIGN KEY FK_1323A575BF396750');
        $this->addSql('ALTER TABLE `group` DROP FOREIGN KEY FK_6DC044C532C8A3DE');
        $this->addSql('ALTER TABLE `group` DROP FOREIGN KEY FK_6DC044C5727ACA70');
        $this->addSql('ALTER TABLE `group_project` DROP FOREIGN KEY FK_A9B384E2FE54D947');
        $this->addSql('ALTER TABLE `group_project` DROP FOREIGN KEY FK_A9B384E2166D1F9C');
        $this->addSql('ALTER TABLE `group_user` DROP FOREIGN KEY FK_A4C98D39FE54D947');
        $this->addSql('ALTER TABLE `group_user` DROP FOREIGN KEY FK_A4C98D39A76ED395');
        $this->addSql('ALTER TABLE `improvement` DROP FOREIGN KEY FK_A0C03C5DBD06B3B3');
        $this->addSql('ALTER TABLE `improvement` DROP FOREIGN KEY FK_A0C03C5DBF396750');
        $this->addSql('ALTER TABLE `mailing` DROP FOREIGN KEY FK_3ED9315EB1057265');
        $this->addSql('ALTER TABLE `mailing` DROP FOREIGN KEY FK_3ED9315EA76ED395');
        $this->addSql('ALTER TABLE `mailing` DROP FOREIGN KEY FK_3ED9315E32C8A3DE');
        $this->addSql('ALTER TABLE `maturity_level_remark` DROP FOREIGN KEY FK_2D53024561FD714C');
        $this->addSql('ALTER TABLE `maturity_level_remark` DROP FOREIGN KEY FK_2D5302457FAB7F77');
        $this->addSql('ALTER TABLE `practice` DROP FOREIGN KEY FK_7FEC344E26C05169');
        $this->addSql('ALTER TABLE `practice_level` DROP FOREIGN KEY FK_B225843861FD714C');
        $this->addSql('ALTER TABLE `practice_level` DROP FOREIGN KEY FK_B2258438ED33821');
        $this->addSql('ALTER TABLE `project` DROP FOREIGN KEY FK_2FB3D0EE32C8A3DE');
        $this->addSql('ALTER TABLE `project` DROP FOREIGN KEY FK_2FB3D0EEDD3DD5F1');
        $this->addSql('ALTER TABLE `project` DROP FOREIGN KEY FK_2FB3D0EE34A65A53');
        $this->addSql('ALTER TABLE `project` DROP FOREIGN KEY FK_2FB3D0EE44464978');
        $this->addSql('ALTER TABLE `question` DROP FOREIGN KEY FK_B6F7494E81C06096');
        $this->addSql('ALTER TABLE `question` DROP FOREIGN KEY FK_B6F7494EE20237BF');
        $this->addSql('ALTER TABLE `remark` DROP FOREIGN KEY FK_E1CAD839A76ED395');
        $this->addSql('ALTER TABLE `remark` DROP FOREIGN KEY FK_E1CAD8392298D193');
        $this->addSql('ALTER TABLE `remark` DROP FOREIGN KEY FK_E1CAD83932C8A3DE');
        $this->addSql('ALTER TABLE `stage` DROP FOREIGN KEY FK_C27C936929CB5D2');
        $this->addSql('ALTER TABLE `stage` DROP FOREIGN KEY FK_C27C9369F4BD7827');
        $this->addSql('ALTER TABLE `stage` DROP FOREIGN KEY FK_C27C936979F7D87D');
        $this->addSql('ALTER TABLE `stage` DROP FOREIGN KEY FK_C27C936932C8A3DE');
        $this->addSql('ALTER TABLE `stream` DROP FOREIGN KEY FK_F0E9BE1CED33821');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64932C8A3DE');
        $this->addSql('ALTER TABLE `validation` DROP FOREIGN KEY FK_16AC5B6EBF396750');
        $this->addSql('DROP TABLE `activity`');
        $this->addSql('DROP TABLE `answer`');
        $this->addSql('DROP TABLE `answer_set`');
        $this->addSql('DROP TABLE `assessment`');
        $this->addSql('DROP TABLE `assessment_answer`');
        $this->addSql('DROP TABLE `assessment_stream`');
        $this->addSql('DROP TABLE `assignment`');
        $this->addSql('DROP TABLE `business_function`');
        $this->addSql('DROP TABLE `evaluation`');
        $this->addSql('DROP TABLE `failedlogin`');
        $this->addSql('DROP TABLE `group`');
        $this->addSql('DROP TABLE `group_project`');
        $this->addSql('DROP TABLE `group_user`');
        $this->addSql('DROP TABLE `improvement`');
        $this->addSql('DROP TABLE `mail_template`');
        $this->addSql('DROP TABLE `mailing`');
        $this->addSql('DROP TABLE `maturity_level`');
        $this->addSql('DROP TABLE `maturity_level_remark`');
        $this->addSql('DROP TABLE `metamodel`');
        $this->addSql('DROP TABLE `practice`');
        $this->addSql('DROP TABLE `practice_level`');
        $this->addSql('DROP TABLE `project`');
        $this->addSql('DROP TABLE `question`');
        $this->addSql('DROP TABLE `remark`');
        $this->addSql('DROP TABLE `stage`');
        $this->addSql('DROP TABLE `stream`');
        $this->addSql('DROP TABLE `system_config`');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE `validation`');
    }
}
