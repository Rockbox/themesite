<p class="breadcrumbs">
{if $grandparent}
    <a href="{$grandparent|regex_replace:"/\|.*/":""}">
    {$grandparent|regex_replace:"/.*\|/":""}</a>
    &raquo; 
{/if}
{if $parent}
    <a href="{$parent|regex_replace:"/\|.*/":""}">
    {$parent|regex_replace:"/.*\|/":""}</a>
    &raquo; 
{/if}
{$self}
</p>
