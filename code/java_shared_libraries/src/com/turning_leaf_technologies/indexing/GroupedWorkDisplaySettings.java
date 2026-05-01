package com.turning_leaf_technologies.indexing;

public class GroupedWorkDisplaySettings {
	private long id;
	private String name;

	private boolean includeOnlineMaterialsInAvailableToggle  = true;
	private boolean includeAllRecordsInShelvingFacets;
	private boolean includeAllRecordsInDateAddedFacets;
	private boolean includeEContentInShelvingLocations;
	private boolean baseAvailabilityToggleOnLocalHoldingsOnly = false;
	
	// Boost Factors
	private boolean applyNumberOfHoldingsBoost;
	private boolean limitBoosts;
	private int maxTotalBoost;
	private int maxPopularityBoost;
	private int maxFormatBoost;
	private int maxHoldingsBoost;

	public long getId() {
		return id;
	}

	public void setId(long id) {
		this.id = id;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public boolean isIncludeAllRecordsInShelvingFacets() {
		return includeAllRecordsInShelvingFacets;
	}

	public boolean includeEContentInShelvingLocations() {
		return includeEContentInShelvingLocations;
	}

	void setIncludeAllRecordsInShelvingFacets(boolean includeAllRecordsInShelvingFacets) {
		this.includeAllRecordsInShelvingFacets = includeAllRecordsInShelvingFacets;
	}

	void setincludeEContentInShelvingLocations(boolean includeEContentInShelvingLocations) {
		this.includeEContentInShelvingLocations = includeEContentInShelvingLocations;
	}

	public boolean isIncludeAllRecordsInDateAddedFacets() {
		return includeAllRecordsInDateAddedFacets;
	}

	void setIncludeAllRecordsInDateAddedFacets(boolean includeAllRecordsInDateAddedFacets) {
		this.includeAllRecordsInDateAddedFacets = includeAllRecordsInDateAddedFacets;
	}

	@SuppressWarnings("BooleanMethodIsAlwaysInverted")
	public boolean isBaseAvailabilityToggleOnLocalHoldingsOnly() {
		return baseAvailabilityToggleOnLocalHoldingsOnly;
	}

	void setBaseAvailabilityToggleOnLocalHoldingsOnly(boolean baseAvailabilityToggleOnLocalHoldingsOnly) {
		this.baseAvailabilityToggleOnLocalHoldingsOnly = baseAvailabilityToggleOnLocalHoldingsOnly;
	}

	public boolean isIncludeOnlineMaterialsInAvailableToggle() {
		return includeOnlineMaterialsInAvailableToggle;
	}

	void setIncludeOnlineMaterialsInAvailableToggle(boolean includeOnlineMaterialsInAvailableToggle) {
		this.includeOnlineMaterialsInAvailableToggle = includeOnlineMaterialsInAvailableToggle;
	}

	// Getters
	public boolean getApplyNumberOfHoldingsBoost() {
		return applyNumberOfHoldingsBoost;
	}

	public boolean getLimitBoosts() {
		return limitBoosts;
	}

	public int getMaxTotalBoost() {
		return maxTotalBoost;
	}

	public int getMaxPopularityBoost() {
		return maxPopularityBoost;
	}

	public int getMaxFormatBoost() {
		return maxFormatBoost;
	}

	public int getMaxHoldingsBoost() {
		return maxHoldingsBoost;
	}

	// Setters
	public void setApplyNumberOfHoldingsBoost(boolean applyNumberOfHoldingsBoost) {
		this.applyNumberOfHoldingsBoost = applyNumberOfHoldingsBoost;
	}

	public void setLimitBoosts(boolean limitBoosts) {
		this.limitBoosts = limitBoosts;
	}

	public void setMaxTotalBoost(int maxTotalBoost) {
		this.maxTotalBoost = maxTotalBoost;
	}

	public void setMaxPopularityBoost(int maxPopularityBoost) {
		this.maxPopularityBoost = maxPopularityBoost;
	}

	public void setMaxFormatBoost(int maxFormatBoost) {
		this.maxFormatBoost = maxFormatBoost;
	}

	public void setMaxHoldingsBoost(int maxHoldingsBoost) {
		this.maxHoldingsBoost = maxHoldingsBoost;
	}

}

