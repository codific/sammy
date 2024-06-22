<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240402074323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        //the user admin@example.com with admin as a password
        $this->addSql(
            "INSERT INTO `user` (`id`, `email`, `name`, `surname`, `roles`, `last_login`, `external_id`, `agreed_to_terms`, `last_changelog`, `time_zone`, `date_format`, `password`, `salt`, `failed_logins`, `secret_key`, `trusted_version`, `backup_codes`, `password_reset_hash`, `password_reset_hash_expiration`, `created_at`, `updated_at`, `deleted_at`)
VALUES
	(1, 'admin@example.com', 'SAMMY', 'Administrator', '[\"ROLE_VALIDATOR\", \"ROLE_IMPROVER\", \"ROLE_EVALUATOR\", \"ROLE_AUDITOR\", \"ROLE_MANAGER\", \"ROLE_USER\"]', NULL, NULL, 1, NULL, NULL, NULL, '$2y$13\$gLUGLwS0Qe93g4SyBYesI.oXdDmfFDhXtJWdT4BCyMNqtGF5c/te.', NULL, 0, 'AB4FHDUHYVGW7IAB', 1, '[]', NULL, NULL, '2024-04-02 08:45:31', '2024-04-02 08:45:31', NULL);
"
        );
        $this->addSql(
            "INSERT INTO `metamodel` (`id`, `name`, `max_score`, `default`, `created_at`, `updated_at`, `deleted_at`)
VALUES
	(1, 'SAMM', 3, 0, '2024-04-02 07:53:45', '2024-04-02 07:53:45', NULL);
"
        );

        $this->addSql(
            "INSERT INTO `group` (`id`, `parent_id`, `name`, `created_at`, `updated_at`, `deleted_at`)
VALUES
	(1, NULL, 'Default Team', '2024-04-02 10:19:33', '2024-04-02 10:19:33', NULL);
"
        );
        $this->addSql(
            "INSERT INTO `group_user` (`id`, `group_id`, `user_id`, `created_at`, `updated_at`, `deleted_at`)
VALUES
	(1, 1, 1, '2024-04-02 10:20:00', '2024-04-02 10:20:00', NULL);
"
        );

        // current & target scores by assessment stream
        $this->addSql(
            "CREATE VIEW current_and_desired_scores_by_assessment_stream  AS
               SELECT assessment_stream.id as assessment_stream_id, stream.id as stream_id,
               stream.name as stream_name,
               SUM(CASE WHEN project.template = 0 THEN COALESCE(answer.value, 0) ELSE 0 END) as current_score,
               SUM(CASE WHEN project.template = 1 THEN COALESCE(answer.value, 0) ELSE 0 END) as target_posture_score,
               activity.id AS activity_id
               FROM stream
               LEFT JOIN assessment_stream ON assessment_stream.stream_id = stream.id
               LEFT JOIN stage ON stage.assessment_stream_id = assessment_stream.id AND stage.id IN 
                   (
                   SELECT max(`id`) AS id FROM stage 
                   WHERE (stage.dType = 'evaluation')
                   GROUP BY stage.assessment_stream_id, stage.dType
                   )                       
               LEFT JOIN assessment_answer ON stage.id = assessment_answer.stage_id AND assessment_answer.type = 0 
               LEFT JOIN answer ON answer.ID = assessment_answer.answer_id
               LEFT JOIN assessment ON assessment_stream.assessment_id = assessment.id
               LEFT JOIN project ON assessment.project_id = project.id
               LEFT JOIN practice ON stream.practice_id = practice.id
               LEFT JOIN business_function ON business_function.id = practice.business_function_id
               LEFT JOIN answer_set ON answer_set.id = answer.answer_set_id
               LEFT JOIN question ON question.answer_set_id = answer_set.id AND question.id = assessment_answer.question_id
               LEFT JOIN activity ON question.activity_id = activity.id
               GROUP BY assessment_stream.id, stream.id, question.id",
        );

        // count of questions by activity
        $this->addSql(
            "CREATE VIEW activity_question_count AS 
                SELECT COUNT(question.id) as question_count, activity.id as activity_id
                FROM maturity_level
                INNER JOIN practice_level ON practice_level.maturity_level_id = maturity_level.id
                INNER JOIN activity ON practice_level.id = activity.practice_level_id
                INNER JOIN question ON question.activity_id = activity.id
                GROUP BY activity_id",
        );

        // questions fully joined with activity, stream practice and business function, grouped by metamodel
        $this->addSql(
            "CREATE VIEW question_fully_joined AS
                SELECT
                    metamodel_id                                                         as metamodel_id,
                    business_function.name                                               as bf_name,
                    business_function.order                                              as bf_order,
                    business_function.id                                                 as bf_id,
                    practice.short_name                                                  as short_name,
                    practice.order                                                       as p_order,
                    stream.name                                                          as stream_name,
                    stream.id                                                            as stream_id,
                    activity.id                                                          as activity_id,
                    question.id                                                          as question_id
                FROM business_function
                LEFT JOIN metamodel ON business_function.metamodel_id = metamodel.id
                INNER JOIN practice ON practice.business_function_id = business_function.id
                INNER JOIN stream ON stream.practice_id = practice.id
                LEFT JOIN activity ON stream.id = activity.stream_id
                LEFT JOIN question ON activity.id = question.activity_id
                LEFT JOIN practice_level ON practice_level.id = activity.practice_level_id
                LEFT JOIN maturity_level ON practice_level.maturity_level_id = maturity_level.id"
        );

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
