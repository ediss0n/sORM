<?php
// Including our library
include __DIR__ . '/../lib/orm.php';

// Configuring database
$cfg = array();
$cfg['host'] = 'localhost';
$cfg['username'] = 'root';
$cfg['password'] = '';
$cfg['database'] = 'ci_db';

// Describing necessary models
// There we have to describe table relations

// User table
class Users extends ORMTable {
    public function __construct() {
        global $cfg;
        $script = "CREATE TABLE IF NOT EXISTS `users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) CHARACTER SET utf8 NOT NULL,
                    PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        parent::__construct('users', $cfg, $script);
    }
}

// Describing Users fields
class usersRecord extends ORMRecord {
    public $id;
    public $username;
}

// Publications table
class Publications extends ORMTable {
    public function __construct() {
        global $cfg;
        $script = "CREATE TABLE IF NOT EXISTS `publications` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `title` varchar(100) DEFAULT NULL,
                    `body` text,
                    `user_id` int(11) DEFAULT NULL,
                    `pub_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        parent::__construct('publications', $cfg, $script);
        // Describing relations
        $this->relations['user_id'] = "users";
    }
}

// Describing Publications fields
class publicationsRecord extends ORMRecord {
    public $id;
    public $title;
    public $body;
    public $user_id;
    public $pub_date;
    
    public $users; // field for user object
  }

// Comments table
class Comments extends ORMTable {
    public function __construct() {
        global $cfg;
        $script = "CREATE TABLE IF NOT EXISTS `comments` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `pub_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `comment` text,
                    `comm_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        parent::__construct('comments', $cfg, $script);
        // Describing relations
        $this->relations['user_id'] = "users";
        $this->relations['pub_id'] = "publications";
    }
}

// Describing Publications fields
class commentsRecord extends ORMRecord {
    public $id;
    public $pub_id;
    public $user_id;
    public $comment;
    public $comm_date;
    
    public $publications; // field for publication object
    public $users; // field for user object
    
}

?>

