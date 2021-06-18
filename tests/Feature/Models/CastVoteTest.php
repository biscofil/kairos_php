<?php


namespace Tests\Feature\Models;


use App\Models\CastVote;
use Tests\TestCase;

class CastVoteTest extends TestCase
{

    /**
     * @test
     */
    public function setVerifiedBy()
    {
        $vote = new CastVote();

        $vote->verified_by = 0;

        $vote->setVerifiedBy(5);
        self::assertTrue($vote->isVerifiedBy(5));

        $vote->setVerifiedBy(2);
        self::assertTrue($vote->isVerifiedBy(5));
        self::assertTrue($vote->isVerifiedBy(2));
    }

}
