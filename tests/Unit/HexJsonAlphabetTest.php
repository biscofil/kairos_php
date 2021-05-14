<?php


namespace Tests\Unit;


use Tests\TestCase;

/**
 * This encoding takes hald the space required by ASCII
 * Class HexJsonAlphabet
 * @package Tests\Unit
 */
class HexJsonAlphabetTest extends TestCase
{

    public const alphabet = '0123456789{}:",';

    /**
     * @param array $obj
     * @return string
     */
    private function json2hex(array $obj): string
    {
        // TODO check if object contains array [] which will be escaped
        $objJson = json_encode($obj);
        // remove all invalid chars
        $objJson = preg_replace("/[^0-9\{\}\:\"\,]/", '', $objJson);
        // remap
        $chars = array_map(function ($char) {
            return dechex(strpos(self::alphabet, $char));
        }, str_split($objJson));
        return implode('', $chars);
    }

    /**
     * @param string $a
     * @return array
     */
    private function hex2json(string $a): array
    {
        // remap
        $chars = array_map(function ($char) {
            return self::alphabet[hexdec($char)];
        }, str_split($a));
        return json_decode(implode('', $chars), true);
    }

    /**
     *
     */
    public function testRemapping()
    {
        $a = ['1' => 124, '2' => 3522, '4' => 2];
        // TODO add spaces
        $d = $this->hex2json($this->json2hex($a));
        $this->assertEquals($a, $d);
    }

    /**
     *
     */
    public function testRemappingWithArray()
    {
        $a = ['1' => 124, '2' => 3522, '4' => [2]];
        $d = $this->hex2json($this->json2hex($a));
        $this->assertNotEquals($a, $d);
    }


}
