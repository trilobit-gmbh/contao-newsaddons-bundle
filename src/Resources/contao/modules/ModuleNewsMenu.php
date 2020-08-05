<?php

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-newsaddons-bundle
 */

namespace Trilobit\NewsaddonsBundle;

/**
 * Class ModuleNewsMenu.
 */
class ModuleNewsMenu extends \ModuleNewsMenu
{
    /**
     * @var bool
     */
    protected $blnShowFirstLast = true;

    /**
     * @return string
     */
    public function generate()
    {
        return parent::generate();
    }

    protected function compile()
    {
        switch ($this->news_format) {
            case 'news_year':
                $this->compileYearlyMenu();
                $this->Template->total = $this->intTotalPages();
                $this->Template->pages = $this->getPages();
                $this->Template->pagination = $this->getPagination();
                break;
            case 'news_quarter':
                $this->compileQuarterlyMenu();
                $this->Template->total = $this->intTotalPages();
                $this->Template->pages = $this->getPages();
                $this->Template->pagination = $this->getPagination();
                break;
            default:
            case 'news_month':
                $this->compileMonthlyMenu();
                $this->Template->total = $this->intTotalPages();
                $this->Template->pages = $this->getPages();
                $this->Template->pagination = $this->getPagination();
                break;
            case 'news_day':
                $this->compileDailyMenu();
                break;
        }

        $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];
    }

    /**
     * @return string
     */
    protected function getPagination()
    {
        $this->lblFirst = $GLOBALS['TL_LANG']['MSC']['first'];
        $this->lblPrevious = $GLOBALS['TL_LANG']['MSC']['previous'];
        $this->lblNext = $GLOBALS['TL_LANG']['MSC']['next'];
        $this->lblLast = $GLOBALS['TL_LANG']['MSC']['last'];
        $this->lblTotal = $GLOBALS['TL_LANG']['MSC']['totalPages'];

        $this->strParameter = $this->strParameter();
        $this->intPage = $this->intPage();
        $this->pages = $this->getPages();
        $this->intTotalPages = $this->intTotalPages();

        $this->intNumberOfLinks = 7;

        $blnQuery = false;
        list($this->strUrl) = explode('?', \Environment::get('request'), 2);

        // Prepare the URL
        foreach (preg_split('/&(amp;)?/', \Environment::get('queryString'), -1, PREG_SPLIT_NO_EMPTY) as $fragment) {
            if (false === strpos($fragment, $this->strParameter.'=')) {
                $this->strUrl .= (!$blnQuery ? '?' : '&amp;').$fragment;
                $blnQuery = true;
            }
        }

        $this->strVarConnector = $blnQuery ? '&amp;' : '?';

        $objTemplate = new \FrontendTemplate('pagination');

        $objTemplate->hasFirst = $this->hasFirst();
        $objTemplate->hasPrevious = $this->hasPrevious();
        $objTemplate->hasNext = $this->hasNext();
        $objTemplate->hasLast = $this->hasLast();

        $objTemplate->pages = $this->getItemsAsArray();
        $objTemplate->pages = $this->getItemsAsArray();
        $objTemplate->total = sprintf($this->lblTotal, $this->intPage, $this->intTotalPages);

        $objTemplate->first = [
            'link' => $this->lblFirst,
            'href' => $this->linkToPage(1),
            'title' => \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['goToPage'], 1 .' ('.$this->pages[array_keys($this->pages)[0]]['link'].')')),
        ];

        $objTemplate->previous = [
            'link' => $this->lblPrevious,
            'href' => $this->linkToPage($this->pages[array_keys($this->pages)[$this->intPage - 2]]['date']),
            'title' => \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['goToPage'], $this->intPage - 1 .' ('.$this->pages[array_keys($this->pages)[$this->intPage - 2]]['link'].')')),
        ];

        $objTemplate->next = [
            'link' => $this->lblNext,
            'href' => $this->linkToPage($this->pages[array_keys($this->pages)[$this->intPage]]['date']),
            'title' => \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['goToPage'], $this->intPage + 1 .' ('.$this->pages[array_keys($this->pages)[$this->intPage]]['link'].')')),
        ];

        $objTemplate->last = [
            'link' => $this->lblLast,
            'href' => $this->linkToPage($this->pages[array_keys($this->pages)[$this->intTotalPages - 1]]['date']),
            'title' => \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['goToPage'], $this->intTotalPages.' ('.$this->pages[array_keys($this->pages)[$this->intTotalPages - 1]]['link'].')')),
        ];

        $objTemplate->class = 'pagination-'.$this->strParameter;

        // Adding rel="prev" and rel="next" links is not possible
        // anymore with unique variable names (see #3515 and #4141)

        return $objTemplate->parse();
    }

    /**
     * @return bool
     */
    protected function hasFirst()
    {
        return ($this->blnShowFirstLast && $this->intPage > 2) ? true : false;
    }

    /**
     * @return bool
     */
    protected function hasPrevious()
    {
        return ($this->intPage > 1) ? true : false;
    }

    /**
     * @return bool
     */
    protected function hasNext()
    {
        return ($this->intPage < $this->intTotalPages) ? true : false;
    }

    /**
     * @return bool
     */
    protected function hasLast()
    {
        return ($this->blnShowFirstLast && $this->intPage < ($this->intTotalPages - 1)) ? true : false;
    }

    /**
     * @return array
     */
    protected function getItemsAsArray()
    {
        $arrLinks = [];

        $intNumberOfLinks = floor($this->intNumberOfLinks / 2);
        $intFirstOffset = $this->intPage - $intNumberOfLinks - 1;

        if ($intFirstOffset > 0) {
            $intFirstOffset = 0;
        }

        $intLastOffset = $this->intPage + $intNumberOfLinks - $this->intTotalPages;

        if ($intLastOffset < 0) {
            $intLastOffset = 0;
        }

        $intFirstLink = $this->intPage - $intNumberOfLinks - $intLastOffset;

        if ($intFirstLink < 1) {
            $intFirstLink = 1;
        }

        $intLastLink = $this->intPage + $intNumberOfLinks - $intFirstOffset;

        if ($intLastLink > $this->intTotalPages) {
            $intLastLink = $this->intTotalPages;
        }

        for ($i = $intFirstLink; $i <= $intLastLink; ++$i) {
            if ($i === $this->intPage) {
                $arrLinks[] = [
                    'page' => $this->pages[array_keys($this->pages)[$i - 1]]['page'],
                    'href' => null,
                    'title' => null,
                ];
            } else {
                $arrLinks[] = [
                    'page' => $this->pages[array_keys($this->pages)[$i - 1]]['page'],
                    'href' => $this->linkToPage($this->pages[array_keys($this->pages)[$i - 1]]['date']),
                    'title' => \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['goToPage'], $i.' ('.$this->pages[array_keys($this->pages)[$i - 1]]['link'].')')),
                ];
            }
        }

        return $arrLinks;
    }

    /**
     * @param $intPage
     *
     * @return string|string[]|null
     */
    protected function linkToPage($intPage)
    {
        if ($intPage <= 1 && !$this->blnForceParam) {
            return ampersand($this->strUrl);
        }

        return ampersand($this->strUrl).$this->strVarConnector.$this->strParameter.'='.$intPage;
    }

    /**
     * @return int|null
     */
    protected function intTotalPages()
    {
        if ($this->Template->yearly) {
            return \count($this->Template->items);
        }

        if (!$this->Template->yearly
            && !$this->Template->quarterly
            && !$this->Template->daily
            || $this->Template->quarterly
        ) {
            $intTotalPages = 0;

            foreach ($this->Template->items as $intYear => $arrItems) {
                $intTotalPages += \count($arrItems);
            }

            return $intTotalPages;
        }

        return null;
    }

    /**
     * @return false|int|string
     */
    protected function intPage()
    {
        $intYear = \Input::get('year');
        $intQuarter = \Input::get('quarter');
        $intMonth = \Input::get('month');

        $intDate = null;

        if ($this->Template->yearly) {
            $intDate = $intYear;

            if (!isset($_GET['year'])) {
                $intDate = date('Y');
            }
        }

        if ($this->Template->quarterly) {
            $intDate = $intQuarter;

            if (!isset($_GET['quarter'])) {
                $intDate = date('Y').ceil(date('n', time()) / 3);
            }
        }

        if (!$this->Template->yearly
            && !$this->Template->quarterly
            && !$this->Template->daily
        ) {
            $intDate = $intMonth;

            if (!isset($_GET['month'])) {
                $intDate = date('Ym');
            }
        }

        return array_search($intDate, array_keys($this->getPages()), true) + 1;
    }

    /**
     * @return string|null
     */
    protected function strParameter()
    {
        if ($this->Template->yearly) {
            return 'year';
        }

        if ($this->Template->quarterly) {
            return 'quarter';
        }

        if (!$this->Template->yearly
            && !$this->Template->quarterly
            && !$this->Template->daily
        ) {
            return 'month';
        }

        return null;
    }

    /**
     * @return array|mixed
     */
    protected function getPages()
    {
        if ($this->Template->yearly) {
            return $this->Template->items;
        }

        if ((!$this->Template->yearly
            && !$this->Template->quarterly
            && !$this->Template->daily)
            || $this->Template->quarterly
        ) {
            $arrPages = [];

            foreach ($this->Template->items as $intYear => $arrItems) {
                foreach ($arrItems as $key => $arrItem) {
                    $arrItem['page'] = $arrItem['link'];

                    $arrPages[$arrItem['date']] = $arrItem;
                }
            }

            return $arrPages;
        }
    }

    protected function compileQuarterlyMenu()
    {
        $arrData = [];
        $time = \Date::floorToMinute();

        // Get the dates
        $objDates = $this->Database->query("SELECT FROM_UNIXTIME(date, '%Y') AS year, QUARTER(FROM_UNIXTIME(date, '%Y-%m-%d')) AS quarter, COUNT(*) AS count FROM tl_news WHERE pid IN(".implode(',', array_map('intval', $this->news_archives)).')'.((!BE_USER_LOGGED_IN || TL_MODE === 'BE') ? " AND (start='' OR start<='$time') AND (stop='' OR stop>'".($time + 60)."') AND published='1'" : '').' GROUP BY year, quarter ORDER BY year DESC, quarter DESC');

        while ($objDates->next()) {
            $arrData[$objDates->year][$objDates->quarter] = $objDates->count;
        }

        // Sort the data
        foreach (array_keys($arrData) as $key) {
            ('ascending' === $this->news_order) ? ksort($arrData[$key]) : krsort($arrData[$key]);
        }

        ('ascending' === $this->news_order) ? ksort($arrData) : krsort($arrData);

        $arrItems = [];

        // Prepare the navigation
        foreach ($arrData as $intYear => $arrQuarter) {
            $count = 0;
            $limit = \count($arrQuarter);

            foreach ($arrQuarter as $intQuarter => $intCount) {
                $intDate = $intYear.$intQuarter;
                $intQuarter = ((int) $intQuarter);

                $quantity = sprintf((($intCount < 2) ? $GLOBALS['TL_LANG']['MSC']['entry'] : $GLOBALS['TL_LANG']['MSC']['entries']), $intCount);

                $arrItems[$intYear][$intQuarter]['date'] = $intDate;
                $arrItems[$intYear][$intQuarter]['link'] = sprintf($GLOBALS['TL_LANG']['QUARTER'], $intQuarter, $intYear);
                $arrItems[$intYear][$intQuarter]['href'] = $this->strUrl.'?quarter='.$intDate;
                $arrItems[$intYear][$intQuarter]['title'] = \StringUtil::specialchars('Q'.$intQuarter.' '.$intYear.' ('.$quantity.')');
                $arrItems[$intYear][$intQuarter]['class'] = trim(((1 === ++$count) ? 'first ' : '').(($count === $limit) ? 'last' : ''));
                $arrItems[$intYear][$intQuarter]['isActive'] = (\Input::get('quarter') === $intDate);
                $arrItems[$intYear][$intQuarter]['quantity'] = $quantity;
            }
        }

        $this->Template->quarterly = true;
        $this->Template->items = $arrItems;
        $this->Template->showQuantity = ('' !== $this->news_showQuantity) ? true : false;
        $this->Template->url = $this->strUrl.'?';
        $this->Template->activeYear = \Input::get('year');
    }
}
