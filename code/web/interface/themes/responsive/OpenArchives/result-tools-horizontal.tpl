{strip}
	{if $showComments || $showFavorites || $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if $showMoreInfo || $showComments || $showFavorites}
				{if $showMoreInfo !== false}
					<div class="btn-group btn-group-sm">
						<a href="{$openArchiveUrl}" class="btn btn-sm btn-tools" target="_blank" aria-label="{translate text='More Info' isPublicFacing=true} ({translate text='opens in new window' isPublicFacing=true})">{translate text="More Info" isPublicFacing=true}</a>
					</div>
				{/if}
				{if $showFavorites == 1 && (empty($offline) || $enableEContentWhileOffline)}
					<div class="btn-group btn-group-sm">
						<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'OpenArchives', '{$id|escape}');" class="btn btn-sm btn-tools addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
					</div>
				{/if}
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="OpenArchives/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}