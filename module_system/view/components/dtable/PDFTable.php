<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/

declare(strict_types = 1);

namespace Kajona\System\View\Components\Dtable;

use Kajona\System\View\Components\AbstractComponent;
use Kajona\System\View\Components\Dtable\Model\DTable;

/**
 * Component which renders a HTML table which is optimized for the PDF HTML writer
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/dtable/pdf.twig
 */
class PDFTable extends AbstractComponent
{
    /**
     * @var DTable
     */
    private $dataTable;

    /**
     * @var string
     */
    private $oddBgColor;

    /**
     * @var string
     */
    private $oddFontColor;

    /**
     * @var string
     */
    private $evenBgColor;

    /**
     * @var string
     */
    private $evenFontColor;

    /**
     * @var array
     */
    private $columnWidths;

    /**
     * @var string
     */
    private $borderWidth = '0.5px';

    private $cellPadding = 4;
    private $cellSpacing = 4;

    /**
     * @param DTable $dataTable
     */
    public function __construct(DTable $dataTable)
    {
        parent::__construct();

        $this->dataTable = $dataTable;
    }

    public function setDefaultStripingEnabled()
    {
        $this->setCellSpacing(0);
        $this->setBorderWidth('0');

        $this->setOdd('#ffffff', '#000000');
        $this->setEven('#f2f2f2', '#000000');

    }

    /**
     * @return DTable
     */
    public function getDataTable(): DTable
    {
        return $this->dataTable;
    }

    /**
     * @param DTable $dataTable
     */
    public function setDataTable(DTable $dataTable)
    {
        $this->dataTable = $dataTable;
    }

    /**
     * @return string
     */
    public function getOddBgColor(): ?string
    {
        return $this->oddBgColor;
    }

    /**
     * @return string
     */
    public function getOddFontColor(): ?string
    {
        return $this->oddFontColor;
    }

    /**
     * @param string $oddBgColor
     * @param string $oddFontColor
     */
    public function setOdd(string $oddBgColor, string $oddFontColor)
    {
        $this->oddBgColor = $oddBgColor;
        $this->oddFontColor = $oddFontColor;
    }

    /**
     * @return string
     */
    public function getEvenBgColor(): ?string
    {
        return $this->evenBgColor;
    }

    /**
     * @return string
     */
    public function getEvenFontColor(): ?string
    {
        return $this->evenFontColor;
    }

    /**
     * @param string $evenBgColor
     * @param string $evenFontColor
     */
    public function setEven(string $evenBgColor, string $evenFontColor)
    {
        $this->evenBgColor = $evenBgColor;
        $this->evenFontColor = $evenFontColor;
    }

    /**
     * @return array
     */
    public function getColumnWidths()
    {
        return $this->columnWidths;
    }

    /**
     * @param array $columnWidths
     */
    public function setColumnWidths(array $columnWidths)
    {
        $this->columnWidths = $columnWidths;
    }

    /**
     * @inheritdoc
     */
    public function renderComponent(): string
    {
        $data = [
            "headers" => $this->dataTable->getHeaders(),
            "rows" => $this->dataTable->getRows(),
            "oddBgColor" => $this->getOddBgColor(),
            "oddFontColor" => $this->getOddFontColor(),
            "evenBgColor" => $this->getEvenBgColor(),
            "evenFontColor" => $this->getEvenFontColor(),
            "columnWidths" => $this->getColumnWidths(),
            "cellpadding" => $this->getCellPadding(),
            "cellspacing" => $this->getCellSpacing(),
            "borderWidth" => $this->getBorderWidth(),
        ];

        return $this->renderTemplate($data);
    }

    /**
     * @return int
     */
    public function getCellPadding(): int
    {
        return $this->cellPadding;
    }

    /**
     * @param int $cellPadding
     */
    public function setCellPadding(int $cellPadding): void
    {
        $this->cellPadding = $cellPadding;
    }

    /**
     * @return int
     */
    public function getCellSpacing(): int
    {
        return $this->cellSpacing;
    }

    /**
     * @param int $cellSpacing
     */
    public function setCellSpacing(int $cellSpacing): void
    {
        $this->cellSpacing = $cellSpacing;
    }

    /**
     * @return string
     */
    public function getBorderWidth(): string
    {
        return $this->borderWidth;
    }

    /**
     * @param string $borderWidth
     */
    public function setBorderWidth(string $borderWidth): void
    {
        $this->borderWidth = $borderWidth;
    }




}
