{strip}
	{if $showEmailThis == 1 || $showShareOnExternalSites == 1}
	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">{translate text="SHARE" isPublicFacing=true}</span>
		{if !empty($showShareOnX)}
			<a href="https://twitter.com/intent/tweet?text={$recordDriver->getTitle()|urlencode}+{$url}/{$recordDriver->getLinkUrl()|escape:'url'}" target="_blank" title="{translate text="Share on X" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Share on X" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})">
				<i class="fa-brands fa-square-x-twitter fa-2x fa-fw"></i>
			</a>
		{/if}
		{if !empty($showShareOnFacebook)}
			<a href="http://www.facebook.com/sharer/sharer.php?u={$url}/{$recordDriver->getLinkUrl()|escape:'url'}" target="_blank" title="{translate text="Share on Facebook" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Share %1%, by %2% on Facebook" 1=$recordDriver->getTitle()|escapeCSS 2=$recordDriver->getTitle()|escapeCSS inAttribute=true isPublicFacing=true translateParameters=false} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})">
				<i class="fa-brands fa-square-facebook fa-2x fa-fw"></i>
			</a>
		{/if}
		{if !empty($showShareOnPinterest)}
			<a href="http://www.pinterest.com/pin/create/button/?url={$url}/{$recordDriver->getLinkUrl()}&media={$url}{$recordDriver->getBookcoverUrl('large')}&description=Pin%20on%20Pinterest" target="_blank" title="{translate text="Pin on Pinterest" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Pin %1%, by %2% on Pinterest" 1=$recordDriver->getTitle()|escapeCSS 2=$recordDriver->getTitle()|escapeCSS inAttribute=true isPublicFacing=true translateParameters=false} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})">
				<i class="fa-brands fa-square-pinterest fa-2x fa-fw"></i>
			</a>
		{/if}
		{if !empty($showShareOnLink)}
			<a href="javascript:void(0);" onclick="navigator.clipboard.writeText('{$url}/{$recordDriver->getLinkUrl()}');AspenDiscovery.showMessage('{translate text="Link Copied" isPublicFacing=true inAttribute=true}', '{translate text="The link for this has been copied to the clipboard. You can now share it with others by pasting the link." isPublicFacing=true inAttribute=true}', true);" title="{translate text="Share a link to this page" inAttribute=true isPublicFacing=true}">
				<i class="fas fa-share-alt-square fa-2x fa-fw"></i>
			</a>
		{/if}
	</div>
	{/if}
{/strip}
