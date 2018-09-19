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

/* For PHP versions less than 5.6. */
define('EKOMI_RATINGS_AND_REVIEWS_TABLE_NAME', 'ekomi_ratings_and_reviews');
define('EKOMI_RATINGS_AND_REVIEWS_TABLE_NAME_WITH_PREFIX', _DB_PREFIX_ . EKOMI_RATINGS_AND_REVIEWS_TABLE_NAME);

/**
 * Class ReviewsModel
 */
class ReviewsModel extends ObjectModel
{

    /**
     * Name of the database table.
     */
    const TABLE_NAME = EKOMI_RATINGS_AND_REVIEWS_TABLE_NAME;

    /**
     * Name of the database table with the Prestashop defined prefix.
     */
    const TABLE_NAME_WITH_PREFIX = EKOMI_RATINGS_AND_REVIEWS_TABLE_NAME_WITH_PREFIX;

    /**
     * @return bool
     */
    public static function createTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME_WITH_PREFIX . '`(
        `id_ekomi_ratings_and_reviews` int(10) unsigned NOT NULL auto_increment,
        `shop_id` int(10) unsigned NOT NULL,
        `order_id` varchar(64) NOT NULL,
        `product_id` varchar(64) NOT NULL,
        `timestamp` int(11) unsigned NOT NULL,
        `stars` tinyint(1) unsigned NOT NULL,
        `review_comment` text NOT NULL,
        `helpful` mediumint(5) unsigned NOT NULL default \'0\',
        `nothelpful` mediumint(5) unsigned NOT NULL default \'0\',
        PRIMARY KEY (`id_ekomi_ratings_and_reviews`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @return bool
     */
    public static function dropTable()
    {
        $sql = 'DROP TABLE `' . self::TABLE_NAME_WITH_PREFIX . '`';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param string $range
     * @return array
     */
    public static function populateTable($range)
    {
        if (!EkomiRatingsAndReviews::isActivated()) {
            return array(
                'status' => 'error',
                'message' => 'Please enable the plugin!');
        }

        $result = array(
            'status' => 'success',
            'message' => "Successfully imported all reviews. If a review was already present, it is not overwritten."
        );
        $configs = EkomiRatingsAndReviews::getConfigValues();
        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $config = array(
                'ShopId' => $configs['EKOMI_RNR_SHOP_ID'][$lang['id_lang']],
                'ShopPw' => $configs['EKOMI_RNR_SHOP_PASSWORD'][$lang['id_lang']]
            );

            $response = EkomiRatingsAndReviews::verifyAccount(
                $config['ShopId'],
                $config['ShopPw']
            );

            if (!$response['success']) {
                $result[$lang['id_lang']] = array(
                    'status' => 'error',
                    'shopId' => $config['ShopId'],
                    'message' => $response['message'] . " for " . $lang['name'] . " shop."
                );

                continue;
            }

            $productReviews = ReviewsModel::getApiReviews($config, $range);
            $savedReviews   = 0;

            if (!(is_array($productReviews)) || count($productReviews) === 0) {
                $result[$lang['id_lang']] = array(
                    'status' => 'error',
                    'shopId' => $config['ShopId'],
                    'message' => "No reviews imported for " . $lang['name'] . " shop. This could mean two posibilites.
                    (1) There were no reviews for your shop present at eKomi.
                    (2) Something went wrong while fetching reviews from eKomi.
                    If you think that some reviews should be imported then try saving the
                    configurations again."
                );

                continue;
            }

            foreach ($productReviews as $productReview) {
                if (ReviewsModel::ifReviewExists($config['ShopId'], $productReview)) {
                    continue;
                }

                $args = array(
                    'shop_id'        => intval($config['ShopId']),
                    'order_id'       => pSQL($productReview['order_id']),
                    'product_id'     => pSQL($productReview['product_id']),
                    'timestamp'      => pSQL($productReview['submitted']),
                    'stars'          => pSQL($productReview['rating']),
                    'review_comment' => pSQL($productReview['review']),
                    );

                if (Db::getInstance()->insert(self::TABLE_NAME, $args) === false) {
                    continue;
                }

                $savedReviews++;
            }

            if ($savedReviews > 0) {
                $message = "Successfully imported " . $savedReviews . " reviews for " . $lang['name'] . " shop.";
            } else {
                $message = "Reviews already up to date for " . $lang['name'] . " shop.";
            }

            $result[$lang['id_lang']] = array(
                'status' => 'success',
                'shopId' => $config['ShopId'],
                'message' => $message
            );
        }

        return $result;
    }

    /**
     * @param        $config
     * @param string $range
     *
     * @return array|mixed|object
     */
    public static function getApiReviews($config, $range = 'all')
    {
        $ekomiApiUrl = 'http://api.ekomi.de/v3/getProductfeedback?interface_id=' .
        $config['ShopId'] . '&interface_pw=' . $config['ShopPw'] .
        '&type=json&charset=utf-8&range=' . $range;

        // Get the reviews
        $productReviews = self::fileGetContentsCurl($ekomiApiUrl);

        return json_decode($productReviews, true);
    }

    /**
     * @param $shopId
     * @param $productIdsArray
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public static function getReviewsStars($shopId, $productIdsArray)
    {
        /* Sanitizing Data */
        for ($i = 0; $i < count($productIdsArray); $i++) {
            $productIdsArray[$i] = intval($productIdsArray[$i]);
        }
        $productIdsCommaSeparated = implode(',', $productIdsArray);
        $sql = "select reviews.product_id, reviews.stars, count(reviews.id_ekomi_ratings_and_reviews) as starsCount ";
        $sql .= "from " . self::TABLE_NAME_WITH_PREFIX . " as reviews ";
        $sql .= "where reviews.product_id in (" . $productIdsCommaSeparated . ") ";
        $sql .= "AND ";
        $sql .= "reviews.shop_id = " . intval($shopId) . " ";
        $sql .= "group by reviews.stars ";
        $sql .= "order by reviews.stars desc ";
        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param $shopId
     * @param $productIdsArray
     * @param $reviewsOffset
     * @param $reviewsLimit
     * @param $orderBy
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public static function getReviews($shopId, $productIdsArray, $reviewsOffset, $reviewsLimit, $orderBy)
    {
        /* Sanitizing Data */
        for ($i = 0; $i < count($productIdsArray); $i++) {
            $productIdsArray[$i] = intval($productIdsArray[$i]);
        }
        $productIdsCommaSeparated = implode(',', $productIdsArray);
        $shopId = (int) $shopId;
        $reviewsOffset = (int) $reviewsOffset;
        $reviewsLimit = (int) $reviewsLimit;

        $orderByColumnName  = 'timestamp';

        if (Validate::isOrderBy($orderBy['column_name'])) {
            $orderByColumnName = Tools::strtolower($orderBy['column_name']);
        }

        $orderByType = Validate::isOrderWay($orderBy['type']) ? Tools::strtoupper($orderBy['type']) : 'DESC';

        /* Creating SQL Query */
        $sql = "SELECT * ";
        $sql .= "FROM " . self::TABLE_NAME_WITH_PREFIX . " ";
        $sql .= "WHERE product_id in (" . $productIdsCommaSeparated . ") ";
        $sql .= "AND shop_id=" . $shopId . " ";
        $sql .= "ORDER BY " . $orderByColumnName . " " . $orderByType . " ";
        $sql .= "LIMIT " . pSQL($reviewsOffset) . ", " . pSQL($reviewsLimit);

        /* Executing Query */
        $reviews = Db::getInstance()->executeS($sql, true, false);

        return $reviews;
    }

    /**
     * @param $starsCountArray
     * @return array
     */
    public static function getReviewStarsCountsArray($starsCountArray)
    {
        $starsArray = array();
        foreach ($starsCountArray as $value) {
            $starsArray[$value['stars']] = $value['starsCount'];
        }

        // set count for all stars
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($starsArray[$i])) {
                $starsArray[$i] = 0;
            }
        }

        return $starsArray;
    }

    /**
     * @param $shopId
     * @param $productIdsArray
     * @return array|false|mysqli_result|null|PDOStatement|resource|string
     */
    public static function getReviewsStarsAvg($shopId, $productIdsArray)
    {
        /* Sanitizing Data */
        for ($i = 0; $i < count($productIdsArray); $i++) {
            $productIdsArray[$i] = intval($productIdsArray[$i]);
        }
        $productIdsCommaSeparated = implode(',', $productIdsArray);
        $sql = "select AVG(reviews.stars) as stars_average ";
        $sql .= "from " . self::TABLE_NAME_WITH_PREFIX . " as reviews ";
        $sql .= "where reviews.product_id in (" . pSQL($productIdsCommaSeparated) . ") ";
        $sql .= "AND ";
        $sql .= "reviews.shop_id = " . intval($shopId) . " ";

        $starsAvg = Db::getInstance()->executeS($sql);
        $starsAvg = number_format($starsAvg[0]["stars_average"], 1);

        return $starsAvg;
    }

    /**
     * @param $reviewId
     * @param $helpfulness
     * @return null|string
     */
    public static function saveFeedback($reviewId, $helpfulness)
    {
        $reponseBody = null;
        if (!$reviewId || is_null($helpfulness)) {
            $reponseBody = json_encode(
                array(
                    'state' => 'error',
                    'message' => 'Please provide the review parameters',
                    'helpfulness' => $helpfulness . ' ' . gettype($helpfulness),
                )
            );
        } else {
            $rate_helpfulness = self::rateTheReview($reviewId, $helpfulness);

            if ($rate_helpfulness >= 1) {
                $sql = "select helpful, nothelpful from " . self::TABLE_NAME_WITH_PREFIX . " ";
                $sql .= "where id_ekomi_ratings_and_reviews = " . intval($reviewId);
                $result = Db::getInstance()->executeS($sql);
                $helpful = $result[0]['helpful'];
                $notHelpful = $result[0]['nothelpful'];


                $reponseBody = json_encode(
                    array(
                        'state' => 'success',
                        'message' => 'Rated successfully',
                        'helpfull_count' => $helpful,
                        'total_count' => ($helpful + $notHelpful),
                        'rate_helpfulness' => $helpfulness == '1' ? 'helpful' : 'nothelpful',
                    )
                );
            } else {
                $reponseBody = json_encode(
                    array(
                        'state' => 'error',
                        'message' => 'Could not process the request! ' . $rate_helpfulness['last_error'],
                        'rate_helpfulness' => $rate_helpfulness,
                    )
                );
            }
        }
        //return
        return $reponseBody;
    }

    /**
     * @param $reviewId
     * @param $helpfulness
     * @return bool
     */
    public static function rateTheReview($reviewId, $helpfulness)
    {
        // sanitize data
        $helpfulness = trim($helpfulness);
        $reviewId = trim($reviewId);

        $sql = "select * from " . self::TABLE_NAME_WITH_PREFIX . " ";
        $sql .= "where id_ekomi_ratings_and_reviews = " . intval($reviewId);

        $result = Db::getInstance()->executeS($sql);

        if (!empty($result)) {
            if ($helpfulness == '1') {
                $sql = "select helpful from " . self::TABLE_NAME_WITH_PREFIX . " ";
                $sql .= "where id_ekomi_ratings_and_reviews = " . intval($reviewId);
                $result = Db::getInstance()->executeS($sql);
                $helpful = $result[0]['helpful'] + 1;

                $sql = "update " . self::TABLE_NAME_WITH_PREFIX . " ";
                $sql .= "set helpful = " . pSQL($helpful) . " ";
                $sql .= "where id_ekomi_ratings_and_reviews = " . intval($reviewId);
                Db::getInstance()->execute($sql);
            } else {
                $sql = "select nothelpful from " . self::TABLE_NAME_WITH_PREFIX . " ";
                $sql .= "where id_ekomi_ratings_and_reviews = " . intval($reviewId);
                $result = Db::getInstance()->executeS($sql);
                $nothelpful = $result[0]['nothelpful'] + 1;

                $sql = "update " . self::TABLE_NAME_WITH_PREFIX . " ";
                $sql .= "set nothelpful = " . pSQL($nothelpful) . " ";
                $sql .= "where id_ekomi_ratings_and_reviews = " . intval($reviewId);
                Db::getInstance()->execute($sql);
            }
            return true;
        }
        return false;
    }

    /**
     * @param $shopId
     * @param $productIdsArray
     * @return mixed
     */
    public static function getReviewsCount($shopId, $productIdsArray)
    {
        /* Sanitizing Data */
        for ($i = 0; $i < count($productIdsArray); $i++) {
            $productIdsArray[$i] = intval($productIdsArray[$i]);
        }
        $productIdsCommaSeparated = implode(',', $productIdsArray);

        /* Creating Query */
        $sql = "SELECT COUNT(*) as count ";
        $sql .= "FROM " . self::TABLE_NAME_WITH_PREFIX . " ";
        $sql .= "WHERE product_id in (" . pSQL($productIdsCommaSeparated) . ") ";
        $sql .= "AND shop_id=" . intval($shopId) . " ";

        /* Fetching Results */
        $reviews = Db::getInstance()->executeS($sql, true, false);
        
        if (isset($reviews[0])) {
            return $reviews[0]['count'];
        }

        return 0;
    }

    /**
     * @param $shopId
     * @param $review
     *
     * @return array|mixed|null|object
     */
    private static function ifReviewExists($shopId, $review)
    {
        $sql = 'SELECT *
        FROM `' . _DB_PREFIX_ . 'ekomi_ratings_and_reviews`
        WHERE `shop_id`  = ' . intval($shopId) . '
        AND `order_id`   = \'' . pSQL($review['order_id']) . '\'
        AND `product_id` = \'' . pSQL($review['product_id']) . '\'
        AND `timestamp`  = ' . pSQL($review['submitted']);

        return Db::getInstance()->getValue($sql);
    }

    /**
     * @param $url
     * @return mixed
     */
    private static function fileGetContentsCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
