import { defineStore } from 'pinia'

/** 地図のデータ構造 */
export type Map = {
  /** 作った時間unixtime */
  createdAt: number | undefined
  /** 地図のアイコン画像URL（オーナー以外編集不可） */
  icon: string | undefined
  /** 地図の説明 */
  description: string | undefined
  /** 地図の名前（オーナー以外編集不可） */
  name: string
  /** 公開設定（オーナー以外編集不可） */
  isPublic: boolean
  /** サーバーID（URLに表示されます・オーナー以外編集不可） */
  serverId: string
  /** 作った人のユーザーID */
  ownerUserId: string
  /** 地点リスト */
  points: {
    /** 差分マージ用一意ID */
    id?: string
    /** 地点の名前 */
    name: string | undefined
    /** 地点の説明 */
    description: string | undefined
    /** 地点のアイコン画像URL */
    iconImg: string | undefined
    /** 地点のアイコンMdi */
    iconMdi: string | undefined
    /** 地点のアイコンの色 */
    iconColor: string | undefined
    /** 地点の位置 */
    latlng: [lat: number, lng: number]
    /** 追加・編集した人のユーザーID */
    authorUserId: string | undefined
  }[]
  /** 線リスト */
  lines: {
    /** 差分マージ用一意ID */
    id?: string
    /** 線の名前 */
    name: string | undefined
    /** 線の説明 */
    description: string | undefined
    /** 線のアイコン画像URL */
    iconImg: string | undefined
    /** 線のアイコンMdi */
    iconMdi: string | undefined
    /** 経由地点リスト */
    waypoints: {
      /** 経由地点の名前 */
      name: string | undefined
      /** 経由地点の説明 */
      description: string | undefined
      /** 経由地点のアイコン画像URL */
      iconImg: string | undefined
      /** 経由地点のアイコンMdi */
      iconMdi: string | undefined
      /** 経由地点のアイコンの色 */
      iconColor: string | undefined
      /** 経由地点の位置 */
      latlng: [lat: number, lng: number]
    }[]
    /** 線の色 */
    color: string | undefined
    /** 線の太さ */
    width: number | undefined
    /** 追加・編集した人のユーザーID */
    authorUserId: string | undefined
  }[]
  /** 閲覧可能ユーザーIDリスト（isPublicがtrueの場合に有効） */
  sharedUserIds: string[]
  /** 編集可能ユーザーIDリスト */
  editorUserIds: string[]
  /** 地図を開いたときに最初に表示する中心地 */
  defaultCenterLatLng: [lat: number, lng: number]
  /** 地図を開いたときに最初に表示するズームレベル */
  defaultZoom: number
}

export const useMapsStore = defineStore('maps', {
  state: () => ({
    maps: [] as Map[],
  }),
  actions: {},
  persist: true,
})
