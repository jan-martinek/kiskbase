<?php

namespace App\Presenters;

use Nette;
use DibiConnection;

class SetupPresenter extends Nette\Application\UI\Presenter
{
    /** @var DibiConnection @inject */
    public $connection;

    /** @var \Kdyby\Translation\Translator @inject */
    public $translator;

    public function renderDefault()
    {
        $tables = array('answer', 'entry', 'entry_tag', 'question', 'tag', 'user', 'example_course', 'checklist');

        $success = true;
        $report = array();
        foreach ($tables as $table) {
            if ($this->connection->query("SHOW TABLES LIKE '$table'")->count('*')) {
                $tableReport = $this->translator->translate('messages.app.setup.tableInstalled', null, array('table' => $table));
                $report[] = '<span style="color: green">'.$tableReport.'</span>';
            } else {
                $tableReport = $this->translator->translate('messages.app.setup.tableNotInstalled', null, array('table' => $table));
                $report[] = '<span style="color: red">'.$tableReport.'</span>';
                $success = false;
            }
        }
        $this->template->report = '<p>'.implode('<br>', $report).'</p>';
        $this->template->success = $success;
    }

    public function actionInstall()
    {
        $dropTables = false;
        $timeZone = '+02:00';
        $collate = 'utf8_czech_ci';
        $name = 'John Doe';
        $email = 'john.doe@gmail.com';

        $this->connection->query('SET NAMES utf8;');
        $this->connection->query('SET foreign_key_checks = 0;');
        $this->connection->query("SET time_zone = '$timeZone';");
        $this->connection->query("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';");

        if ($dropTables) {
            $this->connection->query('DROP TABLE IF EXISTS `answer`;');
        }
        $this->connection->query("CREATE TABLE IF NOT EXISTS `answer` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `entry_id` int(10) unsigned NOT NULL,
          `user_id` int(11) NOT NULL,
          `created_at` datetime NOT NULL,
          `text` text COLLATE $collate NOT NULL,
          `current` tinyint(1) NOT NULL DEFAULT '1',
          PRIMARY KEY (`id`),
          KEY `entry_id` (`entry_id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `answer_ibfk_6` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE,
          CONSTRAINT `answer_ibfk_7` FOREIGN KEY (`entry_id`) REFERENCES `entry` (`id`) ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=$collate;");

        if ($dropTables) {
            $this->connection->query('DROP TABLE IF EXISTS `checklist`;');
        }
        $this->connection->query("CREATE TABLE `checklist` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `entry_id` int(10) unsigned NOT NULL,
          `created_at` datetime NOT NULL,
          `updated_at` datetime NOT NULL,
          `name` varchar(100) COLLATE $collate NOT NULL,
          `text` text COLLATE $collate NOT NULL,
          `state` text COLLATE $collate NOT NULL,
          `public` enum('private','public') COLLATE $collate NOT NULL,
          `removed` tinyint(1) unsigned NOT NULL,
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          KEY `entry_id` (`entry_id`),
          CONSTRAINT `checklist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
          CONSTRAINT `checklist_ibfk_2` FOREIGN KEY (`entry_id`) REFERENCES `entry` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=$collate;");



        if ($dropTables) {
            $this->connection->query('DROP TABLE IF EXISTS `entry`;');
        }
        $this->connection->query("CREATE TABLE IF NOT EXISTS `entry` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `guarantor_id` int(11) NOT NULL,
          `question_id` int(11) DEFAULT NULL,
          `answer_id` int(11) DEFAULT NULL,
          `namespace` varchar(30) COLLATE $collate NULL,
          `public` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `access` enum('private','public') COLLATE $collate NOT NULL DEFAULT 'private',
          `removed` int(1) unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `guarantor_id` (`guarantor_id`),
          KEY `answer_id` (`answer_id`),
          KEY `question_id` (`question_id`),
          CONSTRAINT `entry_ibfk_4` FOREIGN KEY (`guarantor_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE,
          CONSTRAINT `entry_ibfk_5` FOREIGN KEY (`answer_id`) REFERENCES `answer` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
          CONSTRAINT `entry_ibfk_6` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=$collate;");

        if ($dropTables) {
            $this->connection->query('DROP TABLE IF EXISTS `entry_tag`;');
        }
        $this->connection->query("CREATE TABLE IF NOT EXISTS `entry_tag` (
          `entry_id` int(10) unsigned NOT NULL,
          `tag_id` int(11) NOT NULL,
          KEY `entry_id` (`entry_id`),
          KEY `tag_id` (`tag_id`),
          CONSTRAINT `entry_tag_ibfk_10` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `entry_tag_ibfk_11` FOREIGN KEY (`entry_id`) REFERENCES `entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=$collate;");

        if ($dropTables) {
            $this->connection->query('DROP TABLE IF EXISTS `person`;');
        }
        $this->connection->query("CREATE TABLE `person` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(100) COLLATE $collate NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=$collate;");

        if ($dropTables) {
            $this->connection->query('DROP TABLE IF EXISTS `question`;');
        }
        $this->connection->query("CREATE TABLE IF NOT EXISTS `question` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `entry_id` int(10) unsigned NOT NULL,
          `user_id` int(11) NOT NULL,
          `created_at` datetime NOT NULL,
          `text` text COLLATE $collate NOT NULL,
          `current` tinyint(1) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `entry_id` (`entry_id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `question_ibfk_5` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE,
          CONSTRAINT `question_ibfk_6` FOREIGN KEY (`entry_id`) REFERENCES `entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=$collate;");

        if ($dropTables) {
            $this->connection->query('DROP TABLE IF EXISTS `tag`;');
        }
        $this->connection->query("CREATE TABLE IF NOT EXISTS `tag` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `text` varchar(50) COLLATE $collate NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `text` (`text`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=$collate;");

        if ($dropTables) {
            $this->connection->query('DROP TABLE IF EXISTS `user`;');
        }
        $this->connection->query("CREATE TABLE IF NOT EXISTS `user` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(100) COLLATE $collate NOT NULL,
          `surname` varchar(100) COLLATE $collate NOT NULL,
          `email` varchar(100) COLLATE $collate NOT NULL,
          `google_id` varchar(100) COLLATE $collate NOT NULL,
          `google_access_token` varchar(255) COLLATE $collate NOT NULL,
          `picture` varchar(255) COLLATE $collate NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=$collate;");

        // example table
        if ($dropTables) {
            $this->connection->query('DROP TABLE IF EXISTS `example_course`;');
        }
        $this->connection->query("CREATE TABLE IF NOT EXISTS `example_course` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `type` enum('A','B','A-','B-','B+') COLLATE $collate NOT NULL  COMMENT 'compulsory (A) and other types',
          `grade` enum('bachelor','master') COLLATE $collate NOT NULL  COMMENT 'grade',
          `spec` varchar(20) COLLATE $collate NOT NULL COMMENT 'branch of the study',
          `code` varchar(20) COLLATE $collate NOT NULL COMMENT 'official ID of the course',
          `name` varchar(255) COLLATE $collate NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=$collate;");

        $this->connection->query("INSERT IGNORE INTO `example_course` (`id`, `type`, `grade`, `spec`, `code`, `name`) VALUES
          (1,   'A',    'bachelor', '', 'XYZ101',   'Science 101'),
          (2,   'B',    'bachelor', 'data', 'XYZ102',   'Introduction to research'),
          (3,   'B',    'master',   'data',     'MS001',    'Educational data mining')");

        // first user
        $this->connection->query("INSERT IGNORE INTO `user` (`id`, `name`, `surname`, `email`, `google_id`, `google_access_token`, `picture`) VALUES
            (1, '$name',    '', '$email',   '', '', '')");

        // example entry
        $this->connection->query("INSERT IGNORE INTO `entry` (`id`, `guarantor_id`, `question_id`, `answer_id`, `public`, `access`, `removed`) VALUES
          (1,   1,  1,  1,  0,  '', 0)");
        $this->connection->query("INSERT IGNORE INTO `question` (`id`, `entry_id`, `user_id`, `created_at`, `text`, `current`) VALUES
          (1,   1,  1,  '2015-07-11 18:24:59',  'What makes this KB so cool?',  0)");
        $this->connection->query("INSERT IGNORE INTO `answer` (`id`, `entry_id`, `user_id`, `created_at`, `text`, `current`) VALUES
          (1,   1,  1,  '2015-07-11 18:24:59',  '<p>It enables you to bring text and tabular data together.</p>
            <p>You can write use #hashtags (#sic) and mention people (from custom table).</p>
            <p>The most beautiful thing is that you can insert SQL queries anywhere and results blend into the text. (select * FROM example_course WHERE spec = \"data\";)</p>
            <p>KiskBase uses Medium editor for simple wysiwyg duties, hence you can write not only paragraphs</p>
            <ul><li>but even lists</li><li>and other <strong>beautiful</strong> things</li></ul>
            <p>Try out the editor by clicking on the <i>edit</i> button.</p>
            <p>#example</p>',1)");
        
        //example tags
        $this->connection->query("INSERT INTO `entry_tag` (`entry_id`, `tag_id`) VALUES
          (1, 1),
          (1, 2),
          (1, 3);");

        $this->connection->query("INSERT INTO `tag` (`id`, `text`) VALUES
          (3, 'example'),
          (1, 'hashtags'),
          (2, 'sic');");

        //example person
        $this->connection->query("INSERT IGNORE INTO `person` (`id`, `name`) VALUES (1, '$name')");

        $this->redirect('Setup:default');
    }
}
