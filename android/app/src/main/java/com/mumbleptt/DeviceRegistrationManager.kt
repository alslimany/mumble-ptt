package com.mumbleptt

import android.annotation.SuppressLint
import android.content.Context
import android.provider.Settings
import com.mumbleptt.network.BackendApi
import com.mumbleptt.network.DeviceRegistrationRequest
import com.mumbleptt.network.DeviceRegistrationResponse
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class DeviceRegistrationManager(
    private val context: Context,
    private val backendApi: BackendApi
) {

    private val prefs = context.getSharedPreferences("mumble_prefs", Context.MODE_PRIVATE)

    @SuppressLint("HardwareIds")
    fun getDeviceId(): String {
        return Settings.Secure.getString(context.contentResolver, Settings.Secure.ANDROID_ID)
            ?: "unknown_device_id"
    }

    suspend fun registerDevice(): DeviceRegistrationResponse? {
        return withContext(Dispatchers.IO) {
            try {
                val deviceId = getDeviceId()
                val request = DeviceRegistrationRequest(device_id = deviceId)
                val response = backendApi.registerDevice(request)

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null) {
                        saveCredentials(body)
                        return@withContext body
                    }
                }
                
                // Fallback to cached credentials
                return@withContext getCachedCredentials()
            } catch (e: Exception) {
                // Network failure or other error, fallback to cache
                getCachedCredentials()
            }
        }
    }

    private fun saveCredentials(credentials: DeviceRegistrationResponse) {
        prefs.edit()
            .putString("mumble_username", credentials.mumble_username)
            .putString("mumble_password", credentials.mumble_password)
            .putString("room_name", credentials.room_name)
            .putString("websocket_token", credentials.websocket_token)
            .apply()
    }

    fun getCachedCredentials(): DeviceRegistrationResponse? {
        val username = prefs.getString("mumble_username", null)
        val password = prefs.getString("mumble_password", null)
        val room = prefs.getString("room_name", null)
        val token = prefs.getString("websocket_token", null)

        if (username != null && password != null && token != null) {
            return DeviceRegistrationResponse(username, password, room, token)
        }
        return null
    }
}
