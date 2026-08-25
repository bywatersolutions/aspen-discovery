{strip}
<div class="form-group">
	<label class="control-label" for="startDate">{translate text="Start Date" isPublicFacing=true}</label>
	<input type="date" name="startDate" id="startDate" class="form-control required" min="{$smarty.now|date_format:"%Y-%m-%d"}" value="{$startDate|default:''}">
</div>

<div class="form-group">
	<label class="control-label" for="endDate">{translate text="End Date" isPublicFacing=true}</label>
	<input type="date" name="endDate" id="endDate" class="form-control required" min="{$smarty.now|date_format:"%Y-%m-%d"}" value="{$endDate|default:''}">
</div>

{include file='Record/pickup-location-select.tpl'}

{/strip}
