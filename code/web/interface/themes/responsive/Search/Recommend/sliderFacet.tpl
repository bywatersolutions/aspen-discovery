<form id="{$title}Filter" action="/Search/Results" method="get" class="form-inline">
	<div class="facet-form">
		{if $title == 'lexile_score'}
			<div id="lexile-range"></div>
		{/if}
		<div class="form-group">
			<label for="{$title}from" class="yearboxlabel sr-only control-label">{$cluster.label} from</label>
			<input type="text" size="4" maxlength="4" class="yearbox form-control" placeholder="from" name="{$title}from" id="{$title}from" value="">
		</div>
		<div class="form-group">
			<label for="{$title}to" class="yearboxlabel sr-only control-label">{$cluster.label} to</label>
			<input type="text" size="4" maxlength="4" class="yearbox form-control" placeholder="to" name="{$title}to" id="{$title}to" value="">
		</div>

		{* Preserve search terms and parameters *}
		{if $searchTerms}
			{foreach from=$searchTerms item=term}
				{if isset($term.lookfor)}
					<input type="hidden" name="lookfor" value="{$term.lookfor|escape}" />
				{/if}
				{if isset($term.index)}
					<input type="hidden" name="searchIndex" value="{$term.index|escape}" />
				{/if}
			{/foreach}
		{/if}

		{* Preserve filters (excluding the current facet) *}
		{if $restoredFilters}
			{foreach from=$restoredFilters item=filters key=facetLabel}
				{foreach from=$filters item=filter}
					{if $filter.field != $title}
						<input type="hidden" name="filter[]" value="{$filter.field|escape}:&quot;{$filter.value|escape}&quot;" />
					{/if}
				{/foreach}
			{/foreach}
		{/if}

		{* Preserve search source *}
		{if $searchSource}
			<input type="hidden" name="searchSource" value="{$searchSource|escape}" />
		{/if}

		<input type="submit" value="Go" id="{$title}GoButton" class="goButton btn btn-sm btn-primary">
	</div>
</form>