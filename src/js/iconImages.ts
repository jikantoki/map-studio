/**
 * /public/icons/ 内の画像ファイル一覧（URLパスは /icons/ から始まる）
 */
export type IconImage = {
  /** ファイルパス（/icons/から始まるパス） */
  path: string
  /** 画像の名前（日本語） */
  name: string
  /** 簡単な画像の説明文（日本語） */
  description: string
}

export const iconImages: IconImage[] = [
  {
    path: '/icons/1up.png',
    name: '1UP',
    description: 'ゲームの1UPアイコン',
  },
  {
    path: '/icons/18kin.png',
    name: '18禁',
    description: '18歳未満禁止マーク',
  },
  {
    path: '/icons/60fps_parrot.gif',
    name: '60FPSパロット',
    description: '60FPSで踊るカラフルなオウムのアニメーション',
  },
  {
    path: '/icons/alert.gif',
    name: 'アラート',
    description: '注意・警告を示すアニメーションアイコン',
  },
  {
    path: '/icons/coin.gif',
    name: 'コイン',
    description: '回転するコインのアニメーション',
  },
  {
    path: '/icons/etc.png',
    name: 'ETC',
    description: 'ETCカードを示すアイコン',
  },
  {
    path: '/icons/jr_east.png',
    name: 'JR東日本',
    description: 'JR東日本のロゴマーク',
  },
  {
    path: '/icons/kendo.png',
    name: '都道府県道',
    description: '都道府県道を示す標識のアイコン',
  },
  {
    path: '/icons/kokudo.png',
    name: '国道',
    description: '国道を示す標識のアイコン',
  },
  {
    path: '/icons/map.png',
    name: '地図',
    description: '地図を示すアイコン',
  },
  {
    path: '/icons/mario-block.png',
    name: 'はてなブロック',
    description: 'ゲームのはてなブロック',
  },
  {
    path: '/icons/metro.png',
    name: '地下鉄',
    description: '地下鉄を示す標識のアイコン',
  },
  {
    path: '/icons/new.png',
    name: 'NEW',
    description: '新着・新規を示すNEWマーク',
  },
  {
    path: '/icons/odanhodo.png',
    name: '横断歩道',
    description: '横断歩道を示す標識のアイコン',
  },
  {
    path: '/icons/orbis.png',
    name: 'オービス',
    description: 'オービス（速度違反取締機）のアイコン',
  },
  {
    path: '/icons/other_warning.png',
    name: 'その他の警告',
    description: 'その他の警告を示すアイコン',
  },
  {
    path: '/icons/parking.png',
    name: '駐車場',
    description: '駐車場を示す標識のアイコン',
  },
  {
    path: '/icons/parking_highway.png',
    name: 'パーキングエリア',
    description: '高速道路のパーキングエリアを示す標識のアイコン',
  },
  {
    path: '/icons/parrot.png',
    name: 'パロット',
    description: 'カラフルなオウムのアイコン',
  },
  {
    path: '/icons/patrol_car.png',
    name: 'パトカー',
    description: 'パトカーを示すアイコン',
  },
  {
    path: '/icons/question.png',
    name: 'はてな',
    description: '疑問・不明を示すはてなマーク',
  },
  {
    path: '/icons/saikou2.png',
    name: '最高',
    description: '最高・良いを示すアイコン',
  },
  {
    path: '/icons/shinkansen.png',
    name: '新幹線',
    description: '新幹線を示すアイコン',
  },
  {
    path: '/icons/shutoko.png',
    name: '都市高速',
    description: '都市高速道路を示す標識のアイコン',
  },
  {
    path: '/icons/signal.png',
    name: '信号',
    description: '信号を示す標識のアイコン',
  },
  {
    path: '/icons/SOS.png',
    name: 'SOS',
    description: 'SOSの緊急サインマーク',
  },
  {
    path: '/icons/speed_30.png',
    name: '速度制限30',
    description: '速度制限30km/hを示す標識のアイコン',
  },
  {
    path: '/icons/speed_40.png',
    name: '速度制限40',
    description: '速度制限40km/hを示す標識のアイコン',
  },
  {
    path: '/icons/speed_50.png',
    name: '速度制限50',
    description: '速度制限50km/hを示す標識のアイコン',
  },
  {
    path: '/icons/speed_60.png',
    name: '速度制限60',
    description: '速度制限60km/hを示す標識のアイコン',
  },
  {
    path: '/icons/speed_80.png',
    name: '速度制限80',
    description: '速度制限80km/hを示す標識のアイコン',
  },
  {
    path: '/icons/speed_100.png',
    name: '速度制限100',
    description: '速度制限100km/hを示す標識のアイコン',
  },
  {
    path: '/icons/speed_120.png',
    name: '速度制限120',
    description: '速度制限120km/hを示す標識のアイコン',
  },
  {
    path: '/icons/station.png',
    name: '駅',
    description: '駅を示すアイコン',
  },
  {
    path: '/icons/tomare.png',
    name: '止まれ',
    description: '止まれを示す標識のアイコン',
  },
  {
    path: '/icons/train_warning.png',
    name: '踏切注意',
    description: '踏切注意を示す標識のアイコン',
  },
]
