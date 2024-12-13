{strip}
<div style="display: block; text-align: center; height: 66svh">
	{if (empty($slideInfo->overlay_text) && empty($slideInfo->overlay_images)) }
		<img class="img-responsive" style="max-height: 100%" src="/year_in_review/images/{$slideInfo->background}" alt="Year in review background"/>
	{else}
		<img class="img-responsive" style="max-height: 100%" src="/MyAccount/AJAX?method=getYearInReviewSlideImage&slide={$slideNumber}" alt="Year in review background"/>
	{/if}
</div>
{/strip}