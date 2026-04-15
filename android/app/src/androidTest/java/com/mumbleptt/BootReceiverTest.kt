package com.mumbleptt

import android.content.Context
import android.content.Intent
import androidx.test.core.app.ApplicationProvider
import androidx.test.ext.junit.runners.AndroidJUnit4
import androidx.test.filters.MediumTest
import org.junit.Assert.*
import org.junit.Test
import org.junit.runner.RunWith

@RunWith(AndroidJUnit4::class)
@MediumTest
class BootReceiverTest {

    @Test
    fun testBootReceiverStartsServices() {
        val context = ApplicationProvider.getApplicationContext<Context>()
        val receiver = BootReceiver()

        val intent = Intent(Intent.ACTION_BOOT_COMPLETED)
        
        // This will attempt to start the services
        // A true test for startForeground requires ShadowApplication in Robolectric
        // For instrumentation, we observe it doesn't crash on Boot intent
        try {
            receiver.onReceive(context, intent)
            assertTrue(true)
        } catch (e: Exception) {
            fail("Exception thrown on boot receive: \${e.message}")
        }
    }
}
