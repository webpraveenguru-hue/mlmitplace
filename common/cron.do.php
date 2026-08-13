<?php

// file execute by page load
if (!defined('OK_LOADME')) {
    die("^-^ DODODO");
}

if ($cfgrow['cronts'] < $lastdatetm && $dothisok == INSTALL_KEYS) {
    /* ========= */
    /*  Do Task  */
    /* ========= */

    // process commission
    dotrxwallet();

    // delete old session
    dellog_sess();

    // check expired member
    do_expmbr();

    // remove unpaid member
    do_delinactivembr();

    // autoreg by wallet
    do_planautoregbywallet();

    // check expired sales
    if (defined('ISLOADSTORE')) {
        do_expsalesitem();
    }

    // process interval commission pool
    if ($cfgtoken['isdocmpool'] == '1') {
        do_intvcmpool(32);
    }

    // process database backup
    do_dbbakup();

    // process newsletter
    do_newsletter();

    // update cron
    $data = array(
        'cronts' => $nowdatetm,
    );
    $db->update(DB_TBLPREFIX . '_configs', $data, array('cfgid' => $didId));
}
