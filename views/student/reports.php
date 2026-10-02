<?php
// views/student/reports.php
// Official DepEd report cards (SF9) are generated and issued by school administration and faculty.
require_once '../../config/session.php';
requireStudent();
header('Location: dashboard.php');
exit;
