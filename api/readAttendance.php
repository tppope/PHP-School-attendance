<?php
require_once "../controllers/ReadAttendanceController.php";

header('Content-Type: application/json; charset=utf-8');
$controller = new ReadAttendanceController();

switch ($_GET["actionToDo"]){
    case "getLectures":
        echo json_encode($controller->getLectures());break;
    case "getAttendanceData":
        echo json_encode($controller->divideAllAttendanceIntoObjects());break;
    case "getUserCount":
        echo json_encode($controller->getUserCount());break;

}
