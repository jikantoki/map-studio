import { defineStore } from 'pinia'

export type Map = {
  /** 作った時間unixtime */
  createdAt: number
  icon: string | null
  /** 地図の説明 */
  description: string | null
  /** 地図の名前 */
  name: string
  /** 公開設定 */
  isPublic: boolean
  /** サーバーID */
  serverId: string
  /** 作った人のユーザーID */
  ownerUserId: string
  /** 地点リスト */
  points: {
    /** 地点の名前 */
    name: string
    /** 地点の説明 */
    description: string | null
    /** 地点のアイコン */
    icon: string | null
    /** 地点の位置 */
    latlng: [lat: number, lng: number]
  }[]
}

export const useMapsStore = defineStore('maps', {
  state: () => ({
    maps: [] as Map[],
  }),
  actions: {},
  persist: true,
})
