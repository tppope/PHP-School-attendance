<?php

class User implements \JsonSerializable
{
    private int $id;
    private string $name;
    private string $surname;
    private array $lectures;
    private float $minutes=0;
    private int $attendanceCount=0;

    public function getMinutesAndAttendanceCount(){
        foreach ($this->lectures as $lecture){
            $lectureMinutes = $lecture->getMinutes();
            $this->minutes = $this->minutes + $lectureMinutes;
            if ($lectureMinutes)
                $this->attendanceCount = $this->attendanceCount +1;
        }
        $this->minutes = round($this->minutes,2);
    }

    /**
     * @return float|int
     */
    public function getMinutes(): float|int
    {
        return $this->minutes;
    }

    /**
     * @param float|int $minutes
     */
    public function setMinutes(float|int $minutes): void
    {
        $this->minutes = $minutes;
    }

    /**
     * @return int
     */
    public function getAttendanceCount(): int
    {
        return $this->attendanceCount;
    }

    /**
     * @param int $attendanceCount
     */
    public function setAttendanceCount(int $attendanceCount): void
    {
        $this->attendanceCount = $attendanceCount;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSurname(): string
    {
        return $this->surname;
    }

    /**
     * @param string $surname
     */
    public function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    /**
     * @return array
     */
    public function getLectures(): array
    {
        return $this->lectures;
    }

    /**
     * @param array $lectures
     */
    public function setLectures(array $lectures): void
    {
        $this->lectures = $lectures;
    }



    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
