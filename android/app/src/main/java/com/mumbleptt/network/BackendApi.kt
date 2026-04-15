package com.mumbleptt.network

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.POST

data class DeviceRegistrationRequest(val device_id: String)

data class DeviceRegistrationResponse(
    val mumble_username: String,
    val mumble_password: String,
    val room_name: String?,
    val websocket_token: String,
    val gps_interval: Long?
)

data class GpsPoint(val lat: Double, val lng: Double, val timestamp: Long)

data class GpsReportRequest(val device_id: String, val points: List<GpsPoint>)

interface BackendApi {
    @POST("/api/device/register")
    suspend fun registerDevice(@Body request: DeviceRegistrationRequest): Response<DeviceRegistrationResponse>

    @POST("/api/device/gps")
    suspend fun reportGps(@Body request: GpsReportRequest): Response<Void>
}
