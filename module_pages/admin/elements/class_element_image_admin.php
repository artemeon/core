<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2015 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

/**
 * Class to handle the admin-part of the element
 *
 * @package module_pages
 * @author sidler@mulchprod.de
 *
 * @targetTable element_image.content_id
 */
class class_element_image_admin extends class_element_admin implements interface_admin_element {

    /**
     * @var string
     * @tableColumn element_image.image_title
     * @tableColumnDatatype char254
     *
     * @fieldType text
     * @fieldLabel commons_title
     *
     * @addSearchIndex
     */
    private $strTitle = "";

    /**
     * @var string
     * @tableColumn element_image.image_link
     * @tableColumnDatatype char254
     *
     * @fieldType page
     * @fieldLabel image_link
     *
     * @addSearchIndex
     */
    private $strLink = "";

    /**
     * @var string
     * @tableColumn element_image.image_image
     * @tableColumnDatatype char254
     *
     * @fieldType image
     * @fieldLabel commons_image
     *
     * @elementContentTitle
     *
     * @addSearchIndex
     */
    private $strImage = "";

    /**
     * @var string
     * @tableColumn element_image.image_x
     * @tableColumnDatatype int
     *
     * @fieldType text
     * @fieldLabel image_x
     * @fieldHidden
     */
    private $strImageX = 0;

    /**
     * @var string
     * @tableColumn element_image.image_y
     * @tableColumnDatatype int
     *
     * @fieldType text
     * @fieldLabel image_y
     * @fieldHidden
     */
    private $strImageY = 0;

    /**
     * @var string
     * @tableColumn element_image.image_template
     * @tableColumnDatatype char254
     *
     * @fieldType template
     * @fieldLabel template
     *
     * @fieldTemplateDir /element_image
     */
    private $strTemplate = "";



    /**
     * @param string $strImage
     */
    public function setStrImage($strImage) {
        $this->strImage = $strImage;
    }

    /**
     * @return string
     */
    public function getStrImage() {
        return $this->strImage;
    }

    /**
     * @param string $strImageX
     */
    public function setStrImageX($strImageX) {
        $this->strImageX = $strImageX;
    }

    /**
     * @return string
     */
    public function getStrImageX() {
        return $this->strImageX;
    }

    /**
     * @param string $strImageY
     */
    public function setStrImageY($strImageY) {
        $this->strImageY = $strImageY;
    }

    /**
     * @return string
     */
    public function getStrImageY() {
        return $this->strImageY;
    }

    /**
     * @param string $strLink
     */
    public function setStrLink($strLink) {
        $this->strLink = $strLink;
    }

    /**
     * @return string
     */
    public function getStrLink() {
        return $this->strLink;
    }

    /**
     * @param string $strTemplate
     */
    public function setStrTemplate($strTemplate) {
        $this->strTemplate = $strTemplate;
    }

    /**
     * @return string
     */
    public function getStrTemplate() {
        return $this->strTemplate;
    }

    /**
     * @param string $strTitle
     */
    public function setStrTitle($strTitle) {
        $this->strTitle = $strTitle;
    }

    /**
     * @return string
     */
    public function getStrTitle() {
        return $this->strTitle;
    }





}
