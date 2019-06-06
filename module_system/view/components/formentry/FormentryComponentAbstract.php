<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/

declare(strict_types = 1);

namespace Kajona\System\View\Components\Formentry;

use Kajona\System\View\Components\AbstractComponent;

/**
 * Base class for all form entry components. It provides the base name and title attributes which each form entry must
 * have, it does not contain a value property. You should overwrite the `buildContext` method in each form entry to
 * extend the context for your form entry
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.0
 */
abstract class FormentryComponentAbstract extends AbstractComponent
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var bool
     */
    protected $readOnly = false;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $opener;

    /**
     * @param string $name
     * @param string $title
     */
    public function __construct($name, $title)
    {
        parent::__construct();

        $this->name = $name;
        $this->title = $title;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @param bool $readOnly
     */
    public function setReadOnly(bool $readOnly)
    {
        $this->readOnly = $readOnly;
    }

    /**
     * Sets an additional css class
     *
     * @param string $class
     */
    public function setClass(string $class)
    {
        $this->class = $class;
    }

    /**
     * Method to set additional data attributes on an element
     *
     * @param string $key
     * @param mixed $value
     */
    public function setData(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Method to set additional data attributes on an element
     *
     * @param $data
     */
    public function setDataArray($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $opener
     */
    public function setOpener(string $opener)
    {
        $this->opener = $opener;
    }

    /**
     * @inheritdoc
     */
    public function renderComponent(): string
    {
        return $this->renderTemplate($this->buildContext());
    }

    /**
     * Each form entry should overwrite this method to provide custom values specific to the form entry
     *
     * @return array
     */
    protected function buildContext()
    {
        return [
            "componentId" => generateSystemid(), // every components gets a unique id
            "name" => $this->name,
            "title" => $this->title,
            "readOnly" => $this->readOnly,
            "class" => $this->class,
            "data" => $this->data,
            "opener" => $this->opener,
        ];
    }
}
