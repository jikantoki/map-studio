import type { CapacitorConfig } from '@capacitor/cli'

const config: CapacitorConfig = {
  appId: 'xyz.enoki.mapstudio',
  appName: 'Mapstudio',
  webDir: 'dist',
  plugins: {
    StatusBar: {
      overlaysWebView: false,
    },
    BackgroundRunner: {
      label: 'xyz.enoki.mapstudio.background',
      src: 'runners/background-runner.js',
      event: 'MapstudioBackgroundRunner',
      repeat: true,
      /** OSの制約で15分間隔 */
      interval: 15,
      autoStart: true,
    },
    PushNotifications: {
      presentationOptions: ['badge', 'sound', 'alert'],
    },
    CapacitorHttp: {
      enabled: true,
    },
  },
  server: {
    hostname: 'mapstudio.enoki.xyz',
    androidScheme: 'https',
  },
  deepLinks: {
    enabled: true,
    prefixes: ['mapstudio://', 'https://mapstudio.enoki.xyz'],
  },
  android: {
    useLegacyBridge: true,
    notificationIcon: 'ic_location_status',
  },
}

export default config
