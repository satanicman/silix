{$message}
<fieldset>
    <div class="panel">
        <div class="panel-heading">{l s="Select categories" mod="homecategoryproducts"}</div>
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-12">
                    <div class="form-group clearfix">
                        <label class="control-label col-lg-6 file_upload_label" for="categories">
                            <span class="label-tooltip" data-toggle="tooltip" title="{l s="Select categories" mod="homecategoryproducts"}">{l s="Select categories" mod="homecategoryproducts"}</span>
                        </label>
                        <div class="col-lg-6">
                            <div class="form-group clearfix">
                                <div class="col-lg-12">
                                    <select multiple="multiple" id="categories" name="categories[]" style="width: 300px; height: 160px;">
                                        {foreach from=$categories item=category}
                                            <option value="{$category.id_category}"{if isset($category.selected) && $category.selected} selected="selected"{/if}>{$category.name}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <button type="submit" value="1" id="submit_{$module_name}" name="submit_{$module_name}" class=""> <i class="process-icon-save"></i>
                    Сохранить
                </button>
            </div>
        </form>
    </div>
</fieldset>