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
class MumbleServiceIntegrationTest {

    @Test
    fun testMumbleServiceStart() {
        val context = ApplicationProvider.getApplicationContext<Context>()

        // Simulate MainActivity starting the service
        val serviceIntent = Intent(context, MumbleService::class.java)
        serviceIntent.action = MumbleService.ACTION_CONNECT
        serviceIntent.putExtra(MumbleService.EXTRA_USERNAME, "testUser")
        serviceIntent.putExtra(MumbleService.EXTRA_PASSWORD, "testPass")
        serviceIntent.putExtra(MumbleService.EXTRA_ROOM, "testRoom")
        
        val compName = context.startService(serviceIntent)
        assertNotNull("Service component shouldn't be null", compName)
        
        // PTT Toggle Check
        val pttIntent = Intent(context, MumbleService::class.java)
        pttIntent.action = MumbleService.ACTION_TOGGLE_PTT
        
        val pttCompName = context.startService(pttIntent)
        assertNotNull("PTT Toggle Service call shouldn't be null", pttCompName)
    }
}
