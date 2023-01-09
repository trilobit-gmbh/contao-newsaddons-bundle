<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

use Trilobit\NewsaddonsBundle\ModuleNewsArchive;
use Trilobit\NewsaddonsBundle\ModuleNewsList;
use Trilobit\NewsaddonsBundle\ModuleNewsMenu;

$GLOBALS['FE_MOD']['news']['newsmenu'] = ModuleNewsMenu::class;
$GLOBALS['FE_MOD']['news']['newsarchive'] = ModuleNewsArchive::class;
$GLOBALS['FE_MOD']['news']['newslist'] = ModuleNewsList::class;
