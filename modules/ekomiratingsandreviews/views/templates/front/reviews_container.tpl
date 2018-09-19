{*
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
*}

{nocache}
     {if $hasReviews }
        <div class="ek-row" itemtype="http://schema.org/Product" itemscope="">
            <meta content="{$queryBy|escape:'htmlall':'UTF-8'}" itemprop="productID">
            <meta content="{$pImageUrl|escape:'htmlall':'UTF-8'}" itemprop="image">
                <script type='text/javascript'>
                    var ajaxUrl = '{$ajaxUrl|escape:'htmlall':'UTF-8'}';
                    var shopId = '{$shopId|escape:'htmlall':'UTF-8'}';
                    var articleId = '{$articleId|escape:'htmlall':'UTF-8'}';
                    var queryBy = '{$queryBy|escape:'htmlall':'UTF-8'}';
                    var filter = 0;
                    var pageOffset = 0;
                    var reviewsLimit = {$reviewsLimit|escape:'htmlall':'UTF-8'};
                    var reviewsCount = {$reviewsCount|escape:'htmlall':'UTF-8'};
                    var pageReviewsCount = {$pageReviewsCount|escape:'htmlall':'UTF-8'};
                </script>

                <div class="ekomi_prc_widget reviews_large">
                    <div class="ekomi_header">
                        <div class="ek-row">
                            <div class="ek-small-12 ek-large-6 ek-columns">
                                <span class="header_first_line">{l s='Product reviews for' mod='ekomiratingsandreviews'}</span>
                                <span class="header_second_line">
                                <span itemprop="name"> {$productName|escape:'htmlall':'UTF-8'} </span>
                                ({$reviewsCount|escape:'htmlall':'UTF-8'})
                            </span>
                            <meta content="{$pDescription|strip_tags:'UTF-8'|escape:'htmlall':'UTF-8'}" itemprop="description">
                            <meta content="{$pSku|escape:'htmlall':'UTF-8'}" itemprop="sku">
                            </div>
                            <div class="ek-small-12 ek-large-6 ek-columns ek-text-right">
                                <div class="ek-logo-text" style="">
                                    <span class="ek-powered-by">{l s='Powered by' mod='ekomiratingsandreviews'}</span>
                                    <a class="ek-logo" href="http://www.ekomi.de/de/" target="_blank">
                                        <img src="{$resourceDirUrl|escape:'htmlall':'UTF-8'}views/img/ekomi_logo.png"/>
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="ekomi_statistics">
                        <div class="ek-row">
                            <div class="ek-small-12 ek-large-6 ek-columns">
                                <div class="ekomi_rating_graph">
                                    {$i=5}
                                    {while $i > 0}
                                        <div class="ek-row ekomi_bar ek-small-collapse" data-sort-id=<?php echo $i }>
                                            <div class="ek-small-3 ek-large-3 ek-columns ek-text-right">
                                                <span class="ratings_stars_amount">{($i == 1) ? "{$i|escape:'htmlall':'UTF-8'} {l s='Star' mod='ekomiratingsandreviews'}" : "{$i} {l s='Stars' mod='ekomiratingsandreviews'}"}</span>
                                            </div>
                                            <div class="ek-small-6 ek-large-6 ek-columns">
                                                <div class="progress round">
                                                <span class="meter"
                                                      style="width: {($reviewsCount > 0)?($starsCountArray[$i] / $reviewsCount) * 100 : 0|escape:'htmlall':'UTF-8'}%"></span>
                                                </div>
                                            </div>
                                            <div class="ek-small-3 ek-large-3 ek-columns ek-text-left">
                                                <span class="ratings_overview_number">{$starsCountArray[$i]|escape:'htmlall':'UTF-8'}</span>
                                                <input type="submit" class="ekomi_button ekomi_ratings_filter_reset" value="X"/>
                                            </div>
                                        </div>
                                        {$i=$i-1}
                                    {/while}

                                </div>
                            </div>
                            <div class="ek-small-12 ek-large-6 ek-columns ek-text-center">
                                <span itemprop="offers" itemscope itemtype="http://schema.org/Offer" >
                                    <meta content="{$pPrice|escape:'htmlall':'UTF-8'}" itemprop="price">
                                    <meta content="{$pPriceCurrency|escape:'htmlall':'UTF-8'}" itemprop="priceCurrency">
                                </span>
                                <!-- product average rating  -->
                                <section itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
                                    <div class="ekomi_average_rating">
                                        <div class="ek-row ekomi_average_title ek-text-center">
                                            {l s='Average rating' mod='ekomiratingsandreviews'}
                                        </div>
                                        <div class="ek-row ek-text-center">
                                            <div class="ekomi_average_stars">
                                                <div class="ekomi_stars_wrap">
                                                    <div class="ekomi_stars_gold" style="width:{($avgStars * 20)|escape:'htmlall':'UTF-8'}%"></div>
                                                </div>
                                                <div class="ekomi_agregate_rating">
                                                    <span itemprop="ratingValue">{$avgStars|escape:'htmlall':'UTF-8'}</span> / 5
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ek-row ekomi_average_description ek-text-center">
                                        {l s='Calculated from' mod='ekomiratingsandreviews'}
                                        <span itemprop="reviewCount">{$reviewsCount|escape:'htmlall':'UTF-8'}</span>
                                        {l s='reviews' mod='ekomiratingsandreviews'}
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>
                    <div class="ekomi_filter">
                        <div class="ek-row">
                            <div class="ek-small-12 ek-medium-6 ek-columns ek-small-text-center">
                                <span class="current_review_batch">{$pageReviewsCount|escape:'htmlall':'UTF-8'}</span> {l s='out of' mod='ekomiratingsandreviews'} {$reviewsCount|escape:'htmlall':'UTF-8'} {l s='reviews' mod='ekomiratingsandreviews'} :
                            </div>
                            <div class="ek-small-12 ek-medium-6 ek-columns ek-small-text-center ek-medium-text-right">
                                <select class="ekomi_reviews_sort" autocomplete="off">
                                    <option value="1">{l s='Newest reviews' mod='ekomiratingsandreviews'}</option>
                                    <option value="2">{l s='Oldest reviews' mod='ekomiratingsandreviews'}</option>
                                    <option value="3">{l s='Most helpful reviews' mod='ekomiratingsandreviews'}</option>
                                    <option value="4">{l s='Highest rating' mod='ekomiratingsandreviews'}</option>
                                    <option value="5">{l s='Lowest rating' mod='ekomiratingsandreviews'}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="ekomi_reviews_container" class="ekomi_reviews">
                         {include file='modules/ekomiratingsandreviews/views/templates/front/reviews_container_partial.tpl' reviews=$reviews}
                    </div>
                    <div class="ekomi_footer">
                        <div class="ek-row">
                            <div class="ek-large-12 ek-columns ek-text-center">
                                {if $pageReviewsCount < $reviewsCount}
                                    <span class="loads_more_reviews">{l s='Show more' mod='ekomiratingsandreviews'}</span>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <div id="ekomi_review_template_for_js" style="display:none">
                    <section itemprop="review" itemscope itemtype="http://schema.org/Review">
                        <span itemprop="author" itemscope itemtype="http://schema.org/Organization"><meta itemprop="name" content="eKomi"></span>
                        <div class="ekomi_review ek-row">
                            <div class="ek-large-4 ek-column ek-text-center">
                                <div class="ekomi_stars_container" >
                                    <div class="ekomi_stars_wrap">
                                        <div class="ekomi_stars_gold" style="width:0%"></div>
                                    </div>
                                </div>
                                <span class="ekomi_review_time" itemprop="datePublished" content=""></span>
                            </div>

                            <div class="ek-large-8 ek-column">
                                <p class="ekomi_review_text" itemprop="reviewBody"></p>
                                <div class="ekomi_review_helpful_button_wrapper ">
                                    <span class="ekomi_review_helpful_info ek-small-text-center ek-medium-text-left"  style="display:none;" ></span>
                                    <div class="ekomi_review_helpful_thankyou ek-small-text-center ek-medium-text-left"
                                         style="display:none;">{l s='Thank you for your vote' mod='ekomiratingsandreviews'}!
                                    </div>
                                    <div class="ekomi_review_helpful_question ek-small-text-center ek-medium-text-left">
                                        <span>{l s='Did you find this review helpful?' mod='ekomiratingsandreviews'}</span>
                                        <input type="submit" class="ekomi_button ekomi_review_helpful_button" name="ekomi_answer"
                                               data-review-helpfulness="1" data-review-id="" value={l s='Yes' mod='ekomiratingsandreviews'}>
                                        <input type="submit" class="ekomi_button ekomi_review_helpful_button" name="ekomi_answer"
                                               data-review-helpfulness="0" data-review-id="" value={l s='No' mod='ekomiratingsandreviews'}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
        </div>
        <span class="ekomi_prc_out_of" style="display:none">{l s='out of' mod='ekomiratingsandreviews'}</span>
<span class="ekomi_prc_people_found" style="display:none">{l s='people found this review helpful' mod='ekomiratingsandreviews'}</span>
        {else}
            {$noReviewText|escape:'htmlall':'UTF-8'}
        {/if}
{/nocache}