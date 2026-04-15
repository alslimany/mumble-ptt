package com.mumbleptt

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import com.mumbleptt.network.BackendApi
import com.mumbleptt.network.DeviceRegistrationRequest
import com.mumbleptt.network.DeviceRegistrationResponse
import kotlinx.coroutines.runBlocking
import org.junit.Assert.*
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer

@RunWith(RobolectricTestRunner::class)
class DeviceRegistrationManagerTest {

    private lateinit var context: Context
    private lateinit var mockWebServer: MockWebServer
    private lateinit var backendApi: BackendApi
    private lateinit var registrationManager: DeviceRegistrationManager

    @Before
    fun setup() {
        context = ApplicationProvider.getApplicationContext()
        mockWebServer = MockWebServer()
        mockWebServer.start()

        val retrofit = Retrofit.Builder()
            .baseUrl(mockWebServer.url("/"))
            .addConverterFactory(GsonConverterFactory.create())
            .build()

        backendApi = retrofit.create(BackendApi::class.java)
        registrationManager = DeviceRegistrationManager(context, backendApi)
    }

    @Test
    fun `successful registration saves and returns credentials`() = runBlocking {
        val jsonResponse = """
            {
                "mumble_username": "user123",
                "mumble_password": "passXYZ",
                "room_name": "Channel1",
                "websocket_token": "tokenABC"
            }
        """.trimIndent()
        
        mockWebServer.enqueue(MockResponse().setResponseCode(200).setBody(jsonResponse))

        val result = registrationManager.registerDevice()

        assertNotNull(result)
        assertEquals("user123", result?.mumble_username)

        val cachedResult = registrationManager.getCachedCredentials()
        assertNotNull(cachedResult)
        assertEquals("user123", cachedResult?.mumble_username)
    }

    @Test
    fun `network failure falls back to cached credentials`() = runBlocking {
        val prefs = context.getSharedPreferences("mumble_prefs", Context.MODE_PRIVATE)
        prefs.edit()
            .putString("mumble_username", "cachedUser")
            .putString("mumble_password", "cachedPass")
            .putString("room_name", "cachedRoom")
            .putString("websocket_token", "cachedToken")
            .commit()

        // Missing queue setup causes 500 equivalent network error / connection refused
        mockWebServer.shutdown()

        val result = registrationManager.registerDevice()

        assertNotNull(result)
        assertEquals("cachedUser", result?.mumble_username)
    }
}
