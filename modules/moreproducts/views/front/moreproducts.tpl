<form id="moreProductsForm" method="POST">
	{if isset($id_category) && $id_category}
		<input type="hidden" value="{$id_category}" name="id_category" id="moreProduct_id_category">
	{elseif isset($search_query) && $search_query}
		<input type="hidden" value="{$search_query}" name="search_query" id="moreProduct_search_query">
	{/if}
	<input type="hidden" value="{$p}" name="p" id="moreProduct_p">
	<input type="hidden" value="{$n}" name="n" id="moreProduct_n">
	<input type="hidden" value="{$orderby}" name="orderby" id="moreProduct_orderby">
	<input type="hidden" value="{$orderway}" name="orderway" id="moreProduct_orderway">
	<input type="hidden" value="{$show_type}" name="type" id="moreProduct_type">
	<input type="hidden" value="0" name="type" id="moreProduct_last">
	<div id="moreProduct_ajax_loader" style="display: none;">
		<p>
			<img src="{$img_ps_dir}loader.gif" alt="" />
			<br />{l s='Loading...' mod='moreproducts'}
		</p>
	</div>
	{if $show_type != 2}
		<button id="moreProducts" class="btn btn-default{if $n > $nb_products} hidden{/if}">{l s="Show %d more items" mod="moreproducts" sprintf=$per_page}</button>
	{/if}
</form>