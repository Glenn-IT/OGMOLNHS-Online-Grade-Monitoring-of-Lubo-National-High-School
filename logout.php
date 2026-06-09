<?php
require_once 'config/session.php';
session_destroy();
header('Location: /OGMS-Lubo-National-High-School/index.php');
exit;
