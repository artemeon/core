<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                              *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * Class managing access to lang-files
 *
 * @package module_system
 * @author sidler@mulchprod.de
 */
class Lang
{

    /**
     * This is the default language.
     *
     * @var string
     */
    private $strLanguage = "";

    /**
     * Identifier of the fallback-language, taken into account if loading an entry using the current language failed.
     *
     * @var string
     */
    private $strFallbackLanguage = "en";

    /**
     * The commons-name indicates the fake-module-name of lang-files bundled for common usage (in order to
     * reduce duplicate lang-entries).
     *
     * @var string
     */
    private $strCommonsName = "commons";
    private $arrTexts;

    /**
     * Used to keep placeholders loaded from the fallback lang-file.
     * Only used, if the file itself exists in the target-language but misses a placeholder!
     *
     * @var array
     */
    private $arrFallbackTextEntrys = [];


    private static $objLang = null;

    private $bitSaveToCache = false;

    /**
     * Constructor, singleton
     */
    private function __construct()
    {
        //load texts from session
        $this->arrTexts = CacheManager::getInstance()->getValue(__CLASS__."textSessionCache");
        if ($this->arrTexts === false) {
            $this->arrTexts = [];
        }

        $this->arrFallbackTextEntrys = CacheManager::getInstance()->getValue(__CLASS__."textSessionFallbackCache");
        if ($this->arrFallbackTextEntrys === false) {
            $this->arrFallbackTextEntrys = [];
        }
    }

    public function __destruct()
    {
        //save texts to session
        if ($this->bitSaveToCache) {
            CacheManager::getInstance()->addValue(__CLASS__."textSessionCache", $this->arrTexts, Config::getInstance()->getConfig("textcachetime"));
            CacheManager::getInstance()->addValue(__CLASS__."textSessionFallbackCache", $this->arrFallbackTextEntrys, Config::getInstance()->getConfig("textcachetime"));
        }
    }

    /**
     * Singleton
     */
    private function __clone()
    {
    }

    /**
     * Returning an instance of Lang
     *
     * @return Lang
     */
    public static function getInstance()
    {
        if (self::$objLang == null) {
            self::$objLang = new Lang();
        }

        return self::$objLang;
    }

    /**
     * Returning the searched text-entry.
     * If you have placeholders in the property (like {1}, {2}, you may replace them with the values of the third param.
     *
     * @param string $strText
     * @param string $strModule the module does not contain the module_ prefix
     * @param array $arrParameters an array of variables which are embedded into the string
     * @param string $language optional the needed language otherwise we fallback to the global user language
     * @return string
     */
    public function getLang($strText, $strModule, $arrParameters = [], $language = null)
    {
        if ($language === null) {
            $language = $this->strLanguage;
        }

        //Did we already load this text?
        if (!isset($this->arrTexts[$language][$strModule])) {
            $this->loadText($strModule, $language);
        }

        //Searching for the text
        if (isset($this->arrTexts[$language][$strModule][$strText])) {
            $strReturn = $this->arrTexts[$language][$strModule][$strText];
        } else {
            $strReturn = "!".$strText."!";
        }

        return $this->replaceParams($strReturn, $arrParameters);
    }

    /**
     * Returns all properties for a specific module , and if wanted a specific language
     *
     * @param string $strModule
     * @param string $strLanguage
     * @return array
     */
    public function getProperties($strModule , $strLanguage = null)
    {
        if($strLanguage===null){
            $strLanguage = $this->strLanguage ;
        }
        //Did we already load this text?
        if (!isset($this->arrTexts[$strLanguage][$strModule])) {
            $this->loadText($strModule , $strLanguage);
        }
        if (isset($this->arrTexts[$strLanguage][$strModule])) {
            return $this->arrTexts[$strLanguage][$strModule];
        } else {
            return [];
        }

    }

    /**
     *
     * Internal helper to fill parametrized properties.
     *
     * @param $strProperty
     * @param $arrParameters
     *
     * @return mixed
     */
    public function replaceParams($strProperty, $arrParameters)
    {
        foreach ($arrParameters as $intKey => $strParameter) {
            $strProperty = StringUtil::replace("{".$intKey."}", $strParameter, $strProperty);
        }

        return $strProperty;
    }


    /** Removes prefixes (str, int, float etc.) from the given properties and returns a lower case propertyname
     *
     * @param $strPropertyName
     *
     * @return string
     */
    public function propertyWithoutPrefix($strPropertyName)
    {
        $strStart = StringUtil::substring($strPropertyName, 0, 3);
        if (in_array($strStart, ["int", "bit", "str", "arr", "obj"])) {
            $strPropertyName = StringUtil::toLowerCase(StringUtil::substring($strPropertyName, 3));
        }

        $strStart = StringUtil::substring($strPropertyName, 0, 4);
        if (in_array($strStart, ["long"])) {
            $strPropertyName = StringUtil::toLowerCase(StringUtil::substring($strPropertyName, 4));
        }

        $strStart = StringUtil::substring($strPropertyName, 0, 5);
        if (in_array($strStart, ["float"])) {
            $strPropertyName = StringUtil::toLowerCase(StringUtil::substring($strPropertyName, 5));
        }

        return $strPropertyName;
    }


    /**
     * Adds underscores ("_") to the given string for each uppercase char found.
     * The returned string is lowercase.
     *
     * @param $strText
     *
     * @return string
     */
    public function stringToPlaceholder($strText)
    {
        $strReturn = "";
        $strLastChar = "";

        for ($i = 0; $i < StringUtil::length($strText); $i++) {
            $strChar = StringUtil::substring($strText, $i, 1);
            $strCharLower = StringUtil::toLowerCase($strChar);

            if ($i > 0 && $strChar != $strCharLower && $strLastChar != "_") {
                $strReturn .= "_".$strCharLower;
            } else {
                $strReturn .= $strCharLower;
            }

            $strLastChar = $strChar;
        }

        return $strReturn;
    }


    /**
     * Loading texts from textfiles
     *
     * @param string $strModule
     * @param string $language
     * @return void
     */
    private function loadText($strModule, $language = null)
    {
        if ($language === null) {
            $language = $this->strLanguage;
        }

        $arrCommons = Resourceloader::getInstance()->getLanguageFiles("module_".$this->strCommonsName);
        $arrModuleFiles = Resourceloader::getInstance()->getLanguageFiles("module_".$strModule);

        //following steps:
        // 1. commons fallback language
        foreach (array_keys($arrCommons, "lang_".$this->strCommonsName."_".$this->strFallbackLanguage.".php") as $strPath) {
            $this->loadAndMergeTextfile($strModule, $strPath, $language, $this->arrTexts);
        }

        // 2. entries fallback language
        foreach ($arrModuleFiles as $strPath => $strFilename) {
            $arrFilename = explode("_", StringUtil::substring($strFilename, 0, -4));
            if (end($arrFilename) == $this->strFallbackLanguage) {
                $this->loadAndMergeTextfile($strModule, $strPath, $language, $this->arrTexts);
            }
        }

        // 3. commons current language
        foreach (array_keys($arrCommons, "lang_".$this->strCommonsName."_".$language.".php") as $strPath) {
            $this->loadAndMergeTextfile($strModule, $strPath, $language, $this->arrTexts);
        }

        // 4. entries current language
        foreach ($arrModuleFiles as $strPath => $strFilename) {
            $arrFilename = explode("_", StringUtil::substring($strFilename, 0, -4));
            if (end($arrFilename) == $language) {
                $this->loadAndMergeTextfile($strModule, $strPath, $language, $this->arrTexts);
            }
        }
    }

    /**
     * Includes the file from the filesystem and merges the contents to the passed array.
     * NOTE: this array is used as a reference!!!
     *
     * @param string $strModule
     * @param string $strFilename
     * @param string $strLanguage
     * @param array $arrTargetArray
     */
    private function loadAndMergeTextfile($strModule, $strFilename, $strLanguage, &$arrTargetArray)
    {
        $lang = [];
        $this->bitSaveToCache = true;

        include $strFilename;

        if (!isset($arrTargetArray[$strLanguage])) {
            $arrTargetArray[$strLanguage] = [];
        }

        if (isset($arrTargetArray[$strLanguage][$strModule])) {
            $arrTargetArray[$strLanguage][$strModule] = array_merge($arrTargetArray[$strLanguage][$strModule], $lang);
        } else {
            $arrTargetArray[$strLanguage][$strModule] = $lang;
        }
    }


    /**
     * Sets the language to load textfiles
     *
     * @param string $strLanguage
     */
    public function setStrTextLanguage($strLanguage)
    {
        if ($strLanguage == "") {
            return;
        }

        $this->strLanguage = $strLanguage;
    }

    /**
     * Gets the current language set to the Lang
     *
     * @return string
     */
    public function getStrTextLanguage()
    {
        return $this->strLanguage;
    }

    /**
     * @return string
     */
    public function getStrFallbackLanguage()
    {
        return $this->strFallbackLanguage;
    }
}
