{$message}
<fieldset>
    <div class="panel">
        <div class="panel-heading">Импорт товаров из XML</div>
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-12">
                    <div class="form-group clearfix">
                        <label class="control-label col-lg-6 file_upload_label">
                            <span class="label-tooltip" data-toggle="tooltip" title="{l s="Loading type" mod="moreproducts"}">{l s="Loading type" mod="moreproducts"}</span>
                        </label>
                        <div class="col-lg-6">
                            <div class="form-group clearfix">
                                <div class="col-lg-12">
                                    <select name="type" id="type" class="front-controller">
                                        <option value="1"{if $type == 1} selected="selected"{/if}>{l s="Button" mod="moreproducts"}</option>
                                        <option value="2"{if $type == 2} selected="selected"{/if}>{l s="Scroll" mod="moreproducts"}</option>
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