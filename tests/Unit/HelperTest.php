<?php


namespace Tests\Unit;


use Tests\TestCase;

class HelperTest extends TestCase
{

    /**
     * @test
     */
    public function testExtractDomain()
    {
        $this->assertEquals("website.com", extractDomain("https://website.com/"));
//        $this->assertEquals("website.com", extractDomain("website.com/"));
//        $this->assertEquals("website.com", extractDomain("website.com"));
    }

}
