{strip}
{assign var=dateParts value="-"|explode:$propValue}
<div class="row">
	<div class="col-tn-4 col-md-2">
		<label for="{$propName}_month" class="control-label">{translate text="Month / Day" isAdminFacing=true}</label>
	</div>
	<div class="col-tn-4 col-md-3 col-lg-2">
		<select name='{$propName}_month' id='{$propName}_month' class="form-control" aria-label="Month">
			<option value="01" {if $dateParts.0 == '01'}selected='selected'{/if}>{translate text="January" isAdminFacing=true inAttribute=true}</option>
			<option value="02" {if $dateParts.0 == '02'}selected='selected'{/if}>{translate text="February" isAdminFacing=true inAttribute=true}</option>
			<option value="03" {if $dateParts.0 == '03'}selected='selected'{/if}>{translate text="March" isAdminFacing=true inAttribute=true}</option>
			<option value="04" {if $dateParts.0 == '04'}selected='selected'{/if}>{translate text="April" isAdminFacing=true inAttribute=true}</option>
			<option value="05" {if $dateParts.0 == '05'}selected='selected'{/if}>{translate text="May" isAdminFacing=true inAttribute=true}</option>
			<option value="06" {if $dateParts.0 == '06'}selected='selected'{/if}>{translate text="June" isAdminFacing=true inAttribute=true}</option>
			<option value="07" {if $dateParts.0 == '07'}selected='selected'{/if}>{translate text="July" isAdminFacing=true inAttribute=true}</option>
			<option value="08" {if $dateParts.0 == '08'}selected='selected'{/if}>{translate text="August" isAdminFacing=true inAttribute=true}</option>
			<option value="09" {if $dateParts.0 == '09'}selected='selected'{/if}>{translate text="September" isAdminFacing=true inAttribute=true}</option>
			<option value="10" {if $dateParts.0 == '10'}selected='selected'{/if}>{translate text="October" isAdminFacing=true inAttribute=true}</option>
			<option value="11" {if $dateParts.0 == '11'}selected='selected'{/if}>{translate text="November" isAdminFacing=true inAttribute=true}</option>
			<option value="12" {if $dateParts.0 == '12'}selected='selected'{/if}>{translate text="December" isAdminFacing=true inAttribute=true}</option>
		</select>
	</div>
	<div class="col-tn-4 col-md-3 col-lg-2">
		<input type='number' name='{$propName}_day' id='{$propName}_day' value='{$dateParts.1}' maxLength='2' size='2' min="1" max="31" class="form-control" aria-label="Day"/>
	</div>
</div>
{/strip}
