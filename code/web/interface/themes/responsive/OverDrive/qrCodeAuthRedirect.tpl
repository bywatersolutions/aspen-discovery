{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="OverDrive Authentication Complete" isPublicFacing=true}</h1>
		<div class="alert alert-success">
			{if $autoTriggerCheckout}
				{translate text="Completing your checkout..." isPublicFacing=true}
			{elseif $autoTriggerHold}
				{translate text="Placing your hold..." isPublicFacing=true}
			{/if}
		</div>
		<p id="statusMessage">{translate text="Redirecting back to the item..." isPublicFacing=true}</p>
	</div>

	<script type="text/javascript">
		{literal}
		$(() => {
			const recordId = '{/literal}{$recordId|escape:"javascript"}{literal}';
			const returnUrl = '{/literal}{$returnUrl|escape:"javascript"}{literal}';
			const action = '{/literal}{if $autoTriggerCheckout}checkout{elseif $autoTriggerHold}hold{/if}{literal}';
			const separator = returnUrl.includes('?') ? '&' : '?';
			const targetUrl = `${returnUrl}${separator}autoAction=${action}&autoRecordId=${encodeURIComponent(recordId)}`;
			window.location.href = targetUrl;
		});
		{/literal}
	</script>
{/strip}
