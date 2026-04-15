package com.mumbleptt

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.os.Build

class BootReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action == Intent.ACTION_BOOT_COMPLETED || intent.action == Intent.ACTION_LOCKED_BOOT_COMPLETED) {
            val mumbleIntent = Intent(context, MumbleService::class.java)
            val locationIntent = Intent(context, LocationService::class.java)

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                context.startForegroundService(mumbleIntent)
                context.startForegroundService(locationIntent)
            } else {
                context.startService(mumbleIntent)
                context.startService(locationIntent)
            }
        }
    }
}
