<?php

namespace Kajona\System\Tests;


use Kajona\System\System\I18nTrait;

class I18nTraitTest extends Testbase
{
    use I18nTrait;

    public function testIsI18nValue()
    {
        $this->assertTrue($this->is18nValue(json_encode(["de" => "hallo"])));
        $this->assertTrue($this->is18nValue(json_encode(["de" => "hallo", "en" => "hello"])));
        $this->assertFalse($this->is18nValue("abc the cat"));
    }

    public function testToValueArray()
    {
        $this->assertEquals(["de" => "hallo", "en" => "hello", "es" => ""], $this->toI18nValueArray(json_encode(["de" => "hallo", "en" => "hello"])));
        $this->assertEquals(["de" => "hallo", "en" => "hello", "es" => ""], $this->toI18nValueArray(json_encode(["de" => "hallo", "en" => "hello"])));
        $this->assertEquals(["de" => "[]", "en" => "[]", "es" => "[]"], $this->toI18nValueArray(json_encode([])));

        $this->assertEquals(["de" => "{\"a\":1,\"b\":2}", "en" => "{\"a\":1,\"b\":2}", "es" => "{\"a\":1,\"b\":2}"], $this->toI18nValueArray(json_encode(["a" => 1, "b" => 2])));
    }

    public function testToValueString()
    {
        $this->assertEquals(json_encode(["de" => "hallo", "en" => "hello", "es" => "holla"]), $this->toI18nValueString(["test_de" => "hallo", "test_en" => "hello", "test_es" => "holla"], "test"));
        $this->assertEquals(json_encode(["de" => "hallo", "en" => "hello", "es" => ""]), $this->toI18nValueString(["test_de" => "hallo", "test_en" => "hello"], "test"));
        $this->assertEquals(json_encode(["de" => "", "en" => "", "es" => ""]), $this->toI18nValueString([], "test"));
    }

    public function testValueForString()
    {
        $this->setI18NEnabled(true);
        $this->assertEquals("hallo", $this->getI18nValueForString(json_encode(["de" => "hallo", "en" => "hello", "es" => ""]), "de"));
        $this->assertEquals("", $this->getI18nValueForString(json_encode(["de" => "hallo", "en" => "hello", "es" => ""]), "es"));
        $this->assertEquals("hello", $this->getI18nValueForString(json_encode(["de" => "hallo", "en" => "hello", "es" => ""]), "pt"));
        $this->assertEquals("hallo", $this->getI18nValueForString("hallo"), "de");
        $this->assertEquals("hallo", $this->getI18nValueForString("hallo"), "pt");

        $this->assertEquals("{\"A\":1,\"g\":3}", $this->getI18nValueForString(json_encode(["de" => json_encode(["A"=> 1, "g" => 3]), "en" => "hello", "es" => ""]), "de"));
        $this->setI18NEnabled(false);
    }


    public function testTo18nValueString()
    {
        $this->assertEquals(json_encode(["de" => "de", "en" => "en", "es" => ""]), $this->toI18nValueString(["a_de" => "de", "a_en" => "en"], "a"));
        $this->assertEquals(json_encode(["de" => "", "en" => "", "es" => ""]), $this->toI18nValueString(["a_de" => "de", "a_en" => "en"], "b"));

        $this->assertEquals(json_encode(["de" => "tt", "en" => "tt", "es" => "tt"]), $this->toI18nValueString(["a_de" => "de", "a_en" => "en"], "b", function($val) { return "tt"; }));
    }

    protected function getPossibleI18nLanguages()
    {
        return ["de", "en", "es"];
    }
}

