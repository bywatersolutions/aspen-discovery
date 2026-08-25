{strip}
	{* Reusable inline date-range picker (Cally web components) with labelled
	   From/To date inputs — native entry, ISO values, no hidden mirrors. Wired by
	   the DateRangePicker module named in data-picker-module; styled by
	   css/date-range-picker.less, compiled into main.css.*}
	{assign var="drpId" value=$rangeId|default:'date-range-picker'}
	<div class="date-range-picker" data-picker-module="/interface/themes/responsive/js/aspen/date-range-picker.js?v={$aspenVersion|urlencode}.{$cssJsCacheCounter}">
		<div class="date-range-picker-fields form-group">
			<div class="date-range-picker-field">
				<label class="control-label" for="{$drpId}-start">{if !empty($startLabel)}{translate text=$startLabel isPublicFacing=true}{else}{translate text="From" isPublicFacing=true}{/if}</label>
				<input type="date" id="{$drpId}-start" class="form-control required" data-date-role="start"{if !empty($startName)} name="{$startName}"{/if} value="{$startValue|default:''}">
			</div>
			<span class="date-range-picker-sep" aria-hidden="true">&#8594;</span>
			<div class="date-range-picker-field">
				<label class="control-label" for="{$drpId}-end">{if !empty($endLabel)}{translate text=$endLabel isPublicFacing=true}{else}{translate text="To" isPublicFacing=true}{/if}</label>
				<input type="date" id="{$drpId}-end" class="form-control required" data-date-role="end"{if !empty($endName)} name="{$endName}"{/if} value="{$endValue|default:''}">
			</div>
		</div>

		<calendar-range id="{$drpId}" months="{$months|default:1}"{if !empty($locale)} locale="{$locale}"{/if}>
			<svg slot="previous" aria-label="{translate text='Previous month' isPublicFacing=true}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M15.75 19.5 8.25 12l7.5-7.5"></path></svg>
			<svg slot="next" aria-label="{translate text='Next month' isPublicFacing=true}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"></path></svg>
			{section name=month loop=$months|default:1}
				<calendar-month{if $smarty.section.month.index > 0} offset="{$smarty.section.month.index}"{/if}></calendar-month>
			{/section}
		</calendar-range>
	</div>
{/strip}
