<?php


namespace App\Enums;


interface GetSetIdentifier
{

    public static function getIdentifier($obj): string;

    public static function getByIdentifier(string $identifier): string;

    public function getClass(): string;

}
