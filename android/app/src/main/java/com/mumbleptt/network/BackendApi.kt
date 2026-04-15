package com.mumbleptt.network

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.POST

data class DeviceRegistrationRequest(val device_id: String)

data class DeviceRegistrationResponse(
    val mumble_username: String,
    val mumble_password: String,
    val room_name: String?,
    val websocket_token: String
)

interface BackendApi {
    @POST("/api/device/register")
    suspend fun registerDevice(@Body request: DeviceRegistrationRequest): Response<DeviceRegistrationResponse>
}
