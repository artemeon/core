<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/
declare (strict_types = 1);

namespace Kajona\Tinyurl\Admin;

use Kajona\System\Admin\AdminController;
use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\AdminInterface;
use Kajona\System\Admin\Formentries\FormentryHidden;
use Kajona\System\System\StringUtil;

/**
 * Admin-Part of the TinyUrl.

 * @package module_tinyurl
 * @author andrii.konoval@artemeon.de
 *
 * @module tinyurl
 * @moduleId _tinyurl_module_id_
 */
class TinyUrlController extends AdminController implements AdminInterface
{
    /**
     * @inject tinyurl_manager
     */
    protected $tinyurlManager;

    /**
     * @return false|string
     * @permissions loggedin
     * @responseType json
     */
    public function actionGetShortUrl()
    {
        return ["url" => $this->tinyurlManager->getShortUrl($this->getParam('url'))];
    }

    /**
     * Load by short URL
     * @permissions loggedin
     */
    public function actionLoadUrl()
    {
        $strUrlID = $this->getParam('systemid');
        $strUrl = urldecode($this->tinyurlManager->loadUrl($strUrlID));
        if (!empty($strUrl)) {
            $strReturn = "";
            //get string part with parameters
            if (StringUtil::indexOf($strUrl, "?") !== false) {
                $urlParts = explode("?", $strUrl);
            } else {
                $intDelimiterPos = StringUtil::indexOf($strUrl, "&");
                $urlParts[0] = StringUtil::substring($strUrl, 0, $intDelimiterPos);
                $urlParts[1] = StringUtil::substring($strUrl, $intDelimiterPos + 1);
            }

            $formParams = $this->paramStrToFormParamsArray($urlParts[1]);
            $objGenerator = new AdminFormgenerator("linkredirect", null);

            foreach ($formParams as $strParamName => $strParamValue) {
                $strParamValue = (is_array($strParamValue)) ? implode(',', $strParamValue) : $strParamValue;
                $objGenerator->addField(new FormentryHidden("", $strParamName))->setStrValue($strParamValue);
            }

            $strReturn .= $this->objToolkit->warningBox($this->getLang("url_loading") . "<div class='loadingContainer'></div>", "alert-info");
            $strReturn .= $objGenerator->renderForm($urlParts[0], AdminFormgenerator::GROUP_TYPE_HIDDEN);
            $strReturn .= "<script type='text/javascript'>Forms.defaultOnSubmit(document.forms['linkredirect']);</script>";
            return $strReturn;
        }

        return $this->getLang('no_data', 'tinyurl');
    }

    /**
     * Converts parameters from get-request string to the array of params suitable for generating form.
     *
     * @param string $strParams
     * @return array
     */
    private function paramStrToFormParamsArray($strParams)
    {
        $formParams = [];
        $urlParams = explode("&", $strParams);

        foreach ($urlParams as $strOneParam) {
            if (trim($strOneParam) == "") {
                continue;
            }
            $arrEntry = explode("=", $strOneParam);
            // process case when we have such string of parameters
            // "riskanalysistemplatefilter_riskcategory[]=0&riskanalysistemplatefilter_riskcategory[]=3&riskanalysistemplatefilter_riskcategory[]=6"
            // it should be converted to "$formParams['riskanalysistemplatefilter_riskcategory[]'] = '[0,3,6]'"
            if (isset($formParams[$arrEntry[0]])) {
                if (is_array($formParams[$arrEntry[0]])) {
                    $formParams[$arrEntry[0]][] = is_numeric($arrEntry[1]) ? (int) $arrEntry[1] : $arrEntry[1];
                } else {
                    if (is_numeric($arrEntry[1]) && is_numeric($formParams[$arrEntry[0]])) {
                        $formParams[$arrEntry[0]] = [(int) $formParams[$arrEntry[0]], (int) $arrEntry[1]];
                    } else {
                        $formParams[$arrEntry[0]] = [$formParams[$arrEntry[0]], $arrEntry[1]];
                    }

                }
            } else {
                $formParams[$arrEntry[0]] = $arrEntry[1];
            }
        }

        return $formParams;
    }

}
