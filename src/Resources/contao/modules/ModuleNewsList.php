<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\NewsaddonsBundle;

use Contao\Config;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Pagination;

class ModuleNewsList extends \Contao\ModuleNewsList
{
    /**
     * @return string
     */
    public function generate()
    {
        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile()
    {
        $limit = null;
        $offset = (int) $this->skipFirst;

        // Maximum number of items
        if ($this->numberOfItems > 0) {
            $limit = $this->numberOfItems;
        }

        // Handle featured news
        if ('featured' === $this->news_featured) {
            $blnFeatured = true;
        } elseif ('unfeatured' === $this->news_featured) {
            $blnFeatured = false;
        } else {
            $blnFeatured = null;
        }

        $this->Template->articles = [];
        $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

        // Get the total number of items
        $intTotal = $this->countItems($this->news_archives, $blnFeatured);

        if ($intTotal < 1) {
            return;
        }

        $total = $intTotal - $offset;

        // Split the results
        if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage)) {
            // Adjust the overall limit
            if (isset($limit)) {
                $total = min($limit, $total);
            }

            // Get the current page
            $id = 'page_n'.$this->id;
            $page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
            }

            // Set limit and offset
            $limit = $this->perPage;
            $offset += (max($page, 1) - 1) * $this->perPage;
            $skip = (int) $this->skipFirst;

            // Overall limit
            if ($offset + $limit > $total + $skip) {
                $limit = $total + $skip - $offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }

        $objArticles = $this->fetchItems($this->news_archives, $blnFeatured, ($limit ?: 0), $offset);

        // Add the articles
        if (null !== $objArticles) {
            if ($this->groupQuarterly) {
                $this->Template->articles = $this->parseArticlesQuarterly($objArticles);
            } else {
                $this->Template->articles = $this->parseArticles($objArticles);
            }
        }

        $this->Template->archives = $this->news_archives;
        $this->Template->currentQuarter = date('Y').ceil(date('n') / 3);
    }

    /**
     * Parse one or more items and return them as array.
     *
     * @return array
     */
    protected function parseArticlesQuarterly(Collection $objArticles, bool $blnAddArchive = false)
    {
        $limit = $objArticles->count();

        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrArticles = [];

        foreach ($objArticles as $objArticle) {
            $intQuarter = date('Y', $objArticle->date).ceil(date('n', $objArticle->date) / 3);
            $arrArticles[$intQuarter][] = $this->parseArticle($objArticle, $blnAddArchive, ((1 === ++$count) ? ' first' : '').(($count === $limit) ? ' last' : '').((0 === ($count % 2)) ? ' odd' : ' even'), $count);
        }

        return $arrArticles;
    }
}
