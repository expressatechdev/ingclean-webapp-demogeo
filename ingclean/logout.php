<?php
/**
 * INGClean - Logout
 */
require_once 'includes/init.php';

auth()->logout();
redirect('/login.php');
