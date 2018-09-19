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
{foreach from=$reviews item=review}
<section itemprop="review" itemscope itemtype="http://schema.org/Review">
            <span itemprop="author" itemscope itemtype="http://schema.org/Organization">
                <meta itemprop="name" content="eKomi">
            </span>
    <div class="ekomi_review ek-row">
        <div class="ek-large-4 ek-column ek-text-center">
            <div class="ekomi_stars_container" itemprop="reviewRating" itemscope
                 itemtype="http://schema.org/Rating">
                <meta itemprop="worstRating" content="1">
                <meta itemprop="ratingValue" content="{$review.stars|escape:'htmlall':'UTF-8'}">
                <meta itemprop="bestRating" content="5">

                <div class="ekomi_stars_wrap">
                    <div class="ekomi_stars_gold" style="width:{$review.stars * 20|escape:'htmlall':'UTF-8'}%"></div>
                </div>
            </div>
            <span class="ekomi_review_time" itemprop="datePublished"
                  content="{$review.timestamp|date_format:"%d.%m.%Y %H:%M:%S"|escape:'htmlall':'UTF-8'}">
                    {$review.timestamp|date_format:"%d.%m.%Y %H:%M:%S"|escape:'htmlall':'UTF-8'}
                </span>
        </div>
        <div class="ek-large-8 ek-column">
            <p class="ekomi_review_text" itemprop="reviewBody">{$review.review_comment|escape:'htmlall':'UTF-8'}</p>
            <div class="ekomi_review_helpful_button_wrapper ">

                    <span class="ekomi_review_helpful_info ek-small-text-center ek-medium-text-left"
                          style="display:block;">
                        {if ($review.helpful + $review.nothelpful) gt 0}
                            {$review.helpful|escape:'htmlall':'UTF-8'} {l s='out of' mod='ekomiratingsandreviews'} {$review.helpful + $review.nothelpful} {l s='people found this review helpful' mod='ekomiratingsandreviews'}.
                        {/if}
                    </span>

                <div class="ekomi_review_helpful_thankyou ek-small-text-center ek-medium-text-left"
                     style="display:none;">
                    {l s='Thank you for your vote' mod='ekomiratingsandreviews'}!
                </div>
                <div class="ekomi_review_helpful_question ek-small-text-center ek-medium-text-left">
                    <span>{l s='Did you find this review helpful' mod='ekomiratingsandreviews'}?</span>
                    <input type="button" class="ekomi_button ekomi_review_helpful_button" name="ekomi_answer"
                           data-review-helpfulness="1" data-review-id="{$review.id_ekomi_ratings_and_reviews|escape:'htmlall':'UTF-8'}" value={l s='Yes' mod='ekomiratingsandreviews'}>
                    <input type="button" class="ekomi_button ekomi_review_helpful_button" name="ekomi_answer"
                           data-review-helpfulness="0" data-review-id="{$review.id_ekomi_ratings_and_reviews|escape:'htmlall':'UTF-8'}" value={l s='No' mod='ekomiratingsandreviews'}>
                </div>
            </div>
        </div>
    </div>
</section>
{/foreach}

{/nocache}