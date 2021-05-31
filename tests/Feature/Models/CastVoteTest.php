<?php


namespace Tests\Feature\Models;


use App\Models\CastVote;
use Tests\TestCase;

class CastVoteTest extends TestCase
{

    /**
     * @ TODO test
     */
    public function setVerifiedBy(){
        $vote = new CastVote();

        $vote->verified_by = 0;
        dump($vote->verified_by);
        dump(decbin($vote->verified_by));

        $vote->setVerifiedBy(5);
        dump($vote->verified_by);
        dump(decbin($vote->verified_by));

        $vote->setVerifiedBy(2);
        dump($vote->verified_by);
        dump(decbin($vote->verified_by));
    }

}
