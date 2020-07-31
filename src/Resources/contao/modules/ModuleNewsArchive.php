<?php

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-newsaddons-bundle
 */

namespace Trilobit\NewsaddonsBundle;

use Contao\Config;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Date;
use Contao\Environment;
use Contao\Input;
use Contao\NewsModel;
use Contao\Pagination;

/**
 * Class ModuleNewsArchive.
 */
class ModuleNewsArchive extends \Contao\ModuleNewsArchive
{
    /**
     * @return string
     */
    public function generate()
    {
        return parent::generate();
    }

    protected function compile()
    {
        /* @var \Contao\PageModel $objPage */
        global $objPage;

        $limit = null;
        $offset = 0;
        $intBegin = 0;
        $intEnd = 0;

        $intYear = Input::get('year');
        $intQuarter = Input::get('quarter');
        $intMonth = Input::get('month');
        $intDay = Input::get('day');

        // Jump to the current period
        if (!isset($_GET['year']) && !isset($_GET['quarter']) && !isset($_GET['month']) && !isset($_GET['day']) && 'all_items' !== $this->news_jumpToCurrent) {
            switch ($this->news_format) {
                case 'news_year':
                    $intYear = Date::parse('Y');
                    break;
                case 'news_quarter':
                    $intQuarter = Date::parse('Y').ceil(Date::parse('n', time()) / 3);
                    break;
                default:
                case 'news_month':
                    $intMonth = Date::parse('Ym');
                    break;
                case 'news_day':
                    $intDay = Date::parse('Ymd');
                    break;
            }
        }

        // Create the date object
        try {
            if (!empty($intYear)) {
                $strDate = $intYear;
                $objDate = new Date($strDate, 'Y');
                $intBegin = $objDate->yearBegin;
                $intEnd = $objDate->yearEnd;
                $this->headline .= ' '.Date::parse('Y', $objDate->tstamp);
            } elseif (!empty($intQuarter)) {
                preg_match_all('/^(\d{4})(\d{1})$/', $intQuarter, $arrMatch);

                $strDateBegin = $arrMatch[1][0];
                $strDateEnd = $arrMatch[1][0];

                if (1 === (int) $arrMatch[2][0]) {
                    $strDateBegin .= '01';
                    $strDateEnd .= '03';
                } elseif (2 === (int) $arrMatch[2][0]) {
                    $strDateBegin .= '04';
                    $strDateEnd .= '06';
                } elseif (3 === (int) $arrMatch[2][0]) {
                    $strDateBegin .= '07';
                    $strDateEnd .= '09';
                } else {
                    $strDateBegin .= '10';
                    $strDateEnd .= '12';
                }

                $objDateBegin = new Date($strDateBegin, 'Ym');
                $objDateEnd = new Date($strDateEnd, 'Ym');

                $intBegin = $objDateBegin->monthBegin;
                $intEnd = $objDateEnd->monthEnd;

                $this->headline .= ' '. Date::parse('F Y', $objDateBegin->tstamp).' - '. Date::parse('F Y', $objDateEnd->tstamp);

                $this->Template->quarterly = true;

                $this->Template->quarter = $arrMatch[2][0];
                $this->Template->quarterBegin = Date::parse('F Y', $objDateBegin->tstamp);
                $this->Template->quarterEnd = Date::parse('F Y', $objDateEnd->tstamp);
                $this->Template->year = $arrMatch[1][0];
            } elseif (!empty($intMonth)) {
                $strDate = $intMonth;
                $objDate = new Date($strDate, 'Ym');
                $intBegin = $objDate->monthBegin;
                $intEnd = $objDate->monthEnd;
                $this->headline .= ' '. Date::parse('F Y', $objDate->tstamp);
            } elseif (!empty($intDay)) {
                $strDate = $intDay;
                $objDate = new Date($strDate, 'Ymd');
                $intBegin = $objDate->dayBegin;
                $intEnd = $objDate->dayEnd;
                $this->headline .= ' '. Date::parse($objPage->dateFormat, $objDate->tstamp);
            } elseif ('all_items' === $this->news_jumpToCurrent) {
                $intBegin = 0;
                $intEnd = time();
            }
        } catch (\OutOfBoundsException $e) {
            throw new PageNotFoundException('Page not found: '. Environment::get('uri'));
        }

        $this->Template->articles = [];

        // Split the result
        if ($this->perPage > 0) {
            // Get the total number of items
            $intTotal = NewsModel::countPublishedFromToByPids($intBegin, $intEnd, $this->news_archives);

            if ($intTotal > 0) {
                $total = $intTotal;

                // Get the current page
                $id = 'page_a'.$this->id;
                $page = (null !== Input::get($id)) ? Input::get($id) : 1;

                // Do not index or cache the page if the page number is outside the range
                if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                    throw new PageNotFoundException('Page not found: '. Environment::get('uri'));
                }

                // Set limit and offset
                $limit = $this->perPage;
                $offset = (max($page, 1) - 1) * $this->perPage;

                // Add the pagination menu
                $objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
                $this->Template->pagination = $objPagination->generate("\n  ");
            }
        }

        // Get the news items
        if (isset($limit)) {
            $objArticles = NewsModel::findPublishedFromToByPids($intBegin, $intEnd, $this->news_archives, $limit, $offset);
        } else {
            $objArticles = NewsModel::findPublishedFromToByPids($intBegin, $intEnd, $this->news_archives);
        }

        // Add the articles
        if (null !== $objArticles) {
            $this->Template->articles = $this->parseArticles($objArticles);
        }

        $this->Template->headline = trim($this->headline);
        $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
        $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['empty'];
    }
}
