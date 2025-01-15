<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>{$title|escape: 'html'}</title>
	</head>
	<body>
		{if $showTitleOnPage}
			<h1>{$title|escape: 'html'}</h1>
		{/if}
		<div id="content">
			{$templateContent}
		</div>
	</body>
</html>
