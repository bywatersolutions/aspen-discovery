<form id='{$title}Filter' action='/Search/Results' method="get" class="form-inline">
	<div class="facet-form">
		<div class="form-group">
			<label for="{$title}yearfrom" class='yearboxlabel sr-only control-label'>{$cluster.label} {translate text="from" isPublicFacing=true}</label>
			<input type="text" size="4" maxlength="4" class="yearbox form-control" placeholder="{translate text="from" inAttribute="true" isPublicFacing=true}" name="{$title}yearfrom" id="{$title}yearfrom" value="" />
		</div>
		<div class="form-group">
			<label for="{$title}yearto" class='yearboxlabel sr-only control-label'>{$cluster.label} {translate text="to" isPublicFacing=true}</label>
			<input type="text" size="4" maxlength="4" class="yearbox form-control" placeholder="{translate text="to" inAttribute="true" isPublicFacing=true}" name="{$title}yearto" id="{$title}yearto" value="" />
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

		{if $title == 'publishDate' || $title == 'publishDateSort'}
			<div id='yearDefaultLinks'>
				{assign var=thisyear value=$smarty.now|date_format:"%Y"}
				{translate text="Published in the last" isPublicFacing=true}<br/>
				<a onclick="$('#{$title}yearfrom').val('{$thisyear-1}');$('#{$title}yearto').val('');" href='javascript:void(0);'>{translate text="year" isPublicFacing=true}</a>
				&bullet; <a onclick="$('#{$title}yearfrom').val('{$thisyear-5}');$('#{$title}yearto').val('');" href='javascript:void(0);'>{translate text="5 years" isPublicFacing=true}</a>
				&bullet; <a onclick="$('#{$title}yearfrom').val('{$thisyear-10}');$('#{$title}yearto').val('');" href='javascript:void(0);'>{translate text="10 years" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
</form>
