<?php namespace App\Sport\Provider;

abstract class SportLiveStatus {

    public abstract function stage(): string;

    public abstract function score(): string;

    public abstract function setScores(): string;

    public abstract function createdAt(): int;

    public function toArray(): array {
        return [
            'stage' => $this->stage(),
            'score' => $this->score(),
            'setScores' => $this->setScores(),
            'createdAt' => $this->createdAt()
        ];
    }

}
