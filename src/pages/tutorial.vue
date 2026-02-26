<template lang="pug">
v-card(
  style="width: 100%; height: 100%;"
  :class="settings.hidden.isAndroid15OrHigher ? 'top-android-15-or-higher' : ''"
  )
  v-card-actions
    p.ml-2(style="font-size: 1.3em") チュートリアル
    v-spacer
    v-btn(
      text
      @click="$router.back()"
      icon="mdi-close"
      )
  //- ページインジケーター
  .page-indicator
    span(
      v-for="i in totalPages"
      :key="i"
      :class="['dot', { active: currentPage === i }]"
      @click="currentPage = i"
      )
  v-window(
    v-model="currentPage"
    style="height: calc(100vh - 140px);"
    )
    //- ページ 1: ようこそ
    v-window-item(:value="1" style="height: 100%; overflow-y: auto;")
      .tutorial-page
        .hero-section
          img.hero-icon(src="/icon.png")
          h1.hero-title Map Studio へようこそ！
          p.hero-desc Map Studio は、地図上に自由に点や線を描いて友達と共有できるアプリです。
        .feature-grid
          .feature-item
            v-icon.feature-icon(color="primary" size="40") mdi-map-marker
            p 点を置く
          .feature-item
            v-icon.feature-icon(color="primary" size="40") mdi-vector-polyline
            p 線を引く
          .feature-item
            v-icon.feature-icon(color="primary" size="40") mdi-share-variant
            p 地図を共有
          .feature-item
            v-icon.feature-icon(color="primary" size="40") mdi-account-multiple
            p 友達と使う
        p.hint-text このチュートリアルでは基本的な使い方を説明します。

    //- ページ 2: 点を追加する
    v-window-item(:value="2" style="height: 100%; overflow-y: auto;")
      .tutorial-page
        .page-header
          v-icon.page-icon(color="primary" size="56") mdi-map-marker-plus
          h2.page-title 点を追加する方法
        .step-list
          .step-item
            .step-number 1
            .step-content
              p.step-title 地図を開く
              p.step-desc ホーム画面の右下の
                v-icon(size="18" color="primary") mdi-plus
                |  ボタンから新しい地図を作成するか、既存の地図を開きます。
          .step-item
            .step-number 2
            .step-content
              p.step-title 点追加モードへ
              p.step-desc 地図画面の下部にある
                v-icon(size="18" color="primary") mdi-map-marker-plus
                |  ボタンをタップします。
          .step-item
            .step-number 3
            .step-content
              p.step-title 地図をタップ
              p.step-desc 点を置きたい場所を地図上でタップすると、マーカーが追加されます。
          .step-item
            .step-number 4
            .step-content
              p.step-title 名前・説明を入力
              p.step-desc 追加されたマーカーをタップして、名前や説明を入力できます。
        .tip-box
          v-icon(color="amber" size="20") mdi-lightbulb
          p.tip-text マーカーは後から自由に移動できます。長押しでドラッグ移動！

    //- ページ 3: 線を追加する
    v-window-item(:value="3" style="height: 100%; overflow-y: auto;")
      .tutorial-page
        .page-header
          v-icon.page-icon(color="primary" size="56") mdi-vector-polyline-plus
          h2.page-title 線を追加する方法
        .step-list
          .step-item
            .step-number 1
            .step-content
              p.step-title 線追加モードへ
              p.step-desc 地図画面の下部にある
                v-icon(size="18" color="primary") mdi-vector-polyline-plus
                |  ボタンをタップします。
          .step-item
            .step-number 2
            .step-content
              p.step-title 始点をタップ
              p.step-desc 線を引き始めたい場所を地図上でタップします。
          .step-item
            .step-number 3
            .step-content
              p.step-title 終点をタップ
              p.step-desc 線を終わらせたい場所をタップすると、2点間に線が描かれます。
          .step-item
            .step-number 4
            .step-content
              p.step-title 線の詳細を編集
              p.step-desc 線をタップして、名前・色・太さを変更できます。
        .tip-box
          v-icon(color="amber" size="20") mdi-lightbulb
          p.tip-text 経由地点を追加することで、折れ線や曲線のルートも表現できます！

    //- ページ 4: 色・アイコン・画像の変え方
    v-window-item(:value="4" style="height: 100%; overflow-y: auto;")
      .tutorial-page
        .page-header
          v-icon.page-icon(color="primary" size="56") mdi-palette
          h2.page-title 色・アイコン・画像の変え方
        .step-list
          .step-item
            .step-number 1
            .step-content
              p.step-title 点または線をタップ
              p.step-desc 変更したいマーカーや線をタップして詳細パネルを開きます。
          .step-item
            .step-number 2
            .step-content
              p.step-title 編集ボタンをタップ
              p.step-desc 詳細パネルの編集（
                v-icon(size="18" color="primary") mdi-pencil
                | ）ボタンをタップします。
          .step-item
            .step-number 3
            .step-content
              p.step-title カラーピッカーで色を変更
              p.step-desc 色見本をタップして、マーカーや線の色を自由に変更できます。
          .step-item
            .step-number 4
            .step-content
              p.step-title アイコンを選択
              p.step-desc マーカーのアイコンを多数の中から選べます。
          .step-item
            .step-number 5
            .step-content
              p.step-title 画像を設定
              p.step-desc マーカーにお気に入りの画像（写真）を設定することもできます。

    //- ページ 5: 経由地点の追加/削除
    v-window-item(:value="5" style="height: 100%; overflow-y: auto;")
      .tutorial-page
        .page-header
          v-icon.page-icon(color="primary" size="56") mdi-map-marker-path
          h2.page-title 線の経由地点の追加・削除
        .step-list
          .step-item
            .step-number 1
            .step-content
              p.step-title 線をタップ
              p.step-desc 経由地点を追加したい線をタップして詳細パネルを開きます。
          .step-item
            .step-number 2
            .step-content
              p.step-title 経由地点を追加
              p.step-desc 詳細パネルの「経由地点を追加」ボタンをタップすると、地図上で経由地点を指定できます。
          .step-item
            .step-number 3
            .step-content
              p.step-title 経由地点を移動
              p.step-desc 経由地点のマーカーを長押しでドラッグして位置を調整できます。
          .step-item
            .step-number 4
            .step-content
              p.step-title 経由地点を削除
              p.step-desc 削除したい経由地点をタップし、削除（
                v-icon(size="18" color="error") mdi-delete
                | ）ボタンをタップします。
        .tip-box
          v-icon(color="amber" size="20") mdi-lightbulb
          p.tip-text 経由地点を複数追加すると、複雑なルートも正確に表現できます！

    //- ページ 6: サーバー情報の変更
    v-window-item(:value="6" style="height: 100%; overflow-y: auto;")
      .tutorial-page
        .page-header
          v-icon.page-icon(color="primary" size="56") mdi-server
          h2.page-title 地図のサーバー情報の変更
        .step-list
          .step-item
            .step-number 1
            .step-content
              p.step-title 地図の設定を開く
              p.step-desc 地図画面右上の設定（
                v-icon(size="18" color="primary") mdi-cog
                | ）ボタンをタップします。
          .step-item
            .step-number 2
            .step-content
              p.step-title 地図名・説明を変更
              p.step-desc 地図の名前や説明文を自由に変更できます。
          .step-item
            .step-number 3
            .step-content
              p.step-title 公開設定の変更
              p.step-desc 「公開」にすると、他のユーザーが「みんなの地図」から見つけられるようになります。
          .step-item
            .step-number 4
            .step-content
              p.step-title アイコンの設定
              p.step-desc 地図のアイコン画像を設定して、一覧で見分けやすくしましょう。
          .step-item
            .step-number 5
            .step-content
              p.step-title 変更を保存
              p.step-desc 「保存」ボタンをタップして変更内容をサーバーに反映させます。
        .tip-box
          v-icon(color="amber" size="20") mdi-lightbulb
          p.tip-text ログインしていないと変更はサーバーに保存されません。ぜひログインして使いましょう！

    //- ページ 7: 地図を共有する
    v-window-item(:value="7" style="height: 100%; overflow-y: auto;")
      .tutorial-page
        .page-header
          v-icon.page-icon(color="primary" size="56") mdi-share-variant
          h2.page-title 地図を共有する方法
        .step-list
          .step-item
            .step-number 1
            .step-content
              p.step-title 地図の設定を開く
              p.step-desc 地図画面右上の設定（
                v-icon(size="18" color="primary") mdi-cog
                | ）ボタンをタップします。
          .step-item
            .step-number 2
            .step-content
              p.step-title 「共有」メニューへ
              p.step-desc 設定メニューの「共有」項目をタップします。
          .step-item
            .step-number 3
            .step-content
              p.step-title サーバーIDをコピー
              p.step-desc サーバーIDをコピーして友達に送ると、友達がその地図を開けます。
          .step-item
            .step-number 4
            .step-content
              p.step-title QRコードで共有
              p.step-desc QRコードを表示して、カメラでスキャンするだけでも地図を開けます。
          .step-item
            .step-number 5
            .step-content
              p.step-title 友達を編集者として招待
              p.step-desc 友達のIDを入力して「編集者」として追加すると、友達も地図を編集できるようになります。
        .tip-box
          v-icon(color="amber" size="20") mdi-lightbulb
          p.tip-text 地図を「公開」にすると URL リンク一本で誰でも閲覧できます！

    //- ページ 8: 使ってみる
    v-window-item(:value="8" style="height: 100%; overflow-y: auto;")
      .tutorial-page.final-page
        img.final-icon(src="/icon.png")
        h2.final-title チュートリアル完了！
        p.final-desc 基本的な使い方はバッチリです。さっそく地図を作って友達と共有してみましょう！
        .final-features
          .final-feature
            v-icon(color="primary" size="32") mdi-map-marker
            p 点を置く
          .final-feature
            v-icon(color="primary" size="32") mdi-vector-polyline
            p 線を引く
          .final-feature
            v-icon(color="primary" size="32") mdi-share-variant
            p 共有する
        v-btn.start-btn(
          size="x-large"
          @click="$router.push('/')"
          prepend-icon="mdi-map"
          style="background-color: rgb(var(--v-theme-primary)); color: white; margin-top: 2em;"
          ) 使ってみる

  //- ナビゲーションボタン
  .nav-buttons
    v-btn(
      :disabled="currentPage <= 1"
      variant="text"
      @click="currentPage--"
      prepend-icon="mdi-chevron-left"
      ) 前へ
    v-spacer
    span.page-count {{ currentPage }} / {{ totalPages }}
    v-spacer
    v-btn(
      v-if="currentPage < totalPages"
      variant="text"
      @click="currentPage++"
      append-icon="mdi-chevron-right"
      ) 次へ
    v-btn(
      v-else
      variant="text"
      @click="$router.push('/')"
      append-icon="mdi-check"
      style="color: rgb(var(--v-theme-primary));"
      ) 完了
</template>

<script lang="ts">
  import { useSettingsStore } from '@/stores/settings'

  export default {
    data () {
      return {
        currentPage: 1,
        totalPages: 8,
        settings: useSettingsStore(),
      }
    },
  }
</script>

<style lang="scss" scoped>
.top-android-15-or-higher {
  height: calc(100vh - 40px - 16px) !important;
}

.page-indicator {
  display: flex;
  justify-content: center;
  gap: 6px;
  padding: 8px 0;

  .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: rgba(var(--v-theme-on-surface), 0.2);
    cursor: pointer;
    transition: all 0.3s;

    &.active {
      background-color: rgb(var(--v-theme-primary));
      width: 24px;
      border-radius: 4px;
    }
  }
}

.nav-buttons {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  display: flex;
  align-items: center;
  padding: 8px 16px;
  background-color: rgb(var(--v-theme-surface));
  box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
  z-index: 100;

  .page-count {
    font-size: 0.9em;
    opacity: 0.6;
  }
}

.tutorial-page {
  padding: 16px 24px 80px;

  // ページ 1 ヒーローセクション
  .hero-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 16px 0 24px;

    .hero-icon {
      width: 100px;
      height: 100px;
      margin-bottom: 16px;
    }

    .hero-title {
      font-size: 1.8em;
      font-weight: bold;
      margin-bottom: 12px;
      color: rgb(var(--v-theme-primary));
    }

    .hero-desc {
      font-size: 1em;
      opacity: 0.8;
      line-height: 1.6;
      max-width: 320px;
    }
  }

  .feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin: 24px 0;

    .feature-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 16px;
      background-color: rgba(var(--v-theme-primary), 0.08);
      border-radius: 12px;
      gap: 8px;

      p {
        font-size: 0.9em;
        font-weight: 500;
        margin: 0;
      }
    }
  }

  .hint-text {
    text-align: center;
    opacity: 0.6;
    font-size: 0.85em;
    margin-top: 8px;
  }

  // ページヘッダー
  .page-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 16px 0 24px;

    .page-title {
      font-size: 1.5em;
      font-weight: bold;
      margin-top: 12px;
      color: rgb(var(--v-theme-primary));
    }
  }

  // ステップリスト
  .step-list {
    display: flex;
    flex-direction: column;
    gap: 16px;

    .step-item {
      display: flex;
      flex-direction: row;
      align-items: flex-start;
      gap: 16px;

      .step-number {
        min-width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: rgb(var(--v-theme-primary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9em;
        flex-shrink: 0;
      }

      .step-content {
        .step-title {
          font-weight: bold;
          font-size: 1em;
          margin: 0 0 4px;
        }

        .step-desc {
          font-size: 0.9em;
          opacity: 0.8;
          line-height: 1.5;
          margin: 0;
        }
      }
    }
  }

  // Tip ボックス
  .tip-box {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    gap: 10px;
    background-color: rgba(255, 193, 7, 0.1);
    border-left: 4px solid #ffc107;
    border-radius: 0 8px 8px 0;
    padding: 12px;
    margin-top: 20px;

    .tip-text {
      font-size: 0.9em;
      line-height: 1.5;
      margin: 0;
    }
  }

  // 最終ページ
  &.final-page {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding-top: 32px;

    .final-icon {
      width: 120px;
      height: 120px;
      margin-bottom: 24px;
    }

    .final-title {
      font-size: 1.8em;
      font-weight: bold;
      color: rgb(var(--v-theme-primary));
      margin-bottom: 16px;
    }

    .final-desc {
      font-size: 1em;
      opacity: 0.8;
      line-height: 1.6;
      max-width: 320px;
      margin-bottom: 24px;
    }

    .final-features {
      display: flex;
      flex-direction: row;
      gap: 24px;
      margin: 8px 0;

      .final-feature {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;

        p {
          font-size: 0.85em;
          margin: 0;
        }
      }
    }
  }
}
</style>
