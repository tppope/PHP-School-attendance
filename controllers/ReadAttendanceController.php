<?php
require_once "DatabaseController.php";
require_once "../models/Lecture.php";
require_once "../models/User.php";
require_once "../models/Attendance.php";


class ReadAttendanceController extends DatabaseController
{
    private array $response;

    public function getLectures(){
        try {
            $lectures = $this->getAllLectures();
            $this->setSuccess();
            return array_merge($this->getResponse(),array("lectures"=>$lectures));
        }
        catch (Exception $exception){
            $this->setFailure();
            return array_merge($this->getResponse(),array("message"=>$exception->getMessage()));
        }
    }
    public function getAllLectures(): array
    {
        $statement = $this->mysqlDatabase->prepareStatement("SELECT LECTURE.id, LECTURE.date FROM LECTURE ORDER BY LECTURE.date ASC");

        try {
            $statement->setFetchMode(PDO::FETCH_CLASS, "Lecture");
            $statement->execute();
            return $statement->fetchAll();
        }
        catch (Exception $exception){
            throw $exception;
        }
    }

    public function getAllUsers(): array
    {
        $statement = $this->mysqlDatabase->prepareStatement("SELECT USER.id, USER.name, USER.surname FROM USER");

        try {
            $statement->setFetchMode(PDO::FETCH_CLASS, "User");
            $statement->execute();
            return $statement->fetchAll();
        }
        catch (Exception $exception){
            throw $exception;
        }
    }
    public function getAttendanceOfUserInLecture($userid,$lectureId): array
    {
        $statement = $this->mysqlDatabase->prepareStatement("SELECT ATTENDANCE.id, ATTENDANCE.timestamp, ATTENDANCE.action 
                                                                    FROM ATTENDANCE
                                                                    WHERE ATTENDANCE.user_id = :userId AND ATTENDANCE.lecture_id = :lectureId
                                                                    ORDER BY ATTENDANCE.timestamp ASC");

        try {
            $statement->bindValue(":userId",$userid,PDO::PARAM_INT);
            $statement->bindValue(":lectureId",$lectureId,PDO::PARAM_INT);
            $statement->setFetchMode(PDO::FETCH_CLASS, "Attendance");
            $statement->execute();
            return $statement->fetchAll();
        }
        catch (Exception $exception){
            throw $exception;
        }
    }

    public function divideAllAttendanceIntoObjects(){
        try {
            $usersAttendance = $this->getAllUsers();
            foreach ($usersAttendance as $user) {
                $user->setLectures($this->getAllLectures());
                foreach ($user->getLectures() as $lecture) {
                    $lecture->setAttendance($this->getAttendanceOfUserInLecture($user->getId(),$lecture->getId()));
                    $lecture->calculateMinutesOnLecture($this->getLastLeftFromLecture($lecture->getId()));
                }
                $user->getMinutesAndAttendanceCount();
            }
            $this->setSuccess();
            return array_merge($this->getResponse(),array("userAttendance"=>$usersAttendance));
        }
        catch (Exception $e) {
            $this->setFailure();
            return array_merge($this->getResponse(),array("message"=>$e->getMessage()));
        }
    }

    public function getLastLeftFromLecture($lectureId){
        $statement = $this->mysqlDatabase->prepareStatement("SELECT MAX(ATTENDANCE.timestamp) FROM ATTENDANCE WHERE ATTENDANCE.lecture_id = :lectureId AND ATTENDANCE.action='Left'");

        try {
            $statement->bindValue(":lectureId",$lectureId,PDO::PARAM_INT);
            $statement->execute();
            return strtotime($statement->fetchColumn());
        }
        catch (Exception $exception){
            throw $exception;
        }

    }

    public function getUserCount(): array{
        $statement = $this->mysqlDatabase->prepareStatement("SELECT LECTURE.id, LECTURE.date, COUNT(DISTINCT ATTENDANCE.user_id) AS userCount
                                                                    FROM ATTENDANCE
                                                                    INNER JOIN LECTURE ON LECTURE.id = ATTENDANCE.lecture_id
                                                                    GROUP BY ATTENDANCE.lecture_id, LECTURE.date
                                                                    ORDER BY LECTURE.date");

        try {
            $statement->setFetchMode(PDO::FETCH_CLASS, "Lecture");
            $statement->execute();
            $this->setSuccess();
            $userCount = $statement->fetchAll();
            return array_merge($this->getResponse(),array("userCount"=>$userCount));
        }
        catch (Exception $exception){
            $this->setFailure();
            return array_merge($this->getResponse(),array("message"=>$exception->getMessage()));

        }
    }


    private function setSuccess(){
        $this->setResponse(array(
            "status" => "success",
            "error" => false,
        ));
    }

    private function setFailure(){
        $this->setResponse(array(
            "status" => "failed",
            "error" => true
        ));
    }

    /**
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * @param array $response
     */
    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

}
