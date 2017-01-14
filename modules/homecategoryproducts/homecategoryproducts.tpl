{foreach from=$categories item=category}
    {if isset($category->products) && $category->products}
        <h3 class="page-heading">{$category->name}</h3>
        {include file="$tpl_dir./product-list.tpl" class='homecategoryproducts product-slider' id='homecategoryproducts' products=$category->products}
    {else}
        <ul id="homecategoryproducts" class="homecategoryproducts">
            <li class="alert alert-info">{l s='No featured products at this time.' mod='homecategoryproducts'}</li>
        </ul>
    {/if}
{/foreach}