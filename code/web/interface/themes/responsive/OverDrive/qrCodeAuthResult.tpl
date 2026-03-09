{strip}
	<div id="main-content" class="col-md-12">
		<h1>{if !empty($qrResultTitle)}{$qrResultTitle}{else}{$readerName}{/if}</h1>
		<div class="alert {if $qrResultSuccess}alert-success{else}alert-danger{/if}">
			{$qrResultMessage}
		</div>
		<p>
			<a href="/MyAccount/OverDriveOptions" class="btn btn-primary">
				{translate text="Return to %1% Options" 1=$readerName isPublicFacing=true}
			</a>
		</p>
	</div>
{/strip}
