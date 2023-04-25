<?php namespace App\Sport\Provider;

abstract class SportCompetitor {

    abstract function name(): string;

    abstract function logo(): ?string;

    public function toArray(): array {
        return [
            'name' => $this->name(),
            'logo' => $this->logo()
        ];
    }

}
