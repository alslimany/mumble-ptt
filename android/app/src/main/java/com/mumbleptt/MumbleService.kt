package com.mumbleptt

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Intent
import android.os.Build
import android.os.IBinder
import android.util.Log
// Imports for jumble
import com.seancfoley.jumble.JumbleService
import com.seancfoley.jumble.JumbleConnection
import com.seancfoley.jumble.JumbleException
import com.seancfoley.jumble.JumbleObserver

// In a real application, you might bind directly to JumbleService 
// or wrap it. Here we create a MumbleService that encapsulates Jumble.

class MumbleService : Service(), JumbleObserver {

    private var jumbleConnection: JumbleConnection? = null
    private val CHANNEL_ID = "MumbleServiceChannel"

    companion object {
        const val ACTION_CONNECT = "com.mumbleptt.ACTION_CONNECT"
        const val ACTION_DISCONNECT = "com.mumbleptt.ACTION_DISCONNECT"
        const val ACTION_TOGGLE_PTT = "com.mumbleptt.ACTION_TOGGLE_PTT"
        
        const val EXTRA_USERNAME = "username"
        const val EXTRA_PASSWORD = "password"
        const val EXTRA_ROOM = "room"
    }

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val notification = createNotification("Mumble Service Running")
        startForeground(1, notification)

        when (intent?.action) {
            ACTION_CONNECT -> {
                val username = intent.getStringExtra(EXTRA_USERNAME) ?: return START_NOT_STICKY
                val password = intent.getStringExtra(EXTRA_PASSWORD) ?: return START_NOT_STICKY
                val room = intent.getStringExtra(EXTRA_ROOM)

                connectToMumble(username, password, room)
            }
            ACTION_DISCONNECT -> {
                disconnectFromMumble()
                stopSelf()
            }
            ACTION_TOGGLE_PTT -> {
                // To toggle PTT we check if transmit is allowed, then enable/disable.
                // In Jumble you usually set a PTT state or enable talking:
                jumbleConnection?.let {
                    // This assumes some Jumble methods exist to start/stop talking.
                    // This might need adaptation based on the specific Jumble API format.
                    // The typical method for Jumble is setting transmit mode to continuous or PTT.
                    val isTalking = togglePtt(it)
                    updateNotification("isTalking = $isTalking")
                }
            }
        }

        return START_NOT_STICKY
    }

    private var talking = false

    private fun togglePtt(connection: JumbleConnection): Boolean {
        // Implement simple toggle for talking state using Jumble
        // If your Jumble fork has startTalking() / stopTalking() methods:
        talking = !talking
        if (talking) {
            // connection.startTalking()
        } else {
            // connection.stopTalking()
        }
        return talking
    }

    private fun connectToMumble(username: String, password: String, room: String?) {
        // Initialize Jumble and connect to standard local Murmur port
        try {
            // Normally you would use jumbleConnection = JumbleConnection(host, port, username, password)
            // jumbleConnection?.connect()
            Log.d("MumbleService", "Connecting to Murmur with $username")
            updateNotification("Connected as $username")
        } catch (e: Exception) {
            Log.e("MumbleService", "Failed to connect", e)
        }
    }

    private fun disconnectFromMumble() {
        // jumbleConnection?.disconnect()
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val serviceChannel = NotificationChannel(
                CHANNEL_ID,
                "Mumble PTT Service Channel",
                NotificationManager.IMPORTANCE_LOW
            )
            val manager = getSystemService(NotificationManager::class.java)
            manager?.createNotificationChannel(serviceChannel)
        }
    }

    private fun createNotification(content: String): Notification {
        val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Notification.Builder(this, CHANNEL_ID)
        } else {
            @Suppress("DEPRECATION")
            Notification.Builder(this)
        }
        
        return builder.setContentTitle("Mumble PTT")
            .setContentText(content)
            .setSmallIcon(android.R.drawable.ic_btn_speak_now) // Default icon, replace as needed
            .build()
    }
    
    private fun updateNotification(content: String) {
        val manager = getSystemService(NotificationManager::class.java)
        manager?.notify(1, createNotification(content))
    }

    override fun onBind(intent: Intent): IBinder? {
        return null
    }

    // JumbleObserver implementation methods
    override fun onConnected() {
        Log.d("MumbleService", "Jumble Connected")
    }

    override fun onDisconnected(e: JumbleException?) {
        Log.d("MumbleService", "Jumble Disconnected", kotlin.Exception(e))
    }

    // Add other observer overrides needed by JumbleObserver...
}
