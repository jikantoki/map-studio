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
    path: '/icons/18kin.png',
    name: '18禁',
    description: '18歳未満禁止マーク',
  },
  {
    path: '/icons/1up.png',
    name: '1UP',
    description: 'ゲームの1UPアイコン',
  },
  {
    path: '/icons/60fps_parrot.gif',
    name: '60FPSパロット',
    description: '60FPSで踊るカラフルなオウムのアニメーション',
  },
  {
    path: '/icons/SOS.png',
    name: 'SOS',
    description: 'SOSの緊急サインマーク',
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
    path: '/icons/jr_east.png',
    name: 'JR東日本',
    description: 'JR東日本のロゴマーク',
  },
  {
    path: '/icons/map.png',
    name: '地図',
    description: '地図を示すアイコン',
  },
  {
    path: '/icons/mario-block.png',
    name: 'マリオブロック',
    description: 'マリオのハテナブロック',
  },
  {
    path: '/icons/new.png',
    name: 'NEW',
    description: '新着・新規を示すNEWマーク',
  },
  {
    path: '/icons/orbis.png',
    name: 'オービス',
    description: 'オービス（速度違反取締機）のアイコン',
  },
  {
    path: '/icons/parrot.png',
    name: 'パロット',
    description: 'カラフルなオウムのアイコン',
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
]
