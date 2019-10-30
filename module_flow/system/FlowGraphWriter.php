<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 ********************************************************************************************************/

namespace Kajona\Flow\System;

use Kajona\System\System\Lang;
use Kajona\System\System\Link;

/**
 * FlowGraphWriter
 *
 * @author christoph.kappestein@artemeon.de
 */
class FlowGraphWriter
{

    private static $bitIsIe = false;

    /**
     * Generates a mermaid graph definition of the flow object
     *
     * @param FlowConfig $objFlow
     * @param mixed $objHighlite
     * @param bool $bitPreview
     * @return string
     */
    public static function write(FlowConfig $objFlow, $objHighlite = null, $bitPreview = false)
    {
        //ugly hack to fetch old IE versions. used to inject some special options and css definitions
        $strUA = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
        if (stripos($strUA, 'MSIE') !== false || (stripos($strUA, 'Trident/7.0') !== false) || stripos($strUA, 'WOW64') !== false || stripos($strUA, 'Internet Explorer') !== false) {
            self::$bitIsIe = true;
        }
        //return self::writeCytoscape($objFlow, $objHighlite);
        return self::writeMermaid($objFlow, $objHighlite, $bitPreview);
    }

    /**
     * @param FlowConfig $objFlow
     * @param FlowStatus|FlowTransition $objHighlite
     * @return string
     */
    // private static function writeCytoscape(FlowConfig $objFlow, $objHighlite)
    // {
    //         $arrStatus = $objFlow->getArrStatus();

//         // sort status
    //         usort($arrStatus, function (FlowStatus $objA, FlowStatus $objB) {
    //             if ($objA->getIntIndex() == 1) {
    //                 return 1;
    //             }
    //             if ($objA->getIntIndex() == $objB->getIntIndex()) {
    //                 return 0;
    //             }
    //             return ($objA->getIntIndex() < $objB->getIntIndex()) ? -1 : 1;
    //         });

//         $arrNodes = [];
    //         foreach ($arrStatus as $objStatus) {
    //             $strBgColor = "#fff";
    //             $strBorder = "2";
    //             if ($objHighlite instanceof FlowStatus && $objHighlite->getSystemid() == $objStatus->getSystemid()) {
    //                 $strBgColor = "#eee";
    //                 $strBorder = "4";
    //             } elseif ($objHighlite instanceof FlowTransition && $objHighlite->getParentStatus()->getSystemid() == $objStatus->getSystemid()) {
    //                 $strBgColor = "#eee";
    //                 $strBorder = "4";
    //             }

//             $arrNodes[] = [
    //                 'data' => [
    //                     'id' => $objStatus->getSystemid(),
    //                     'name' => $objStatus->getStrName(),
    //                     'color' => $objStatus->getStrIconColor(),
    //                     'bgcolor' => $strBgColor,
    //                     'border' => $strBorder,
    //                 ],
    //                 'grabbable' => false
    //             ];
    //         }

//         $arrTrans = [];

//         foreach ($arrStatus as $objStatus) {
    //             /** @var FlowStatus $objStatus */
    //             $arrTransitions = $objStatus->getArrTransitions();
    //             foreach ($arrTransitions as $objTransition) {
    //                 if (!$objTransition->isVisible()) {
    //                     continue;
    //                 }

//                 /** @var $objTransition FlowTransition */
    //                 $objParentStatus = $objTransition->getParentStatus();
    //                 $objTargetStatus = $objTransition->getTargetStatus();

//                 $arrTrans[] = [
    //                     'data' => [
    //                         'id' => $objTransition->getSystemid(),
    //                         'source' => $objParentStatus->getSystemid(),
    //                         'target' => $objTargetStatus->getSystemid(),
    //                         //'label' => "A: ".count($objTransition->getArrActions())." C: ".count($objTransition->getArrConditions()),
    //                     ]
    //                 ];
    //             }
    //         }

//         $strNodes = json_encode($arrNodes);
    //         $strTransitions = json_encode($arrTrans);

//         $strTmpSystemId = generateSystemid();
    //         $strLinkTransition = Link::getLinkAdminHref("flow", "listTransition", "&systemid=" . $strTmpSystemId);

//         $strLayout = json_encode([
    //             'name' => 'dagre',
    //             'fit' => false,
    //         ]);

//         /*
    //         $strLayout = json_encode([
    //             'name' => 'grid',
    //             'rows' => 4
    //         ]);
    //         */

//         return <<<HTML
    // <div style='width: 90%; height: 600px; overflow-y: scroll;'><div id='flow-graph' style='width:100%;height:1000px;border:0px solid #999;'></div></div>
    // <script type="text/javascript">
    //     require(['cytoscape', 'cytoscape-dagre', 'dagre'], function(cytoscape, cd, dagre){

//         cd(cytoscape, dagre);

//         var cy = cytoscape({
    //           container: document.getElementById('flow-graph'),
    //           style: [{
    //             selector: 'node',
    //             style: {
    //               'font-size': '13',
    //               'font-family': 'Open Sans, Helvetica Neue, Helvetica, Arial, sans-serif',
    //               'label': 'data(name)',
    //               'text-valign': 'center',
    //               'shape': 'rectangle',
    //               'width': 'label',
    //               'padding' : '10',
    //               'height': 'label',
    //               'border-width': 'data(border)',
    //               'border-style': 'solid',
    //               'border-color': 'data(color)',
    //               'background-color': 'data(bgcolor)'
    //             }
    //            }, {
    //             selector: 'edge',
    //             style: {
    //               'width': 2,
    //               'target-arrow-shape': 'triangle',
    //               'line-color': '#525252',
    //               'target-arrow-color': '#525252',
    //               'curve-style': 'bezier',
    //               'control-point-step-size': 40,
    //               'font-size' : '10',
    //               'text-margin-x' : '0',
    //               'text-margin-y' : '0',
    //               'label': 'data(label)'
    //             }
    //           }],
    //           elements: {
    //             nodes: {$strNodes},
    //             edges: {$strTransitions}
    //           },
    //           layout: {$strLayout},
    //           zoom: 1,
    //           pan: { x: ($('#flow-graph').innerWidth() / 2) - 90, y: 40 },
    //           boxSelectionEnabled: false,
    //           autounselectify: true,
    //           zoomingEnabled: true,
    //           userZoomingEnabled: false,
    //           panningEnabled: true,
    //           userPanningEnabled: true
    //         });

//         /*
    //         cy.$('node').on('click', function(e){
    //           var ele = e.target;
    //           location.href = "{$strLinkTransition}".replace('{$strTmpSystemId}', ele.id());
    //         });
    //         */
    //     });
    // </script>
    // HTML;
    // }

    private static function writeMermaid(FlowConfig $objFlow, $objHighlite = null, $bitPreview = false)
    {
        $arrStatus = $objFlow->getArrStatus();

        // sort status
        usort($arrStatus, function (FlowStatus $objA, FlowStatus $objB) {
            if ($objA->getIntIndex() == 1) {
                return 1;
            }
            if ($objA->getIntIndex() == $objB->getIntIndex()) {
                return 0;
            }
            return ($objA->getIntIndex() < $objB->getIntIndex()) ? -1 : 1;
        });

        $arrList = array("graph TD;");
        foreach ($arrStatus as $objStatus) {
            /** @var FlowStatus $objStatus */
            $arrTransitions = $objStatus->getArrTransitions();
            foreach ($arrTransitions as $objTransition) {
                if (!$objTransition->isVisible()) {
                    continue; //TODO: add option to show / hide invisivle edges
                }

                /** @var $objTransition FlowTransition */
                $objTargetStatus = $objTransition->getTargetStatus();
                if ($objTargetStatus instanceof FlowStatus) {
                    $strLineStart = $objTransition->isVisible() ? "--" : "-.";
                    $strLineEnd = $objTransition->isVisible() ? "--" : ".-";
                    if (self::$bitIsIe) {
                        //IE doesn't render html labels, so no need to replace them later on --> regular edge
                        $arrList[] = $objStatus->getStrSystemid() . "[" . $objStatus->getStrDisplayName() . "]{$strLineEnd}>" . $objTargetStatus->getSystemid() . "[" . $objTargetStatus->getStrDisplayName() . "];";
                    } else {
                        $arrList[] = $objStatus->getStrSystemid() . "[" . $objStatus->getStrDisplayName() . "]{$strLineStart} <span data-" . $objTransition->getSystemid() . ">______</span> {$strLineEnd}>" . $objTargetStatus->getSystemid() . "[" . $objTargetStatus->getStrDisplayName() . "];";
                    }
                }
            }
        }

        // get all status which are used in the flow
        $arrUsed = [];
        self::walkFlow($objFlow->getStatusByIndex(0), $arrUsed);

        $strHighliteId = null;
        $strHighliteColor = null;
        if ($objHighlite instanceof FlowStatus) {
            $strHighliteId = $objHighlite->getSystemid();
            $strHighliteColor = $objHighlite->getStrIconColor();
        } elseif ($objHighlite instanceof FlowTransition) {
            $strHighliteId = $objHighlite->getParentStatus()->getSystemid();
            $strHighliteColor = $objHighlite->getParentStatus()->getStrIconColor();
        }

        foreach ($arrUsed as $strSystemId => $objStatus) {
            if ($strHighliteId !== null && $strSystemId == $strHighliteId) {
                $arrList["style " . $strHighliteId] = "style {$strHighliteId} fill:#f9f9f9,stroke:{$strHighliteColor},stroke-width:3px;";
            } else {
                $arrList["style " . $strSystemId] = "style {$strSystemId} fill:#f9f9f9,stroke:{$objStatus->getStrIconColor()},stroke-width:1px;";
            }
        }

        $strGraph = implode("\n", $arrList);

        $strTmpSystemId = generateSystemid();

        if ($bitPreview) {
            $strLinkTransition = "";
            $strLinkTransitionAction = "";
            $strLinkTransitionCondition = "";
        } else {
            $strLinkTransition = Link::getLinkAdminHref("flow", "listTransition", "&systemid=" . $strTmpSystemId);
            $strLinkTransitionAction = Link::getLinkAdminHref("flow", "listTransitionAction", "&systemid=" . $strTmpSystemId);
            $strLinkTransitionCondition = Link::getLinkAdminHref("flow", "listTransitionCondition", "&systemid=" . $strTmpSystemId);
        }

        $strAction = Lang::getInstance()->getLang("modul_titel_transition_action", "flow");
        $strCondition = Lang::getInstance()->getLang("modul_titel_transition_condition", "flow");

        $strHeight = "";
        if (self::$bitIsIe) {
            $strHeight = "height: 900px;";
        }

        $isIe = self::$bitIsIe ? 'true' : 'false';

        return <<<HTML
<div id='flow-graph' class='mermaid' style='color:#fff; {$strHeight} '>{$strGraph}</div>
<script type="text/javascript">
       var mm = new window.Mermaidwrapper('{$strTmpSystemId}', '{$strLinkTransition}', '{$strLinkTransitionAction}', '{$strLinkTransitionCondition}', '{$strAction}', '{$strCondition}', {$isIe})
       mm.renderGraph();
</script>
HTML;
    }

    /**
     * Helper method to walk through the flow and collect every status which is connected with the "In Bearbeitung"
     * status
     *
     * @param FlowStatus $status
     * @param array $list
     */
    private static function walkFlow(FlowStatus $status, array &$list)
    {
        if (isset($list[$status->getSystemid()])) {
            // we have already visited this status
            return;
        }

        $list[$status->getSystemid()] = $status;

        // get all connected status
        $arrTransitions = $status->getArrTransitions();
        foreach ($arrTransitions as $objTransition) {
            if (!$objTransition->isVisible()) {
                continue;
            }

            /** @var $objTransition FlowTransition */
            $objTargetStatus = $objTransition->getTargetStatus();
            if ($objTargetStatus instanceof FlowStatus) {
                self::walkFlow($objTargetStatus, $list);
            }
        }
    }
}
