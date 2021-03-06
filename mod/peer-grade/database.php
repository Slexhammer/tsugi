<?php

// To allow this to be called directly or from admin/upgrade.php
if ( !isset($PDOX) ) {
    require_once "../../config.php";
    $CURRENT_FILE = __FILE__;
    require $CFG->dirroot."/admin/migrate-setup.php";
}

// Dropping tables
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}peer_flag",
"drop table if exists {$CFG->dbprefix}peer_grade",
"drop table if exists {$CFG->dbprefix}peer_submit",
"drop table if exists {$CFG->dbprefix}peer_assn"
);

// Creating tables
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}peer_assn",
"create table {$CFG->dbprefix}peer_assn (
    assn_id    INTEGER NOT NULL KEY AUTO_INCREMENT,
    link_id    INTEGER NOT NULL,
    due_at     DATETIME NOT NULL,

    json         TEXT NULL,

    updated_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}peer_assn_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}peer_submit",
"create table {$CFG->dbprefix}peer_submit (
    submit_id  INTEGER NOT NULL KEY AUTO_INCREMENT,
    assn_id    INTEGER NOT NULL,
    user_id    INTEGER NOT NULL,

    json       TEXT NULL,
    note       TEXT NULL,
    reflect    TEXT NULL,

    regrade    TINYINT NULL,

    inst_points   DOUBLE NULL,
    inst_note  TEXT NULL,
    inst_id    INTEGER NULL,

    updated_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}peer_submit_ibfk_1`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}peer_submit_ibfk_2`
        FOREIGN KEY (`assn_id`)
        REFERENCES `{$CFG->dbprefix}peer_assn` (`assn_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(assn_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

array( "{$CFG->dbprefix}peer_text",
"create table {$CFG->dbprefix}peer_text (
    text_id      INTEGER NOT NULL KEY AUTO_INCREMENT,
    assn_id      INTEGER NOT NULL,
    user_id      INTEGER NOT NULL,

    data         TEXT NULL,
    json         TEXT NULL,

    updated_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}peer_text_ibfk_1`
        FOREIGN KEY (`assn_id`)
        REFERENCES `{$CFG->dbprefix}peer_assn` (`assn_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}peer_text_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

array( "{$CFG->dbprefix}peer_grade",
"create table {$CFG->dbprefix}peer_grade (
    grade_id     INTEGER NOT NULL KEY AUTO_INCREMENT,
    submit_id    INTEGER NOT NULL,
    user_id      INTEGER NOT NULL, -- The user doing the grading

    points       DOUBLE NULL,
    note         TEXT NULL,

    json         TEXT NULL,

    updated_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}peer_grade_ibfk_1`
        FOREIGN KEY (`submit_id`)
        REFERENCES `{$CFG->dbprefix}peer_submit` (`submit_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(submit_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

array( "{$CFG->dbprefix}peer_flag",
"create table {$CFG->dbprefix}peer_flag (
    flag_id      INTEGER NOT NULL KEY AUTO_INCREMENT,
    submit_id    INTEGER NOT NULL,
    grade_id     INTEGER NULL,
    user_id      INTEGER NOT NULL, -- The user doing the flagging

    note         TEXT NULL,
    response     TEXT NULL,
    handled      BOOLEAN NOT NULL DEFAULT FALSE,
    respond_id   INTEGER NOT NULL,  -- The responder's user_id

    json         TEXT NULL,

    updated_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}peer_flag_ibfk_1`
        FOREIGN KEY (`submit_id`)
        REFERENCES `{$CFG->dbprefix}peer_submit` (`submit_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(submit_id, grade_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

);

// Database upgrade
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // Version 2014042200 improvements
    if ( $oldversion < 2014042200 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD regrade TINYINT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Support for instructor grading
    if ( $oldversion < 201412222310 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD inst_points DOUBLE NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD inst_note TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD inst_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    return 201412222310;
}; // Don't forget the semicolon on anonymous functions :)

// Do the actual migration if we are not in admin/upgrade.php
if ( isset($CURRENT_FILE) ) {
    include $CFG->dirroot."/admin/migrate-run.php";
    $OUTPUT->footer();
}

