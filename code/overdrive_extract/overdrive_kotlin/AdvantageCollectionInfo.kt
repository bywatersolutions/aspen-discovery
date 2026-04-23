package com.turning_leaf_technologies.overdrive;

data class AdvantageCollectionInfo(
    var advantageId: Int,
    var collectionToken: String,
    val aspenLibraryIds: MutableSet<Long> = mutableSetOf(),
    var name: String
)