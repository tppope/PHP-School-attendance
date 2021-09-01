<?php
require_once "DatabaseController.php";

class LoadAttendanceController extends DatabaseController
{
    private array $response;


    public function getAttendanceContent($href): array{
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_URL, $href);
        curl_setopt($curl, CURLOPT_REFERER, $href);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.4 (KHTML, like Gecko) Chrome/5.0.375.125 Safari/533.4");
        $str = curl_exec($curl);
        curl_close($curl);
        $this->uploadLectures(json_decode($str));
        $this->setSuccess();
        return $this->getResponse();
    }

    private function uploadLectures($lectures){
        $lectureNames = array();
        foreach ($lectures as $lecture){
            $lectureName = $lecture->name;
            $lectureDate =  substr($lectureName,0,4)."-".substr($lectureName,4,2)."-".substr($lectureName,6,2);
            array_push($lectureNames,$lectureDate);
            try {
                $lectureId = $this->uploadLectureToDatabase($lectureDate);
                $this->uploadAttendance($lectureId,$lecture->download_url);
            }
            catch (PDOException $PDOException){}
        }
        foreach (array_diff($this->getAllLectures(),$lectureNames) as $index => $lectureToDelete)
            $this->deleteLecture($lectureToDelete);

    }

    private function getAllLectures(){
        $statement = $this->mysqlDatabase->prepareStatement("SELECT LECTURE.date FROM LECTURE");
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_NUM);
        return $statement->fetchAll(PDO::FETCH_COLUMN,0);
    }
    private function deleteLecture($date){
        $statement = $this->mysqlDatabase->prepareStatement("DELETE FROM LECTURE WHERE LECTURE.date = :date");
        $statement->bindValue(':date', $date, PDO::PARAM_STR);
        $statement->execute();
        return $statement->rowCount();
    }

    private function uploadAttendance($lectureId, $lectureDownloadLink){
        $lines = explode(PHP_EOL,$this->getAttendanceCSV($lectureDownloadLink));
        foreach ($lines as $index => $line){
            $lineArray = str_getcsv($line, "\t");

            if ($index > 0 && ($lineArray[0])){
                $fullName = $this->getNameSurnameFromFullName($lineArray[0]);
                $name = $fullName["name"];
                $surname = $fullName["surname"];
                $user_id = $this->getUserId($name,$surname);
                if ($user_id == false)
                    $user_id = $this->uploadUserToDatabase($name,$surname);
                $action = $lineArray[1];
                if (str_contains($lineArray[2],"AM"))
                    $timestamp = date('Y-m-d H:i:s',date_create_from_format('m/d/Y, H:i:s A',$lineArray[2])->getTimestamp());
                else
                    $timestamp = date('Y-m-d H:i:s',date_create_from_format('d/m/Y, H:i:s',$lineArray[2])->getTimestamp());
                $this->uploadAttendanceToDatabase($lectureId,$user_id,$timestamp,$action);
            }
        }
    }

    private function uploadAttendanceToDatabase($lectureId, $userId, $timestamp, $action){
        $statement = $this->mysqlDatabase->prepareStatement("INSERT INTO ATTENDANCE (lecture_id, user_id, timestamp, action)
                                                                    VALUES (:lectureId, :userId, :timestamp, :action)");
        $statement->bindValue(':lectureId', $lectureId, PDO::PARAM_INT);
        $statement->bindValue(':userId', $userId, PDO::PARAM_INT);
        $statement->bindValue(':timestamp', $timestamp, PDO::PARAM_STR);
        $statement->bindValue(':action', $action, PDO::PARAM_STR);
        try {
            $statement->execute();
            return $this->mysqlDatabase->getConnection()->lastInsertId();
        }
        catch (PDOException $PDOException){
            throw $PDOException;
        }
    }
    private function uploadUserToDatabase($name,$surname){
        $statement = $this->mysqlDatabase->prepareStatement("INSERT INTO USER (name, surname)
                                                                    VALUES (:name, :surname)");
        $statement->bindValue(':name', $name, PDO::PARAM_STR);
        $statement->bindValue(':surname', $surname, PDO::PARAM_STR);
        try {
            $statement->execute();
            return $this->mysqlDatabase->getConnection()->lastInsertId();
        }
        catch (PDOException $PDOException){
            throw $PDOException;
        }
    }

    private function getUserId($name,$surname){
        $statement = $this->mysqlDatabase->prepareStatement("SELECT USER.id FROM USER WHERE USER.name = :name AND USER.surname = :surname");
        $statement->bindValue(':name', $name, PDO::PARAM_STR);
        $statement->bindValue(':surname', $surname, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetchColumn();
    }

    private function getNameSurnameFromFullName($fullName): array{
        $explodeFullName = preg_split("/ +/",$fullName);
        $fullName = array(
            "name" => "",
            "surname"=>"");
        $i = 0;
        $name = "";
        for (;$i<(sizeof($explodeFullName)-1);$i++){
            $name = $name.$explodeFullName[$i]." ";
        }
        $fullName["name"] = trim($name);
        $fullName["surname"] = $explodeFullName[$i];
        return $fullName;
    }

    private function getAttendanceCSV($lectureDownloadLink){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$lectureDownloadLink);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        $csvAttendance = curl_exec($curl);
        $csvAttendance = mb_convert_encoding($csvAttendance,'UTF-8','UTF-16LE');
        curl_close($curl);
        return $csvAttendance;
    }

    private function uploadLectureToDatabase($lectureDate){
        $statement = $this->mysqlDatabase->prepareStatement("INSERT INTO LECTURE (date)
                                                                    VALUES (:date)");
        $statement->bindValue(':date', $lectureDate, PDO::PARAM_STR);
        try {
            $statement->execute();
            return $this->mysqlDatabase->getConnection()->lastInsertId();
        }
        catch (PDOException $PDOException){
            throw $PDOException;
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
