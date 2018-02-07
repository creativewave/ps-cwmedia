{if $media}
<div id="cwmedia">
    <button type="button" title="{l s='Previous media' mod='cwmedia'}"><i class="icon-left-open-1"></i></button>
    <ul id="cwmedia-list">
        {foreach $media as $m}
        <li>
            {capture name='title'}{l s='View media fullscreen'}{/capture}
            {if 1 == $m.id_type}
            <a href="{$m.href}" title="{$smarty.capture.title}" rel="cwmedia" data-fancybox-type="iframe">
            {else}
            <a href="{$m.href}" title="{$smarty.capture.title}" rel="cwmedia">
            {/if}
                <img src="{$m.src}" alt="{$m.content}" width="300" height="{($m.height * 300 / $m.width)|round}">
            </a>
        </li>
        {/foreach}
    </ul>
    <button type="button" title="{l s='Next media' mod='cwmedia'}"><i class="icon-right-open-1"></i></button>
</div>
{/if}
