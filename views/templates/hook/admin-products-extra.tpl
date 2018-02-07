<div id="product-media" class="panel product-tab">
    <input type="hidden" name="submitted_tabs[]" value="{$tab_name}" />
    <h3>{l s='Media' mod='cwmedia'}</h3>
    {include file="controllers/products/multishop/check_fields.tpl" product_tab=$tab_name}
    <div class="form-group">
        <div class="col-lg-1">
            <span class="pull-right">
                {include file="controllers/products/multishop/checkbox.tpl" field="cwmedia" type="default"}
            </span>
        </div>
        <label class="control-label col-lg-3" for="add_media">
            <span
                class="label-tooltip"
                data-toggle="tooltip"
                title="{l s=$hints mod='cwbundle'}">
                {l s='Set product media' mod='cwmedia'}
            </span>
        </label>
        <div class="col-lg-8">
            <div class="form-group">
                <button type="button" id="open-library" class="btn btn-info">
                    <i class="icon-folder-o"></i> {l s='Select media' mod='cwmedia'}
                </button>
                <button type="button" id="open-link" class="btn btn-danger">
                    <i class="icon-youtube"></i> {l s='Link Youtube video' mod='cwmedia'}
                </button>
                <noscript>{l s='Javascript must be enabled to use these buttons.' mod='cwmedia'}</noscript>
            </div>
            {$uploader}
            <table id="media-list" class="table">
                <thead>
                    <tr class="nodrag nodrop">
                        <th class="fixed-width-lg hidden" aria-hidden="true"></th>
                        <th class="fixed-width-lg"><span class="title_box">{l s='Media' mod='cwmedia'}</span></th>
                        <th class="fixed-width-xs"><span class="title_box">{l s='Position' mod='cwmedia'}</span></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$media key=position item=m}
                    <tr id="id-media-{$m.id_media}">
                        <td class="hidden">
                            <input type="hidden" name="media[{$position}][id_media]" value="{$m.id_media}">
                        </td>
                        <td>
                            <img
                                src="{$m.src}"
                                alt="{l s='Thumbnail for media ID %d' sprintf=$m.id_media mod='cwmedia'}"
                                class="img-thumbnail"
                                width="300"
                                height="{($m.height * 300 / $m.width)|round}"
                            >
                        </td>
                        <td id="position-id-media-{$m.id_media}" class="center">{$position + 1}</td>
                        <td>
                            <button type="button" id="remove-id-media-{$m.id_media}" class="btn btn-default">
                                <i class="icon-trash text-danger"></i>
                            </button>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel' mod='cwmedia'}</a>
        <button name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save' mod='cwmedia'}</button>
        <button name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay' mod='cwmedia'}</button>
    </div>
</div>

<script>var cwmedia = '{$json}'</script>
<script>CW.Media.init()</script>
