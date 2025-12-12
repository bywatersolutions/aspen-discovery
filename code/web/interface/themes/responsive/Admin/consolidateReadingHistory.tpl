{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if isset($results)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert {if !empty($results.success)}alert-success{else}alert-danger{/if}">
					{$results.message}
				</div>
			</div>
		</div>
	{elseif isset($error)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-danger">
					{$error}
				</div>
			</div>
		</div>
	{/if}
	<div class="row">
		<div class="col-xs-12">
			<div class="alert alert-info">{translate text="Enter the barcode for the user whose reading history you want to consolidate. To consolidate for all patrons with a reading history, enter \"all\". Consolidation will merge any consecutive checkouts of the same title as well as checkouts that overlap or ar duplicated." isAdminFacing=true}</div>
		</div>
	</div>
	<form name="consolidateReadingHistory" method="post" enctype="multipart/form-data" class="form-horizontal">
		<fieldset>
			<div class="row form-group">
				<label for="barcodes" class="col-sm-2 control-label">{translate text='Barcode' isAdminFacing=true}</label>
				<div class="col-sm-10">
					<input type="text" name="barcode" id="barcode" class="form-control" value="{if !empty($barcode)}{$barcode}{/if}"/>
				</div>
			</div>

			<div class="form-group">
				<div class="controls col-sm-offset-2 col-sm-2">
					<input type="submit" name="submit" value="{translate text="Consolidate Reading History" inAttribute=true isAdminFacing=true}" class="btn btn-primary">
				</div>
			</div>
		</fieldset>
	</form>
{/strip}
