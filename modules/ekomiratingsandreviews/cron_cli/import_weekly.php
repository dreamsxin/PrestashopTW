<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    eKomi
 *  @copyright 2017 eKomi
 *  @license   LICENSE.txt
 */

/* Load Prestashop */
require(dirname(__FILE__).'/../../../config/config.inc.php');
require(dirname(__FILE__).'/../../../init.php');

/* Load module files */
require_once(_PS_MODULE_DIR_.'/ekomiratingsandreviews/ekomiratingsandreviews.php');
require_once(_PS_MODULE_DIR_.'/ekomiratingsandreviews/models/ReviewsModel.php');

/* Do stuff */
$result = ReviewsModel::populateTable('1w');
if ($result['status'] === 'error') {
    echo "Failure: Not all reviews were imported successfully." . PHP_EOL;
    die;
}
echo "Success: All reviews imported." . PHP_EOL;

/* Must die. */
echo "END" . PHP_EOL;
die;
