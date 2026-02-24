<template lang="pug">
v-card(
  style="width: 100%; height: 100%;"
  :class="settings.hidden.isAndroid15OrHigher ? 'top-android-15-or-higher' : ''"
  )
  v-card-actions
    .ml-2(style="font-size: 1.3em; height: 4em; display: flex; align-items: center;")
      p Map Studio
    v-spacer
  v-card-text(style="height: inherit; overflow-y: auto;")
    v-tabs(v-model="activeTab" grow)
      v-tab(value="myMaps") 自分の地図
      v-tab(value="publicMaps") 公開地図
    //- 自分の地図タブ
    v-window(v-model="activeTab")
      v-window-item(value="myMaps")
        .content
          p {{ maps.maps.length }}件の地図があります
          .map-card(
            v-for="map in maps.maps"
            :key="map.serverId"
            @click="$router.push(`/map/${map.serverId}`)"
            v-ripple
            style="cursor: pointer; display: flex; flex-direction: row; align-items: center; gap: 1em; padding: 1em; border-radius: 12px;"
          )
            .map-icon
              img(
                :src="map.icon ?? '/icons/map.png'"
                style="width: 4em; height: 4em; object-fit: cover; border-radius: 12px; background-color: white;"
                onerror="this.src='/icons/map.png'"
              )
            .map-info
              p.name-space {{ map.name }}
              p サーバーID: {{ map.serverId }}
              p 作成日時: {{ map.createdAt ? new Date(map.createdAt).toLocaleString() : '不明' }}
              p {{ map.isPublic ? '公開' : '非公開' }} {{ map.ownerUserId === myProfile.userId ? '（あなたの地図）' : `@${map.ownerUserId}が作成` }}
              p {{ map.description && map.description.length ? map.description : '説明はありません' }}
      //- 公開地図タブ
      v-window-item(value="publicMaps")
        .content
          v-text-field(
            v-model="publicMapSearch"
            label="サーバー名・説明で検索"
            prepend-inner-icon="mdi-magnify"
            clearable
            hide-details
            style="margin-bottom: 1em;"
            @keydown.enter="fetchPublicMaps(1)"
            @click:clear="publicMapSearch = ''; fetchPublicMaps(1)"
          )
          p(style="margin-bottom: 0.5em;") {{ publicMapsTotalCount }}件の公開地図があります
          v-progress-linear(v-if="publicMapsLoading" indeterminate)
          .map-card(
            v-for="map in publicMaps"
            :key="map.serverId"
            @click="$router.push(`/map/${map.serverId}`)"
            v-ripple
            style="cursor: pointer; display: flex; flex-direction: row; align-items: center; gap: 1em; padding: 1em; border-radius: 12px;"
          )
            .map-icon
              img(
                :src="map.icon ?? '/icons/map.png'"
                style="width: 4em; height: 4em; object-fit: cover; border-radius: 12px; background-color: white;"
                onerror="this.src='/icons/map.png'"
              )
            .map-info
              p.name-space {{ map.name }}
              p サーバーID: {{ map.serverId }}
              p 作成日時: {{ map.createdAt ? new Date(map.createdAt).toLocaleString() : '不明' }}
              p {{ `@${map.ownerUserId}が作成` }}
              p {{ map.description && map.description.length ? map.description : '説明はありません' }}
          .pagination(style="display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 0.5em; margin-top: 1em;")
            v-btn(
              :disabled="publicMapsPage <= 1"
              icon
              @click="fetchPublicMaps(publicMapsPage - 1)"
            )
              v-icon mdi-chevron-left
            span {{ publicMapsPage }} / {{ publicMapsTotalPages }}
            v-btn(
              :disabled="publicMapsPage >= publicMapsTotalPages"
              icon
              @click="fetchPublicMaps(publicMapsPage + 1)"
            )
              v-icon mdi-chevron-right
  //-- 下部のアクションバー --
  .action-bar
    .buttons
      .button(
        v-ripple
        @click="setCurrentPosition"
        )
        v-icon mdi-home
        p トップ
      .button(
        v-ripple
        @click="optionsDialog = true"
        style="opacity: 0.8;"
        )
        v-icon mdi-dots-vertical
        p その他
    .bottom-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
  //-- 右下のボタン --
  .right-bottom-buttons
    .current-button
      v-btn(
        size="x-large"
        icon
        @click="myProfile.guest ? mapCreateDialog = true : $router.push('/map/create')"
        style="background-color: rgb(var(--v-theme-primary)); color: white"
        )
        v-icon mdi-plus
    .bottom-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
  //-- 右上のアカウントボタン --
  .right-top-buttons
    .top-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
    .account-button
      .button(
        v-ripple
        @click="optionsDialog = true"
        style="cursor: pointer; border-radius: 9999px; height: 4em; width: 4em;"
        )
        img(
          loading="lazy"
          :src="myProfile && myProfile.icon ? myProfile.icon : '/account_default.jpg'"
          style="height: 4em; width: 4em; border-radius: 9999px; border: solid 2px #000;"
          onerror="this.src='/account_default.jpg'"
          )
  //-- 友達検索ダイアログ --
  v-dialog(
    v-model="searchFriendDialog"
  )
    v-card
      v-card-actions(
        style="width: 90vw;"
      )
        p.ml-2 友達を探す
        v-spacer
        v-btn(
          text
          @click="searchFriendDialog = false"
          icon="mdi-close"
          )
      v-card-text
        v-text-field(
          label="友達のID"
          prepend-icon="mdi-account"
          v-model="searchFriendId"
          @keydown="searchFriendErrorMessage = ''"
          @keydown.enter="searchFriend(searchFriendId)"
        )
        p(
          style="height: 1em;"
        ) {{ searchFriendErrorMessage }}
      v-card-actions
        v-btn(
          @click="searchFriend(searchFriendId)"
          prepend-icon="mdi-magnify"
          style="background-color: rgb(var(--v-theme-primary));"
          :loading="searchFriendLoading"
        ) 検索
  //-- オプションダイアログ --
  v-dialog(
    v-model="optionsDialog"
    transition="dialog-bottom-transition"
    fullscreen
  )
    v-card
      .top-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
      v-card-actions
        p.ml-2(class="headline" style="font-size: 1.3em") ようこそ
        v-spacer
        v-btn(
          text
          @click="optionsDialog = false"
          icon="mdi-close"
          )
      v-card-text
        .account-details(
          style="display: flex; flex-direction: column; align-items: center; gap: 1em; margin-bottom: 1em;"
        )
          .account-img
            img(
              :src="myProfile && myProfile.icon ? myProfile.icon : '/account_default.jpg'"
              style="height: 8em; width: 8em; border-radius: 9999px;"
              onerror="this.src='/account_default.jpg'"
              )
          .account-info(
            style="text-align: center;"
          )
            p(
              v-if="myProfile.userId && !myProfile.guest"
              style="font-size: 1.2em; margin: 0; padding: 0;"
              ) {{ myProfile.name ? myProfile.name : myProfile.userId }}
            p(
              v-else
              style="font-size: 1.2em; margin: 0; padding: 0;"
              ) ログインしていません
            p(style="margin: 0; padding: 0;")
              | {{ myProfile.userId && !myProfile.guest ? `@${myProfile.userId}` : 'データは同期されていません' }}
            v-btn.my-2(
              v-if="myProfile.userId && !myProfile.guest"
              text
              @click="$router.push(`/user/${myProfile.userId}`)"
              append-icon="mdi-account-outline"
              style="background-color: rgb(var(--v-theme-primary));"
            ) プロフィールを表示
            v-btn.my-2(
              v-else
              text
              @click="$router.push('/login')"
              append-icon="mdi-login"
              style="background-color: rgb(var(--v-theme-primary)); color: white;"
            ) ログイン
        v-list.options-list
          v-list-item.item( @click="searchFriendDialog = true" )
            .icon-and-text
              v-icon mdi-magnify
              v-list-item-title 友達を探す
          v-list-item.item( @click="$router.push('qrcode')" )
            .icon-and-text
              v-icon mdi-qrcode-scan
              v-list-item-title QRコードで友達を探す
          v-list-item.item(
            @click="$router.push('/friendlist')"
            v-show="myProfile && myProfile.userId"
          )
            .icon-and-text
              v-icon mdi-account-multiple
              v-list-item-title 友達リスト
          v-list-item.item( @click="$router.push('/settings')" )
            .icon-and-text
              v-icon mdi-cog
              v-list-item-title 設定
          v-list-item.item( @click="$router.push('/terms')" )
            .icon-and-text
              v-icon mdi-file-document-outline
              v-list-item-title 利用規約
          v-list-item.item( @click="openURL('https://enoki.xyz/privacy')" )
            .icon-and-text
              v-icon mdi-shield-lock-outline
              v-list-item-title プライバシーポリシー
          v-list-item.item( @click="$router.push('/about')" )
            .icon-and-text
              v-icon mdi-information
              v-list-item-title このアプリについて
          v-list-item.item( @click="share('https://play.google.com/store/apps/dev?id=8940000495375956936', 'エノキ電気')" )
            .icon-and-text
              v-icon mdi-share-variant
              v-list-item-title このアプリを共有する
  v-dialog(
    v-model="acceptDialog"
    persistent
  )
    v-card
      v-card-title 友達リクエストが来ています！
      v-card-text
        p {{ acceptList.length }}人の友達があなたを待っています。承認してつながろう！
      v-card-actions
        v-btn(
          @click="acceptDialog = false"
        ) やめとく
        v-btn(
          @click="$router.push('/friendlist')"
          style="background-color: rgb(var(--v-theme-primary)); color: white"
        ) リクエストを見る
  v-dialog(
    v-model="mapCreateDialog"
  )
    v-card
      .top-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
      v-card-actions
        p.ml-2(class="headline" style="font-size: 1.3em") 地図を作成
        v-spacer
        v-btn(
          text
          @click="mapCreateDialog = false"
          icon="mdi-close"
          )
      v-card-text
        p ログインしてから地図を作成することで、地図の管理や友達との共有ができるようになります。
      v-card-actions
        v-btn(
          text
          prepend-icon="mdi-account-off"
          @click="mapCreateDialog = false; $router.push('/map/create')"
        ) ログインせずに作成
        v-btn(
          text
          @click="$router.push('/login')"
          prepend-icon="mdi-login"
          style="background-color: rgb(var(--v-theme-primary)); color: white;"
        ) ログインして作成
</template>

<script lang="ts">
  import { App } from '@capacitor/app'
  import { Browser } from '@capacitor/browser'
  import { Share } from '@capacitor/share'

  // @ts-ignore
  import mixins from '@/mixins/mixins'
  import { useMapsStore } from '@/stores/maps'
  import { useMyProfileStore } from '@/stores/myProfile'
  import { useSettingsStore } from '@/stores/settings'

  export default {
    components: {},
    mixins: [mixins],
    data () {
      return {
        /** オプションダイアログの表示フラグ */
        optionsDialog: false,
        /** 自分のプロフィール */
        myProfile: useMyProfileStore(),
        /** 友達検索ダイアログ */
        searchFriendDialog: false,
        /** 検索する友達のID */
        searchFriendId: '',
        /** 友達検索中のローディング画面 */
        searchFriendLoading: false,
        /** 友達検索画面のエラー表示 */
        searchFriendErrorMessage: '',
        /** 環境変数 */
        env: null as any,
        /** 承認待ち友達リスト */
        acceptList: [] as any,
        /** 承認してほしい友達がいるダイアログ */
        acceptDialog: false,
        /** 友達リスト */
        friendList: [] as any[],
        /** 設定ストア */
        settings: useSettingsStore(),
        /** 地図ストア */
        maps: useMapsStore(),
        /** 地図作成ダイアログ */
        mapCreateDialog: false,
        /** アクティブタブ */
        activeTab: 'myMaps',
        /** 公開地図リスト */
        publicMaps: [] as any[],
        /** 公開地図の検索キーワード */
        publicMapSearch: '',
        /** 公開地図の現在ページ */
        publicMapsPage: 1,
        /** 公開地図の総件数 */
        publicMapsTotalCount: 0,
        /** 公開地図の総ページ数 */
        publicMapsTotalPages: 1,
        /** 公開地図の読み込み中フラグ */
        publicMapsLoading: false,
      }
    },
    computed: {},
    watch: {
      /** ようこそ画面の表示状態を保存 */
      optionsDialog: {
        handler: async function (dialog: boolean) {
          localStorage.setItem('welcomeDialog', String(dialog))
        },
      },
      /** 公開地図タブに切り替えたら初回取得 */
      activeTab: {
        handler: async function (tab: string) {
          if (tab === 'publicMaps' && this.publicMaps.length === 0) {
            await (this as any).fetchPublicMaps(1)
          }
        },
      },
    },
    async mounted () {
      // @ts-ignore
      this.env = import.meta.env as any

      /** ようこその復活 */
      const welcomeDialog = localStorage.getItem('welcomeDialog')
      if (welcomeDialog && welcomeDialog.toLowerCase() == 'true') {
        this.optionsDialog = true
      }

      /** ログイン情報 */
      if (this.myProfile.$state.guest == false) {
        setTimeout(async () => {
          const token = this.myProfile.userToken
          const profile: any = await this.getProfile(this.myProfile.userId ?? '')
          if (profile) {
            profile.userToken = token
            profile.guest = false
            this.myProfile = {
              ...this.myProfile,
              ...profile,
            }
          }
        }, 100)
      } else {
        this.myProfile.reset()
      }

      /** バックボタンのリスナーを追加 */
      App.addListener('backButton', () => {
        if (this.searchFriendDialog) {
          // 友達検索ダイアログを閉じる
          this.searchFriendDialog = false
        } else if (this.acceptDialog) {
          // 友達承認しろダイアログを閉じる
          this.acceptDialog = false
        } else if (this.optionsDialog) {
          /** オプションダイアログを閉じる */
          this.optionsDialog = false
        } else if (this.$route.path === '/') {
          /** ルートページならアプリを最小化 */
          App.minimizeApp()
        } else {
          /** ルート以外のページなら1つ戻る */
          this.$router.back()
        }
      })

      // 承認していない友達リクエストがあったらポップアップを表示
      const res: any = await this.sendAjaxWithAuth('/getMyFriendList.php', {
        id: this.myProfile.userId,
        token: this.myProfile.userToken,
        withLocation: true,
      })
      if (res && res.body) {
        const allFriendList: any[] = res.body.friendList
        this.acceptList = []
        if (allFriendList && allFriendList[0]) {
          for (const friend of allFriendList) {
            friend.friendProfile.userId = friend.friendRealId
            if (friend.status == 'request' && friend.fromUserId != res.body.mySecretId) {
              this.acceptList.push(friend.friendProfile)
            }
          }
        }
      }
      if (this.acceptList.length > 0 && history.length <= 2) {
        this.acceptDialog = true
      }
    },
    unmounted () {
      App.removeAllListeners()
    },
    methods: {
      /** 秒比較 */
      diffSeconds (date: Date | null | undefined) {
        if (!date) {
          return 999_999
        }

        const now = new Date()
        /** 差分秒 */
        const diff = (now.getTime() - date.getTime()) / 1000
        return diff
      },
      /** Googleマップで開く */
      openGoogleMaps (latlng: [
        number, number,
      ]) {
        this.openURL(
          `https://www.google.com/maps/search/?api=1&query=${latlng[0]},${latlng[1]}`,
        )
      },
      /** URLをブラウザで開く */
      async openURL (url: string) {
        await Browser.open({ url: url })
      },
      /** 友達を検索 */
      async searchFriend (searchId: string) {
        searchId = searchId.replace('@', '')
        this.searchFriendLoading = true
        if (!searchId) {
          this.searchFriendErrorMessage = '検索するIDを入力してください'
          this.searchFriendLoading = false
          return
        }
        const userData = await this.getProfile(
          searchId,
          this.myProfile.userId,
          this.myProfile.userToken,
        )
        if (userData) {
          this.$router.push(`/user/${userData.userId}`)
        } else {
          this.searchFriendErrorMessage = '友達が見つかりませんでした'
          this.searchFriendLoading = false
          return
        }
        this.searchFriendLoading = false
      },
      /**
       * 2つのDateオブジェクトの差が10秒以内か判定する
       * @param {Date} date1 - 比較する1つ目の日付
       * @param {Date} date2 - 比較する2つ目の日付
       * @returns {boolean} 10秒以内ならtrue
       */
      isWithin10Seconds (date1: Date, date2: Date) {
        // 10秒 = 10000ミリ秒
        const diffInMilliseconds = Math.abs(date1.getTime() - date2.getTime())
        return diffInMilliseconds <= 10_000
      },
      /** シェアダイアログ */
      async share (content: string, title = '') {
        await Share.share({
          url: content,
          title: title,
        })
      },
      /** 公開地図を取得 */
      async fetchPublicMaps (page: number) {
        this.publicMapsLoading = true
        this.publicMapsPage = page
        const res: any = await this.sendAjaxWithAuth('/getPublicMaps.php', {
          page: String(page),
          search: this.publicMapSearch ?? '',
        })
        if (res && res.body && res.body.status === 'ok') {
          this.publicMaps = res.body.maps
          this.publicMapsTotalCount = res.body.totalCount
          this.publicMapsTotalPages = res.body.totalPages || 1
        }
        this.publicMapsLoading = false
      },
    },
  }
</script>

<style lang="scss" scoped>
.right-bottom-buttons {
  position: fixed;
  right: 16px;
  bottom: calc(16px + 4em);
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 1000;

  .current-button {
    background-color: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  }
}
.right-top-buttons {
  position: fixed;
  right: 16px;
  top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 1000;

  .account-button {
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  }
}
.left-top-buttons {
  position: fixed;
  left: 16px;
  top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 1000;

  .account-button {
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  }
}

.name-space {
  font-size: 16px;
  font-weight: 500;
  white-space: nowrap;
  -webkit-text-stroke: 2px black;
  paint-order: stroke;
  color: white;
  transition: all 1s;
}

.action-bar{
  position: fixed;
  bottom: 0;
  left: 0;
  background-color: rgb(var(--v-theme-surface));
  z-index: 500;
  width: 100%;
  align-items: center;
  box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.3);
  .buttons{
    width: 100%;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-evenly;
    height: 4em;
    .button {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 6em;
      border-radius: 1em;
      height: 80%;
      cursor: pointer;
      color: rgb(var(--v-theme-on-surface));

      v-icon {
        font-size: 24px;
      }

      p {
        font-size: 10px;
        margin: 0;
        padding: 0;
      }
    }
  }
  .bottom-android-15-or-higher {
    width: 100%;
  }
}

.bottom-android-15-or-higher {
  height: 16px;
}
.top-android-15-or-higher {
  height: 40px;
}

.options-list {
  .item {
    padding : 12px 16px;
    border-radius: 12px!important;
    margin: 8px 0;
    cursor: pointer;
    &:hover {
      background-color: rgba(var(--v-theme-primary), 0.1);
    }
    .icon-and-text {
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 16px;
      v-icon {
        font-size: 24px;
      }
    }
  }
}

.detail-card-target {
  .info{
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 1em;
    margin: 1em 0;
  }
}

.opacity05 {
  opacity: 0.7;
}

.map-card {
  transition: all 0.3s;
  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.1);
  }
}
</style>
