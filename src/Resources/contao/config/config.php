<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2016 Heimrich & Hannot GmbH
 *
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

/**
 * Ajax Actions
 */
if(!isset($GLOBALS['AJAX']))
{
$GLOBALS['AJAX'] = [];
}


/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['initializeSystem'][] = ['huh.ajax.hooks', 'initializeSystemHook'];
