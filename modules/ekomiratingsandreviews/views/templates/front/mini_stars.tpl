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
<div class="ekomi_average_rating">
    {if $starsAvg > 0}
        <div class="ekomi_prc_widget mini_stars_counter">
            <div class="ekomi_stars_wrap">
                <div class="ekomi_stars_gold" style="width:{$starsAvg * 20|escape:'htmlall':'UTF-8'}%"></div>
            </div>
        </div>
        <meta content="{$productName|escape:'htmlall':'UTF-8'}" itemprop="name">
        <div class="ekomi_total_reviews_wrap">
            <span class="total_reviews">({$reviewsCount|escape:'htmlall':'UTF-8'})</span>
        </div>
    {/if}
</div>
{/nocache}