package com.turning_leaf_technologies.overdrive;

import java.sql.ResultSet

// TODO: Determine if these should all be val because this is indexed
data class OverDriveAvailabilityInfo(
    var id: Long,
    var libraryId: Long,
    var available: Boolean,
    var copiesOwned: Int,
    var copiesAvailable: Int,
    var numberOfHolds: Int,
    var availabilityType: String,
    var newAvailabilityLoaded: Boolean = false,
    var settingId: Long
)

// TODO: Add function for creating object from ResultSet
