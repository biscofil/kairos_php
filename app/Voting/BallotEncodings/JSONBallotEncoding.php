<?php


namespace App\Voting\BallotEncodings;


abstract class JSONBallotEncoding implements BallotEncoding
{

    /**
     * Checks if the ballot is valid
     * @param string $jsonBallot
     * @return bool
     */
    public static function isBallotValid(string $jsonBallot): bool
    {

        $decoded = json_decode($jsonBallot, true);

        // check for extra spaces and numbers as strings
        if ($jsonBallot !== json_encode($decoded)) {
            return false;
        }

        // values have to be integers
        $invalidItemsD = array_filter($decoded, function ($v) {
            return !is_int($v);
        });
        if (count($invalidItemsD)) {
            return false;
        }

        return true;
    }

}
