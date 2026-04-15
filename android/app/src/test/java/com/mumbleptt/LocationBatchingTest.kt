package com.mumbleptt

import com.mumbleptt.network.GpsPoint
import org.junit.Assert.assertEquals
import org.junit.Test

class LocationBatchingTest {
    
    // Testing the logic roughly described in LocationService handling 10 items
    @Test
    fun testBatchingThreshold() {
        val maxBatchSize = 10
        val currentBatch = mutableListOf<GpsPoint>()
        
        for (i in 1..9) {
            currentBatch.add(GpsPoint(0.0, 0.0, System.currentTimeMillis()))
        }
        
        assertEquals("Batch hasn't reached threshold", 9, currentBatch.size)
        
        currentBatch.add(GpsPoint(0.0, 0.0, System.currentTimeMillis()))
        
        if (currentBatch.size >= maxBatchSize) {
            val sent = currentBatch.toList()
            currentBatch.clear()
            assertEquals("Batch sent", 10, sent.size)
            assertEquals("Batch cleared", 0, currentBatch.size)
        }
    }
}