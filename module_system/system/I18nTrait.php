<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types = 1);

namespace Kajona\System\System;

/**
 * Trait to keep common logic for i18n based content.
 * Currently providing helpers to explode / implode values
 * and to detect i18n capabilities
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 */
trait I18nTrait
{
    /** @var array */
    private static $availableLangs = [];

    /** @var bool */
    private $i18NEnabled = false;

    /**
     * Evaluates if a value represents an i18n value
     * @param $value
     * @return bool
     */
    public function is18nValue($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $val = json_decode($value, true);
        return $val !== null;
    }

    /**
     * Converts the passed value (i18n or plain string) to an i18n based array.
     * @param string $i18nString
     * @return array
     */
    protected function toI18nValueArray(string $i18nString = null): array
    {
        $langs = $this->getPossibleI18nLanguages();
        $val = json_decode($i18nString ?? "", true);
        //we need to check is we have "another" json array right here
        if (is_array($val)) {
            //if we don't have at least a single lang key, handle it as a plain value
            $keys = array_keys($val);
            if (count(array_diff($langs, $keys)) == count($langs)) {
                $val = null;
            } else {
                //may reset the internal decoded array to a json string
                foreach ($val as $key => $value) {
                    if (is_array($value)) {
                        $val[$key] = json_encode($value);
                    }
                }
            }
        }

        $return = [];

        foreach ($langs as $lang) {
            $return[$lang] = isset($val[$lang]) ? $val[$lang] : ($val !== null ? "" : $i18nString);
        }

        return $return;
    }

    /**
     * Converts post-values back to a storable, single-field string
     * @param array $params
     * @param string $key
     * @param callable|null $transformCallback
     * @return string
     */
    protected function toI18nValueString(array $params, string $key, callable $transformCallback = null): string
    {
        $return = [];
        foreach ($this->getPossibleI18nLanguages() as $lang) {
            $return[$lang] = $params["{$key}_{$lang}"] ?? "";
            if ($transformCallback !== null) {
                $return[$lang] = $transformCallback($return[$lang]);
            }
        }
        return json_encode($return);
    }

    /**
     * Returns the list of languages currently available
     * @return array
     */
    protected function getPossibleI18nLanguages(): array
    {
        if (self::$availableLangs) {
            return self::$availableLangs;
        }

        return self::$availableLangs = array_map(function (LanguagesLanguage $lang) {
            return $lang->getStrName();
        }, LanguagesLanguage::getObjectListFiltered());
    }

    /**
     * Fetches the current value (based on the backend lang or the passed lang) from the
     * value string
     * @param string $value json-string
     * @param string|null $lang
     * @param bool $forceI18n
     * @return string
     * @throws Exception
     */
    protected function getI18nValueForString(?string $value, string $lang = null, $forceI18n = false): string
    {
        $value = $value ?? "";
        if (!$forceI18n && !$this->i18NEnabled) {
            return $value;
        }
        $lang = $lang ?? $this->getCurrentI18nLanguage();
        $fallback = Lang::getInstance()->getStrFallbackLanguage();
        $arr = $this->toI18nValueArray($value);
        if (isset($arr[$lang])) {
            return $arr[$lang];
        } elseif (isset($arr[$fallback])) {
            return $arr[$fallback];
        } else {
            // in this case we have no options
            return reset($arr);
        }
    }

    /**
     * Return the key of the current language
     * @return string
     * @throws Exception
     */
    protected function getCurrentI18nLanguage(): string
    {
        $lang = Session::getInstance()->getAdminLanguage();
        $fallback = Lang::getInstance()->getStrFallbackLanguage();
        $available = $this->getPossibleI18nLanguages();

        if (in_array($lang, $available)) {
            return $lang;
        } else {
            return $fallback;
        }
    }

    /**
     * @return bool
     */
    public function getI18NEnabled(): bool
    {
        return $this->i18NEnabled;
    }

    /**
     * @param bool $i18NEnabled
     */
    public function setI18NEnabled(bool $i18NEnabled): void
    {
        $this->i18NEnabled = $i18NEnabled;
    }


}
