<?php
require_once "../controllers/LoadAttendanceController.php";

header('Content-Type: application/json; charset=utf-8');
$controller = new LoadAttendanceController();
echo json_encode($controller->getAttendanceContent("https://api.github.com/repos/apps4webte/curldata2021/contents"));
