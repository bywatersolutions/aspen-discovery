<form id='{$title}Filter' action='/Search/Results' method="get" class="form-horizontal-narrow">
	<div class="facet-form">
		<div class="form-group">
			<label class="control-label" for="{$title}Start">{translate text="From" isPublicFacing=true}</label>
			<input type="date" name="{$title}Start" id="{$title}Start" placeholder="mm/dd/yyyy" class="form-control" size="10" min="{$smarty.now|date_format:"%Y-%m-%d"}" {if !empty($maxEventDate)}max="{$maxEventDate|date_format:"%Y-%m-%d"}"{/if} {if !empty($cluster.start)}value="{$cluster.start}"{/if}>
		</div>
		<div class="form-group">
			<label class="control-label" for="{$title}End">{translate text="To" isPublicFacing=true}</label>
			<input type="date" name="{$title}End" id="{$title}End" placeholder="mm/dd/yyyy" class="form-control" size="10" min="{$smarty.now|date_format:"%Y-%m-%d"}" {if !empty($maxEventDate)}max="{$maxEventDate|date_format:"%Y-%m-%d"}"{/if} {if !empty($cluster.end)}value="{$cluster.end}"{/if}>
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

		<input type="submit" value="{translate text="Go" inAttribute="true" isPublicFacing=true}" class="goButton btn btn-sm btn-primary" />
		<br/>&nbsp;
	</div>
</form>