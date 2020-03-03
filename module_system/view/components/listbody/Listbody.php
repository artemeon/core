<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/

declare(strict_types = 1);

namespace Kajona\System\View\Components\Listbody;

use Kajona\System\View\Components\AbstractComponent;

/**
 * Temporary component for a list-body.
 * Will be removed by a "real" list component.
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/listbody/template.twig
 */
class Listbody extends AbstractComponent
{

    private $id = "";
    private $name = "";
    private $icon = "";
    private $actions = "";
    private $additionalInfo = "";
    private $description = "";
    private $checkbox = false;
    private $cssAddon = "";
    private $deleted = "";
    private $path = "";

    private $dataAttributes = [];

    /**
     * Listbody constructor.
     * @param string $id
     * @param string $name
     * @param string $icon
     * @param string $actions
     */
    public function __construct(string $id, string $name, string $icon, string $actions)
    {
        parent::__construct();
        $this->id = $id;
        $this->name = $name;
        $this->icon = $icon;
        $this->actions = $actions;
    }


    /**
     * @inheritdoc
     */
    public function renderComponent(): string
    {

        $data = [];
        $data["listitemid"] = $this->id;
        $data["image"] = $this->icon;
        $data["title"] = $this->name;
        $data["center"] = $this->additionalInfo;
        $data["actions"] = $this->actions;
        $data["description"] = $this->description;
        $data["cssaddon"] = $this->cssAddon;
        $data["deleted"] = $this->deleted;
        $data["checkbox"] = $this->checkbox;
        $data["path"] = $this->path;
        $data["data"] = $this->dataAttributes;

        return $this->renderTemplate($data);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Listbody
     */
    public function setId(string $id): Listbody
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Listbody
     */
    public function setName(string $name): Listbody
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return Listbody
     */
    public function setIcon(string $icon): Listbody
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return string
     */
    public function getActions(): string
    {
        return $this->actions;
    }

    /**
     * @param string $actions
     * @return Listbody
     */
    public function setActions(string $actions): Listbody
    {
        $this->actions = $actions;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalInfo(): string
    {
        return $this->additionalInfo;
    }

    /**
     * @param string $additionalInfo
     * @return Listbody
     */
    public function setAdditionalInfo(string $additionalInfo): Listbody
    {
        $this->additionalInfo = $additionalInfo;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Listbody
     */
    public function setDescription(string $description): Listbody
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCheckbox(): bool
    {
        return $this->checkbox;
    }

    /**
     * @param bool $checkbox
     * @return Listbody
     */
    public function setCheckbox(bool $checkbox): Listbody
    {
        $this->checkbox = $checkbox;
        return $this;
    }

    /**
     * @return string
     */
    public function getCssAddon(): string
    {
        return $this->cssAddon;
    }

    /**
     * @param string $cssAddon
     * @return Listbody
     */
    public function setCssAddon(string $cssAddon): Listbody
    {
        $this->cssAddon = $cssAddon;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeleted(): string
    {
        return $this->deleted;
    }

    /**
     * @param string $deleted
     * @return Listbody
     */
    public function setDeleted(string $deleted): Listbody
    {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Method to set additional data attributes on an element
     *
     * @param string $key
     * @param mixed $value
     *
     * @return Listbody
     */
    public function setData(string $key, $value)
    {
        $this->dataAttributes[$key] = $value;
        return $this;
    }


}
