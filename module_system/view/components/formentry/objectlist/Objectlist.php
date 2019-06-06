<?php
/*"******************************************************************************************************
 *   (c) 2018 ARTEMEON                                                                                   *
 *       Published under the GNU LGPL v2.1                                                               *
 ********************************************************************************************************/

declare (strict_types = 1);

namespace Kajona\System\View\Components\Formentry\Objectlist;

use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Root;
use Kajona\System\System\VersionableInterface;
use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;

/**
 * General objectlist form entry which can display a list of models. It is possible to configure an add button where
 * the user can add new objects or you can also configure a search input which lets the user search objects through an
 * auto complete
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.0
 * @componentTemplate core/module_system/view/components/formentry/objectlist/template.twig
 */
class Objectlist extends FormentryComponentAbstract
{
    const MAX_VALUES = 500;
    /**
     * @var array
     */
    protected $items;

    /**
     * @var string
     */
    protected $addLink;

    /**
     * @var string
     */
    protected $endpointUrl;

    /**
     * @var array
     */
    protected $objectTypes;

    /** @var bool  */
    protected $showAddButton = true;

    /** @var bool  */
    protected $showDeleteAllButton = true;

    /** @var bool  */
    protected $showEditButton = false;

    /**
     * @var \Closure
     */
    protected $showDetailButton;

    /**
     * @param string $name
     * @param string $title
     * @param array $items
     */
    public function __construct($name, $title, array $items)
    {
        parent::__construct($name, $title);

        $this->items = $items;
    }

    /**
     * @param Root $item
     */
    public function addItem(Root $item)
    {
        $this->items[] = $item;
    }

    /**
     * @param string $addLink
     */
    public function setAddLink($addLink)
    {
        $this->addLink = $addLink;
    }

    /**
     * @param string $endpointUrl
     * @param array $objectTypes
     */
    public function setSearchInput($endpointUrl, array $objectTypes = null)
    {
        $this->endpointUrl = $endpointUrl;
        $this->objectTypes = $objectTypes;
    }

    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        $context = parent::buildContext();

        $rows = [];
        $ids = [];
        $count = 0;
        foreach ($this->items as $item) {
            /** @var $item Model */
            if ($item instanceof ModelInterface) {
                if (++$count > self::MAX_VALUES) {
                    continue;
                }
                $deleteAlt = Carrier::getInstance()->getObjLang()->getLang("commons_remove_assignment", "system");
                $attributes = [
                    "href" => "#",
                    "class" => "removeLink",
                    "onclick" => "V4skin.removeObjectListItem(this);return false;",
                ];
                $removeLink = Link::getLinkAdminManual($attributes, $deleteAlt, $deleteAlt, "icon_delete");

                $editLink = "";
                if ($this->isShowEditButton() && $item->rightEdit()) {
                    $editLinkText = Carrier::getInstance()->getObjLang()->getLang("commons_list_edit", "system");
                    $editLink = Link::getLinkAdminDialog($item->getArrModule("modul"), "edit", ["systemid" => $item->getSystemid(), "form_element" => $this->name], $editLinkText, $editLinkText, "icon_edit", $item->getStrDisplayName());
                }

                $detailLink = "";
                if ($this->showDetailButton instanceof \Closure) {
                    $link = call_user_func_array($this->showDetailButton, [$item]);
                    if (!empty($link)) {
                        $detailLink = $link;
                    }
                }

                $icon = is_array($item->getStrIcon()) ? $item->getStrIcon()[0] : $item->getStrIcon();

                $rows[] = [
                    'systemid' => $item->getSystemid(),
                    'displayName' => html_entity_decode($this->getDisplayName($item)),
                    'path'        => $this->getPathName($item),
                    'icon'        => AdminskinHelper::getAdminImage($icon),
                    'removeLink'  => $removeLink,
                    'editLink'    => $editLink,
                    'detailLink'  => $detailLink,

                ];
                $ids[] = $item->getSystemid();
            }
        }

        $deleteIcon = getImageAdmin("icon_delete", "", true);
        $removeAllAlt = Carrier::getInstance()->getObjLang()->getLang("commons_remove_all_assignment", "system");
        $attributes = [
            "href" => "#",
            "onclick" => "V4skin.removeAllObjectListItems('" . $this->name . "'); return false;",
        ];
        $toolkit = Carrier::getInstance()->getObjToolkit("admin");
        $removeAllLink = $toolkit->listButton(Link::getLinkAdminManual($attributes, $deleteIcon, $removeAllAlt));
        $searchInputPlaceholder = Lang::getInstance()->getLang("form_objectlist_add_search", "system", [$this->title]);

        $context["rows"] = $rows;
        if ($this->showAddButton) {
            $context["addLink"] = $this->addLink;
        }
        if ($this->showDeleteAllButton) {
            $context["removeAllLink"] = $removeAllLink;
        }
        $context["deleteIcon"] = json_encode($deleteIcon);
        $context["endpointUrl"] = $this->endpointUrl;
        $context["objectTypes"] = json_encode(implode(",", $this->objectTypes ?: []));
        $context["searchInputPlaceholder"] = $searchInputPlaceholder;
        $context["initval"] = implode(",", $ids);
        $context["maxValues"] = self::MAX_VALUES;

        return $context;
    }

    /**
     * Renders the display name for the object and, if possible, also the object type
     * This method is an implementation detail of the objectlist component please make it _not_ public static, this
     * behaviour might change in the future
     *
     * @param ModelInterface $model
     * @return string
     * @internal
     */
    private function getDisplayName(ModelInterface $model)
    {
        $name = "";

        if ($model instanceof VersionableInterface) {
            $name .= "[" . $model->getVersionRecordName() . "] ";
        }

        $name .= strip_tags($model->getStrDisplayName());

        return $name;
    }

    /**
     * This method is an implementation detail of the objectlist component please make it _not_ public static, this
     * behaviour might change in the future
     *
     * @param ModelInterface $objOneElement
     * @return string
     * @internal
     */
    private function getPathName(ModelInterface $objOneElement)
    {
        //fetch the process-path, at least two levels
        $arrParents = $objOneElement->getPathArray();

        // remove first two nodes
        if (count($arrParents) >= 2) {
            array_shift($arrParents);
            array_shift($arrParents);
        }

        //remove current element
        array_pop($arrParents);

        //Only return three levels
        $arrPath = array();
        for ($intI = 0; $intI < 3; $intI++) {
            $strPathId = array_pop($arrParents);
            if (!validateSystemid($strPathId)) {
                break;
            }

            $objObject = Objectfactory::getInstance()->getObject($strPathId);
            $arrPath[] = strip_tags(html_entity_decode($objObject->getStrDisplayName()));
        }

        if (count($arrPath) == 0) {
            return "";
        }

        $strPath = implode(" > ", array_reverse($arrPath));
        return $strPath;
    }

    /**
     * @return bool
     */
    public function isShowAddButton(): bool
    {
        return $this->showAddButton;
    }

    /**
     * @param bool $showAddButton
     */
    public function setShowAddButton(bool $showAddButton)
    {
        $this->showAddButton = $showAddButton;
    }

    /**
     * @return bool
     */
    public function isShowDeleteAllButton(): bool
    {
        return $this->showDeleteAllButton;
    }

    /**
     * @param bool $showDeleteAllButton
     */
    public function setShowDeleteAllButton(bool $showDeleteAllButton)
    {
        $this->showDeleteAllButton = $showDeleteAllButton;
    }

    /**
     * @return bool
     */
    public function isShowEditButton(): bool
    {
        return $this->showEditButton;
    }

    /**
     * @param bool $showEditButton
     */
    public function setShowEditButton(bool $showEditButton): void
    {
        $this->showEditButton = $showEditButton;
    }

    /**
     * @param \Closure|null $showDetailButton
     */
    public function setShowDetailButton(?\Closure $showDetailButton)
    {
        $this->showDetailButton = $showDetailButton;
    }
}
