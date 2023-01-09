<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

/*
 * Update fields.
 */
$GLOBALS['TL_DCA']['tl_news']['fields']['date']['sql'] = "int(10) signed NOT NULL default '0'";
$GLOBALS['TL_DCA']['tl_news']['fields']['time']['sql'] = "int(10) signed NOT NULL default '0'";
