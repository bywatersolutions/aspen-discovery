package com.turning_leaf_technologies.overdrive;

class OverDriveSetting(
    var readerName: String,
    var clientSecret: String,
    val clientKey: String,
    val accountId: String,
    val productsKey: String,
    val runFullUpdate: Boolean,
    val lastUpdateOfChangedRecords: Long,
    val allowLargeDeletes: Boolean,
    val enableRequestLogging: Boolean,
    val numRetriesOnError: Int,
    val deletionCheckHour: Int,
    val productsToUpdate: MutableSet<String> = mutableSetOf(),
    val productsToUpdateNextTime: MutableSet<String> = mutableSetOf()
)
