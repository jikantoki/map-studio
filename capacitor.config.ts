import type { CapacitorConfig } from '@capacitor/cli'

const config: CapacitorConfig = {
  appId: 'xyz.enoki.mapstudio',
  appName: 'Map Studio',
  webDir: 'dist',
  plugins: {
    StatusBar: {
      overlaysWebView: false,
    },
    PushNotifications: {
      presentationOptions: ['badge', 'sound', 'alert'],
    },
    CapacitorHttp: {
      enabled: true,
    },
  },
  server: {
    hostname: 'map.enoki.xyz',
    androidScheme: 'https',
  },
  deepLinks: {
    enabled: true,
    prefixes: ['mapstudio://', 'https://map.enoki.xyz'],
  },
  android: {
    useLegacyBridge: true,
    notificationIcon: 'ic_location_status',
  },
}

export default config
