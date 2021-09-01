<?php

class Lecture implements \JsonSerializable
{
    private int $id;
    private string $date;
    private array $attendance;
    private float $minutes = 0;
    private bool $isLeft = true;
    private int $userCount;

    public function calculateMinutesOnLecture($lastEvidentLeft){
        if (!empty($this->attendance)){
            $i = 1;
            for (;$i<sizeof($this->attendance);$i = $i +2) {
                $this->minutes = $this->minutes + $this->diffOfTwoDatesInMinutes(strtotime($this->attendance[$i-1]->getTimestamp()),strtotime($this->attendance[$i]->getTimestamp()));
            }
            if (sizeof($this->attendance)&1){
                $this->setIsLeft(false);
                $this->minutes = $this->minutes + $this->diffOfTwoDatesInMinutes(strtotime($this->attendance[$i-1]->getTimestamp()),$lastEvidentLeft);
            }
        }
        $this->minutes = round($this->minutes,2);
    }

    private function diffOfTwoDatesInMinutes ($fromTime, $toTime): float{
        if ($fromTime >= $toTime)
            return 0;
        else {
            $diff = $toTime - $fromTime;
            return round($diff / (60),2);
        }
    }

    /**
     * @return int
     */
    public function getUserCount(): int
    {
        return $this->userCount;
    }

    /**
     * @param int $userCount
     */
    public function setUserCount(int $userCount): void
    {
        $this->userCount = $userCount;
    }



    /**
     * @return bool
     */
    public function isLeft(): bool
    {
        return $this->isLeft;
    }

    /**
     * @param bool $isLeft
     */
    public function setIsLeft(bool $isLeft): void
    {
        $this->isLeft = $isLeft;
    }



    /**
     * @return float
     */
    public function getMinutes(): float
    {
        return $this->minutes;
    }

    /**
     * @param float $minutes
     */
    public function setMinutes(float $minutes): void
    {
        $this->minutes = $minutes;
    }



    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return array
     */
    public function getAttendance(): array
    {
        return $this->attendance;
    }

    /**
     * @param array $attendance
     */
    public function setAttendance(array $attendance): void
    {
        $this->attendance = $attendance;
    }




}
