<?php


namespace Tests\Unit;


use Tests\TestCase;

/**
 * Class HelperTest
 * @package Tests\Unit
 */
class HelperTest extends TestCase
{

    /**
     * @test
     * @throws \Exception
     */
    public function testExtractDomain()
    {
        static::assertEquals('website.com', extractDomain('https://website.com/'));
//        $this->assertEquals("website.com", extractDomain("website.com/"));
//        $this->assertEquals("website.com", extractDomain("website.com"));
    }

}
