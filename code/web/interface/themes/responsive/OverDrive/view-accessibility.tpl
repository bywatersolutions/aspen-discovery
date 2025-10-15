{strip}
{if !empty($overdriveAccessibilityStatements)}
	<div class="overdrive-accessibility-statements" id="overdrive-accessibility-statements">
		<div class="overdrive-accessibility-intro" id="overdrive-accessibility-intro">
			{translate text="The publisher provides the following statement about the accessibility of the EPUB file supplied to OverDrive. Experiences may vary across reading systems. After borrowing the book, you may download the EPUB files through OverDrive to read in another reading system." isPublicFacing=true}
		</div>

		{foreach from=$overdriveAccessibilityStatements item=statement}
			<div class="overdrive-accessibility-statement" id="overdrive-accessibility-statement">
				{if !empty($statement.summaryStatement)}
					<div class="overdrive-accessibility-section" id="overdrive-accessibility-summary-section">
						<div class="overdrive-section-label" id="overdrive-accessibility-summary-label">
							<h4><strong>{translate text="Summary" isPublicFacing=true}</strong></h4>
						</div>
						<div class="overdrive-section-content" id="overdrive-accessibility-summary-content">
							{$statement.summaryStatement|escape}
						</div>
					</div>
				{/if}

				{if !empty($statement.waysOfReading)}
					<div class="overdrive-accessibility-section ways-of-reading" id="overdrive-ways-of-reading-section">
						<div class="overdrive-section-label" id="overdrive-ways-of-reading-label">
							<h4><strong>{translate text="Ways of Reading" isPublicFacing=true}</strong></h4>
						</div>
						<div class="overdrive-section-content" id="overdrive-ways-of-reading-content">
							{foreach from=$statement.waysOfReading item=wayItem key=wayIndex}
								<div class="overdrive-accessibility-item" id="overdrive-ways-of-reading-item-{$wayIndex}">
									<p>{translate text=$wayItem isPublicFacing=true}</p>
								</div>
							{/foreach}
						</div>
					</div>
				{/if}

				{if !empty($statement.conformance)}
					<div class="overdrive-accessibility-section conformance" id="overdrive-conformance-section">
						<div class="overdrive-section-label" id="overdrive-conformance-label">
							<h4><strong>{translate text="Conformance" isPublicFacing=true}</strong></h4>
						</div>
						<div class="overdrive-section-content" id="overdrive-conformance-content">
							{foreach from=$statement.conformance item=conformanceItem key=conformanceIndex}
								<div class="overdrive-accessibility-item" id="overdrive-conformance-item-{$conformanceIndex}">
									<p>{translate text=$conformanceItem isPublicFacing=true}</p>
								</div>
							{/foreach}
						</div>
					</div>
				{/if}

				{if !empty($statement.navigation)}
					<div class="overdrive-accessibility-section navigation" id="overdrive-navigation-section">
						<div class="overdrive-section-label" id="overdrive-navigation-label">
							<h4><strong>{translate text="Navigation" isPublicFacing=true}</strong></h4>
						</div>
						<div class="overdrive-section-content" id="overdrive-navigation-content">
							{foreach from=$statement.navigation item=navItem key=navIndex}
								<div class="overdrive-accessibility-item" id="overdrive-navigation-item-{$navIndex}">
									<p>{translate text=$navItem isPublicFacing=true}</p>
								</div>
							{/foreach}
						</div>
					</div>
				{/if}

				{if !empty($statement.richContent)}
					<div class="overdrive-accessibility-section rich-content" id="overdrive-rich-content-section">
						<div class="overdrive-section-label" id="overdrive-rich-content-label">
							<h4><strong>{translate text="Rich Content" isPublicFacing=true}</strong></h4>
						</div>
						<div class="overdrive-section-content" id="overdrive-rich-content-content">
							{foreach from=$statement.richContent item=richItem key=richIndex}
								<div class="overdrive-accessibility-item" id="overdrive-rich-content-item-{$richIndex}">
									<p>{translate text=$richItem isPublicFacing=true}</p>
								</div>
							{/foreach}
						</div>
					</div>
				{/if}

				{if !empty($statement.hazards)}
					<div class="overdrive-accessibility-section hazards" id="overdrive-hazards-section">
						<div class="overdrive-section-label" id="overdrive-hazards-label">
							<h4><strong>{translate text="Hazards" isPublicFacing=true}</strong></h4>
						</div>
						<div class="overdrive-section-content" id="overdrive-hazards-content">
							{foreach from=$statement.hazards item=hazard key=hazardIndex}
								<div class="overdrive-accessibility-item" id="overdrive-hazard-item-{$hazardIndex}">
									<p>{translate text=$hazard isPublicFacing=true}</p>
								</div>
							{/foreach}
						</div>
					</div>
				{/if}

				{if !empty($statement.legalConsiderations)}
					<div class="overdrive-accessibility-section legal-considerations" id="overdrive-legal-considerations-section">
						<div class="overdrive-section-label" id="overdrive-legal-considerations-label">
							<h4><strong>{translate text="Legal Considerations" isPublicFacing=true}</strong></h4>
						</div>
						<div class="overdrive-section-content" id="overdrive-legal-considerations-content">
							{foreach from=$statement.legalConsiderations item=legalConsideration key=legalIndex}
								<div class="overdrive-accessibility-item" id="overdrive-legal-consideration-item-{$legalIndex}">
									<p>{translate text=$legalConsideration isPublicFacing=true}</p>
								</div>
							{/foreach}
						</div>
					</div>
				{/if}

				{if !empty($statement.additionalInformation)}
					<div class="overdrive-accessibility-section additional-info" id="overdrive-additional-info-section">
						<div class="overdrive-section-label" id="overdrive-additional-info-label">
							<h4><strong>{translate text="Additional Information" isPublicFacing=true}</strong></h4>
						</div>
						<div class="overdrive-section-content" id="overdrive-additional-info-content">
							{foreach from=$statement.additionalInformation item=additionalInfo key=additionalIndex}
								<div class="overdrive-accessibility-item" id="overdrive-additional-info-item-{$additionalIndex}">
									<p>{translate text=$additionalInfo isPublicFacing=true}</p>
								</div>
							{/foreach}
						</div>
					</div>
				{/if}
			</div>
		{/foreach}
	</div>
{else}
	<div class="overdrive-accessibility-statements-empty" id="overdrive-accessibility-statements-empty">
		<div class="overdrive-no-accessibility-message" id="overdrive-no-accessibility-message">
			{translate text="No publisher statement provided" isPublicFacing=true}
		</div>
	</div>
{/if}
{/strip}
