<?php

namespace Tests\Unit\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\ElGamal\DLogProof;
use App\Voting\CryptoSystems\ElGamal\EGPrivateKey;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use Exception;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class EGPublicKeyTest
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 */
class EGPublicKeyTest extends TestCase
{

    /**
     * @test
     * @throws Exception
     */
    public function combine()
    {
        $a = EGPublicKey::fromArray(['g' => 1, 'p' => 8, 'q' => 1, 'y' => 3], false, 10);
        $b = EGPublicKey::fromArray(['g' => 1, 'p' => 8, 'q' => 1, 'y' => 3], false, 10);
        $c = $a->combine($b);
        $this->assertTrue($c->g->equals($a->g));
        $this->assertTrue($c->p->equals($a->p));
        $this->assertTrue($c->q->equals($a->q));
        $this->assertTrue($c->y->equals(BI(1))); // 3*3 mod 8 = 1
    }

    /**
     * @test
     */
    public function verify_sk_proof()
    {

        $public_key = EGPublicKey::fromArray([

            "g" => "148874922249631876342824215371860408013040080177434923044817373825719339375687244738471060299150" .
                "401507840318822060902869386614644588964942152739895478892011448573526110585722365787343195051280426" .
                "023728645704265508552014481117465798718112491147816743090626934424423686974499706482326218800017095" .
                "351430479136614328832871500034298023922293615836086866432433497277919762472479486189304238661804105" .
                "584582726066271112700400912030735802389053039944722029307832074723945784985077647031912882495476598" .
                "999971311661302597006044338912322981823484031759474502844334112659667891310245736295460486378489022" .
                "43503970966798589660808533",

            "p" => "1632863208493301000238405503380545732960161477118595538973916730908621480040646579903858363495375" .
                "2941675645562182498120750264980492381375579367675648771293800310370964745767014243638518442553823973" .
                "4829952673040443267770476629574802693913227893783846194285964464469846943061876447674624609656225800" .
                "8756433921263177581789595840901667639897567126617963789855768731707617721884323315069515788106125705" .
                "3019133078545928983562221396313169622475509818442661047018436264806901023966236718367204710755935899" .
                "0137503061077380023641379174265957374038711141877508043465647312506091968466381839039823878845782661" .
                "36503697493474682071",

            "q" => "61329566248342901292543872769978950870633559608669337131139375508370458778917",

            "y" => "9979875145095565224439434038161012878033238590837386389383372881441062481998118445999516992674642" .
                "6580917324721019371526092331652571468201064752045948192013576162679382266665628589137681092211696411" .
                "5057679828850773374954089583651745007882065430643899439852598063005117461571243966241203074522366815" .
                "6057500569675098629792195634766152085042442431323540488552092432679949264725541653466149670454400404" .
                "3097637285398930939053519740161800951969919919363918957443760123942318654776896587722506934259929156" .
                "8553531898311866946794219098159757863910613990638321482803567988874500375273062501767042802869136499" .
                "8327599884945835202"

        ], false, 10);

        $dlog_proof = DLogProof::fromArray([

            "challenge" => "1165377119724850468148062563043749312955162922428",

            "commitment" => "3421152755449183423330696293651924583658435062553293618904563017542083269274903051086584" .
                "0477754062434115072668113936699265602903974745633000352416980434662499075411494565352782604412319798" .
                "7557108878212732530113593252190580374010249491127134132797304283734477914003367058024733009710780814" .
                "8912284097926902828275890956631296219028736817077394458823691105250632954283986723653725690783492708" .
                "4124232810248016698531149887272627252322012802711977415531521611282977185984286975399018982071996453" .
                "0054541892798092660623662698139754223488513441916885708585077569814910174966168481753043401641670376" .
                "6385213247826691444285114701",

            "response" => "21309276677749107072903080798730520004739468970991152035961621996336930759480"

        ], 10);

        $this->assertTrue(
            $public_key->verifySecretKeyProof($dlog_proof, [EGPrivateKey::class, 'DLogChallengeGenerator'])
        );

    }

}