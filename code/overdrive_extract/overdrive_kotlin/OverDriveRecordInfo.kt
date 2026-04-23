package com.turning_leaf_technologies.overdrive;

class OverDriveRecordInfo(
    var hasChanges: Boolean = false,
    var isNew: Boolean = false,
    var databaseId: Long = -1,
    val collections: MutableSet<AdvantageCollectionInfo> = mutableSetOf()
) {
    var id: String = ""
        set(value) {
            field = value.lowercase()
        }
}