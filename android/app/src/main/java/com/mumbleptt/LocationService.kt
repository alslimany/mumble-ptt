package com.mumbleptt

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.IBinder
import android.os.Looper
import android.util.Log
import androidx.core.app.NotificationCompat
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationCallback
import com.google.android.gms.location.LocationRequest
import com.google.android.gms.location.LocationResult
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.mumbleptt.network.BackendApi
import com.mumbleptt.network.GpsPoint
import com.mumbleptt.network.GpsReportRequest
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.CopyOnWriteArrayList

class LocationService : Service() {

    private val CHANNEL_ID = "LocationServiceChannel"
    private lateinit var fusedLocationClient: FusedLocationProviderClient
    private lateinit var locationCallback: LocationCallback
    private val locationBatch = CopyOnWriteArrayList<GpsPoint>()
    private val job = Job()
    private val scope = CoroutineScope(Dispatchers.IO + job)
    private lateinit var backendApi: BackendApi
    private lateinit var registrationManager: DeviceRegistrationManager
    private var trackingIntervalMillis = 60000L

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
        
        fusedLocationClient = LocationServices.getFusedLocationProviderClient(this)
        
        val retrofit = Retrofit.Builder()
            .baseUrl("http://10.0.2.2")
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            
        backendApi = retrofit.create(BackendApi::class.java)
        registrationManager = DeviceRegistrationManager(this, backendApi)
        
        locationCallback = object : LocationCallback() {
            override fun onLocationResult(p0: LocationResult) {
                p0 ?: return
                for (location in p0.locations) {
                    handleNewLocation(location.latitude, location.longitude, location.time)
                }
            }
        }
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val notification = createNotification("Location tracking active")
        startForeground(2, notification)

        startLocationUpdates()
        startPeriodicIntervalSync()
        startBatchSender()

        return START_STICKY
    }

    private fun handleNewLocation(lat: Double, lng: Double, time: Long) {
        val point = GpsPoint(lat, lng, time)
        locationBatch.add(point)
        if (locationBatch.size >= 10) {
            sendBatch()
        }
    }

    private fun startLocationUpdates() {
        try {
            val locationRequest = LocationRequest.Builder(Priority.PRIORITY_HIGH_ACCURACY, trackingIntervalMillis)
                .setMinUpdateIntervalMillis(trackingIntervalMillis / 2)
                .build()

            fusedLocationClient.requestLocationUpdates(
                locationRequest,
                locationCallback,
                Looper.getMainLooper()
            )
        } catch (e: SecurityException) {
            Log.e("LocationService", "Missing location permissions", e)
        }
    }
    
    private fun startPeriodicIntervalSync() {
        scope.launch {
            while (isActive) {
                try {
                    val result = registrationManager.registerDevice()
                    // Assuming interval comes back to config, we could restart location update.
                    Log.d("LocationService", "Synced API registration")
                } catch (e: Exception) {
                    Log.e("LocationService", "Sync error", e)
                }
                delay(5 * 60 * 1000) // 5 mins
            }
        }
    }

    private fun startBatchSender() {
        scope.launch {
            while (isActive) {
                delay(10000) // 10 sec
                if (locationBatch.isNotEmpty()) {
                    sendBatch()
                }
            }
        }
    }

    private fun sendBatch() {
        val currentBatch = ArrayList(locationBatch)
        locationBatch.clear()

        scope.launch {
            try {
                val req = GpsReportRequest(registrationManager.getDeviceId(), currentBatch)
                backendApi.reportGps(req)
                Log.d("LocationService", "Sent ${currentBatch.size} locations")
            } catch (e: Exception) {
                Log.e("LocationService", "Failed to send location batch", e)
                // Could put them back in batch
            }
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        fusedLocationClient.removeLocationUpdates(locationCallback)
        job.cancel()
    }

    override fun onBind(intent: Intent): IBinder? = null

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Location Tracking Service",
                NotificationManager.IMPORTANCE_LOW
            )
            val manager = getSystemService(NotificationManager::class.java)
            manager?.createNotificationChannel(channel)
        }
    }

    private fun createNotification(content: String): Notification {
        val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Notification.Builder(this, CHANNEL_ID)
        } else {
            @Suppress("DEPRECATION")
            Notification.Builder(this)
        }

        return builder.setContentTitle("Tracker")
            .setContentText(content)
            .setSmallIcon(android.R.drawable.ic_menu_mylocation)
            .build()
    }
}
