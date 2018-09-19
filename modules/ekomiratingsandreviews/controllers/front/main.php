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

/**
 * Class EkomiRatingsAndReviewsModuleFrontController
 */
class EkomiRatingsAndReviewsMainModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        // your code here
        parent::initContent();
    }

    public function displayAjax()
    {
        $type = Tools::getValue('type');
        
        if ($type == 'loadReviews') {
            $productId = Tools::getValue('articleId');
            $shopId = Tools::getValue('shopId');
            $pageOffset = Tools::getValue('pageOffset');
            $reviewsLimit = Tools::getValue('reviewsLimit');
            $filter = Tools::getValue('filter');
            $prc = Module::getInstanceByName("ekomiratingsandreviews");
            $productIdsArray = $prc->getProductIdsArray($productId, $this->context->language->id);
            $responseBody = json_encode(
                $prc->getReviewsHtml($shopId, $productIdsArray, $pageOffset, $reviewsLimit, $filter)
            );
        } elseif ($type == 'saveFeedback') {
            $reviewId = Tools::getValue('review_id');
            $helpfulness = Tools::getValue('helpfulness');
            $responseBody = ReviewsModel::saveFeedback($reviewId, $helpfulness);
        } else {
            $responseBody = json_encode(
                array(
                    'state' => 'error',
                    'message' => 'Invalid request!',
                    )
            );
        }
        echo $responseBody;
        die;
    }
}
