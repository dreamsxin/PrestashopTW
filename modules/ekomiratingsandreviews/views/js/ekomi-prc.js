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

var prcJQuery = jQuery.noConflict(true);

prcJQuery(document).ready(function () {
    // sorting reviews data
    prcJQuery('.ekomi_reviews_sort').on('change', function (e) {
        filter = this.value;
        pageOffset = 0;
        var data = {
            type: 'loadReviews',
            articleId: articleId,
            shopId: shopId,
            queryBy: queryBy,
            pageOffset: pageOffset,
            reviewsLimit: reviewsLimit,
            filter: filter
        };

        prcJQuery.ajax({
            type: "POST",
            url: ajaxUrl,
            data: data,
            cache: false,
            success: function (data) {
                var json = JSON.parse(data);
                prcJQuery('#ekomi_reviews_container').html(json.reviews_data.result);
                // reset the page offset
                pageReviewsCount = json.reviews_data.count;
                prcJQuery('.current_review_batch').text(pageReviewsCount);
                prcJQuery('.loads_more_reviews').show();
            }
        });
    });

    // saving users feedback on reviews
    prcJQuery('body').on('click', '.ekomi_review_helpful_button', function () {
        var current = prcJQuery(this);

        var data = {
            type: 'saveFeedback',
            articleId: articleId,
            shopId: shopId,
            review_id: prcJQuery(this).data('review-id'),
            helpfulness: prcJQuery(this).data('review-helpfulness')
        };

        prcJQuery.ajax({
            type: "POST",
            url: ajaxUrl,
            data: data,
            cache: false,
            success: function (data) {
                var json = prcJQuery.parseJSON(data);

                current.parent('.ekomi_review_helpful_question').hide();
                current.parent().prev('.ekomi_review_helpful_thankyou').show();
                var infoMsg= json.helpfull_count+" "+prcJQuery('.ekomi_prc_out_of').text()+" "+json.total_count+" "+prcJQuery('.ekomi_prc_people_found').text();
                current.parent().prev().prev('.ekomi_review_helpful_info').html(infoMsg);
            }
        });
    });

    // Loading reviews on paginatin
    prcJQuery('body').on('click', '.loads_more_reviews', function (e) {
        pageOffset = pageReviewsCount;

        if (reviewsCount / pageReviewsCount > 1) {
            var data = {
                type: 'loadReviews',
                articleId: articleId,
                shopId: shopId,
                queryBy: queryBy,
                pageOffset: pageOffset,
                reviewsLimit: reviewsLimit,
                filter: filter
            };

            prcJQuery.ajax({
                type: "POST",
                url: ajaxUrl,
                data: data,
                cache: false,
                success: function (data) {
                    var json = prcJQuery.parseJSON(data);

                    pageReviewsCount = pageReviewsCount + json.reviews_data.count;
                    prcJQuery('#ekomi_reviews_container').append(json.reviews_data.result);
                    prcJQuery('.current_review_batch').text(pageReviewsCount);

                    if (reviewsCount / pageReviewsCount <= 1) {
                        prcJQuery('.loads_more_reviews').hide();
                    }
                }
            });
        } else {
            prcJQuery('.loads_more_reviews').hide();
        }
    });
});
