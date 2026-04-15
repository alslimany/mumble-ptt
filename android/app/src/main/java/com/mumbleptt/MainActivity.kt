package com.mumbleptt

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.view.KeyEvent
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import com.mumbleptt.network.BackendApi
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory

class MainActivity : ComponentActivity() {

    private lateinit var registrationManager: DeviceRegistrationManager
    private val scope = CoroutineScope(Dispatchers.Main)

    private val requiredPermissions = mutableListOf(
        Manifest.permission.RECORD_AUDIO,
        Manifest.permission.INTERNET,
        Manifest.permission.READ_PHONE_STATE,
        Manifest.permission.ACCESS_FINE_LOCATION,
        Manifest.permission.ACCESS_COARSE_LOCATION
    ).apply {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            add(Manifest.permission.POST_NOTIFICATIONS)
        }
    }.toTypedArray()

    private val permissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        if (permissions.all { it.value }) {
            initializeRegistration()
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val retrofit = Retrofit.Builder()
            // using 10.0.2.2 for local emulator to access host machine backend
            .baseUrl("http://10.0.2.2")
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            
        registrationManager = DeviceRegistrationManager(this, retrofit.create(BackendApi::class.java))

        checkPermissionsAndInit()

        setContent {
            var status by remember { mutableStateOf("Initializing...") }
            
            LaunchedEffect(Unit) {
               // Update status based on registration manager init
               // Will be triggered by initializeRegistration()
            }

            MaterialTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(16.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.Center
                    ) {
                        Text(text = "Mumble PTT App", style = MaterialTheme.typography.headlineMedium)
                        Spacer(modifier = Modifier.height(16.dp))
                        Text(text = status, style = MaterialTheme.typography.bodyLarge)
                        Spacer(modifier = Modifier.height(32.dp))
                        
                        Button(
                            onClick = { togglePtt() },
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(60.dp)
                        ) {
                            Text("Toggle PTT")
                        }
                    }
                }
            }
        }
    }

    private fun togglePtt() {
        val intent = Intent(this, MumbleService::class.java)
        intent.action = MumbleService.ACTION_TOGGLE_PTT
        startService(intent)
    }

    private fun checkPermissionsAndInit() {
        if (requiredPermissions.all { it ->
            ContextCompat.checkSelfPermission(this, it) == PackageManager.PERMISSION_GRANTED
        }) {
            initializeRegistration()
        } else {
            permissionLauncher.launch(requiredPermissions)
        }
    }

    private fun initializeRegistration() {
        scope.launch {
            val credentials = registrationManager.registerDevice()
            if (credentials != null) {
                // Display success
                val serviceIntent = Intent(this@MainActivity, MumbleService::class.java)
                serviceIntent.action = MumbleService.ACTION_CONNECT
                serviceIntent.putExtra(MumbleService.EXTRA_USERNAME, credentials.mumble_username)
                serviceIntent.putExtra(MumbleService.EXTRA_PASSWORD, credentials.mumble_password)
                credentials.room_name?.let {
                    serviceIntent.putExtra(MumbleService.EXTRA_ROOM, it)
                }
                
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    startForegroundService(serviceIntent)
                } else {
                    startService(serviceIntent)
                }
            } else {
                // Display Error
                // status = "Registration failed."
            }
        }
    }

    override fun onKeyDown(keyCode: Int, event: KeyEvent?): Boolean {
        if (keyCode == KeyEvent.KEYCODE_VOLUME_UP || keyCode == KeyEvent.KEYCODE_VOLUME_DOWN) {
            // Volume key long press or click for PTT
            togglePtt()
            return true
        }
        return super.onKeyDown(keyCode, event)
    }
    
    override fun onKeyUp(keyCode: Int, event: KeyEvent?): Boolean {
        if (keyCode == KeyEvent.KEYCODE_VOLUME_UP || keyCode == KeyEvent.KEYCODE_VOLUME_DOWN) {
            togglePtt()
            return true
        }
        return super.onKeyUp(keyCode, event)
    }
}
