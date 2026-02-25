<template lang="pug">
div(style="height: 100%; width: 100%")
  //- 地図データ取得中ローディングオーバーレイ
  v-overlay(
    v-model="fetchMapLoading"
    contained
    persistent
    style="display: flex; align-items: center; justify-content: center;"
  )
    v-progress-circular(
      indeterminate
      color="primary"
      size="64"
    )
  //- 地図データが見つからない場合のエラー画面
  .map-not-found(
    v-if="mapNotFound"
    style="position: fixed; inset: 0; z-index: 2000; display: flex; align-items: center; justify-content: center; background-color: rgb(var(--v-theme-background));"
  )
    .top-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
    v-card(
      max-width="400"
      style="width: 90%; text-align: center;"
      elevation="0"
    )
      v-card-text
        v-icon(size="80" color="grey-lighten-1") mdi-map-search
        h2.mt-4(style="font-size: 1.4em; font-weight: 600;") 地図が見つかりません
        p.mt-2(style="color: rgba(var(--v-theme-on-surface), 0.6);") この地図は存在しないか、非公開に設定されている可能性があります。
        p(style="color: rgba(var(--v-theme-on-surface), 0.4); font-size: 0.85em;") ID: {{ $route.params.serverId }}
      v-card-actions(style="justify-content: center;")
        v-btn(
          @click="$router.back()"
          prepend-icon="mdi-arrow-left"
          style="background-color: rgb(var(--v-theme-primary)); color: white;"
        ) 前のページに戻る
  LMap(
    :zoom="leaflet.zoom"
    :center="leaflet.center"
    style="height: calc(100% - 5em); width: 100%"
    :useGlobalLeaflet="false"
    @update:zoom="leaflet.zoom = $event"
    @update:center="leaflet.center = $event"
    @click="onMapClick"
    :options="{ zoomControl: false }"
    ref="map"
    )
    LTileLayer(
      url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
      attribution='&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
    )
    //- 自分の現在地マーカー
    //- LMarker(
      :lat-lng="myLocation"
      )
      LIcon(
        :icon-size="[0,0]"
        style="border: none;"
        :icon-anchor="[16, 16]"
        )
        div(style="display: flex; align-items: center; width: auto;")
          img(
            loading="lazy"
            :src="myProfile?.icon ?? '/account_default.jpg'"
            style="height: 32px; width: 32px; border-radius: 9999px; border: solid 1px #000;"
            onerror="this.src='/account_default.jpg'"
            )
          p.ml-2.name-space(:style="leaflet.zoom >= 15 ? 'opacity: 1;' : 'opacity: 0;'")
            span(v-if="myProfile") {{ myProfile.name ?? myProfile.userId }}
    //- 描画済みの線
    LPolyline(
      v-for="(line, lineIndex) in mapData.lines"
      :key="`line-${lineIndex}`"
      :lat-lngs="catmullRomSpline(line.waypoints.map(w => w.latlng))"
      :color="line.color ?? '#3388ff'"
      :weight="line.width ?? 4"
      @click="editMode ? (selectedLine = line, selectedLineIndex = lineIndex) : detailCardTarget = line"
    )
    //- 編集モード時の経由地点マーカー
    template
      LMarker(
        v-for="(wp, wpIdx) in linesWaypointsFlat"
        :key="`wp-${wp.lineIdx}-${wp.wpIdx}`"
        :lat-lng="wp.latlng"
        :draggable="editMode"
        @click="editMode ? openWaypointEditor(wp.lineIdx, wp.wpIdx) : detailCardTarget = wp.waypoint"
        @dragend="onWaypointDragEnd($event, wp.lineIdx, wp.wpIdx)"
      )
        LIcon(
          :icon-size="[0,0]"
          style="border: none;"
          :icon-anchor="[12, 12]"
        )
          div(style="display: flex; align-items: center; width: auto;")
            .icon-wrap(
              v-if="wp.waypoint.iconMdi"
              style="display: flex; align-items: center; justify-content: center; height: 32px; width: 32px; border-radius: 9999px; background-color: white; border: solid 1px #000;"
            )
              Icon(
                v-if="wp.waypoint.iconMdi"
                :data="wp.waypoint.iconMdi"
                size="24px"
                :color="wp.waypoint.iconColor ? wp.waypoint.iconColor : 'rgb(var(--v-theme-primary))'"
                style="width: 32px; height: 32px; transform: translate(3px, 3px);"
                )
            img(
              v-else-if="wp.waypoint.iconImg"
              loading="lazy"
              :src="wp.waypoint.iconImg ?? '/icons/question.png'"
              style="height: 32px; width: 32px;"
              onerror="this.src='/icons/question.png'"
              )
            p.ml-2.name-space(:style="leaflet.zoom >= 15 ? 'opacity: 1;' : 'opacity: 0; width: 0; overflow: hidden;'")
              span {{ wp.waypoint.name }}
              .wp-dot(
                v-if="!wp.waypoint.iconMdi && !wp.waypoint.iconImg && editMode"
                style="position: absolute; height: 24px; width: 24px; border-radius: 9999px; background-color: white; border: solid 3px #3388ff;"
              )
    //- 編集モード時の経由地点追加ボタン（中間点）
    template(v-if="editMode")
      LMarker(
        v-for="mid in linesMidpointsFlat"
        :key="`mid-${mid.lineIdx}-${mid.afterWpIdx}`"
        :lat-lng="mid.latlng"
        @click="insertWaypointAt(mid.lineIdx, mid.afterWpIdx, mid.latlng)"
      )
        LIcon(
          :icon-size="[0,0]"
          style="border: none;"
          :icon-anchor="[10, 10]"
        )
          .wp-add-btn(style="height: 20px; width: 20px; border-radius: 9999px; background-color: white; border: solid 2px #3388ff; display: flex; align-items: center; justify-content: center; color: #3388ff; font-size: 16px; font-weight: bold; cursor: pointer; line-height: 1;") +
    //- 描画中の線
    LPolyline(
      v-if="drawingLine && drawingLine.waypoints.length >= 2"
      :lat-lngs="catmullRomSpline(drawingLine.waypoints.map(w => w.latlng))"
      color="#ff4444"
      :weight="4"
      :dash-array="'10, 5'"
    )
    //- 描画中の経由地点マーカー
    LMarker(
      v-for="(wp, wpIdx) in (drawingLine ? drawingLine.waypoints : [])"
      :key="`drawing-wp-${wpIdx}`"
      :lat-lng="wp.latlng"
      :draggable="true"
      @dragend="onDrawingWaypointDragEnd($event, wpIdx)"
    )
      LIcon(
        :icon-size="[0,0]"
        style="border: none;"
        :icon-anchor="[7, 7]"
      )
        .wp-dot(style="height: 14px; width: 14px; border-radius: 9999px; background-color: white; border: solid 3px #ff4444;")
    //- 描画済みのポイント
    LMarker(
      v-for="(mapPoint, index) in mapData.points"
      :key="index"
      :lat-lng="mapPoint.latlng"
      :draggable="editMode && !drawingLine"
      @click="!drawingLine ? detailCardTarget = mapPoint : null"
      @dragend="onPointDragEnd($event, index)"
      )
      LIcon(
        :icon-size="[0,0]"
        style="border: none;"
        :icon-anchor="[16, 16]"
        )
        div(style="display: flex; align-items: center; width: auto;")
          .icon-wrap(
            v-if="mapPoint.iconMdi"
            style="display: flex; align-items: center; justify-content: center; height: 32px; width: 32px; border-radius: 9999px; background-color: white; border: solid 1px #000;"
          )
            Icon(
              v-if="mapPoint.iconMdi"
              :data="mapPoint.iconMdi"
              size="24px"
              :color="mapPoint.iconColor ? mapPoint.iconColor : 'rgb(var(--v-theme-primary))'"
              style="width: 32px; height: 32px; transform: translate(3px, 3px);"
              )
          img(
            v-else-if="mapPoint.iconImg"
            loading="lazy"
            :src="mapPoint.iconImg ?? '/icons/question.png'"
            style="height: 32px;"
            onerror="this.src='/icons/question.png'"
            )
          p.ml-2.name-space(:style="leaflet.zoom >= 15 ? 'opacity: 1;' : 'opacity: 0; width: 0; overflow: hidden;'")
            span {{ mapPoint.name }}
    //- 線上の500m毎アイコンマーカー
    .interval-markers(v-if="!editMode")
      LMarker(
        v-for="(marker, mIdx) in linesIntervalMarkers"
        :key="`interval-${marker.lineIdx}-${mIdx}`"
        :lat-lng="marker.latlng"
        @click="detailCardTarget = mapData.lines[marker.lineIdx]"
      )
        LIcon(
          :icon-size="[0,0]"
          style="border: none;"
          :icon-anchor="[16, 16]"
        )
          div(style="display: flex; align-items: center; width: auto;")
            .icon-wrap(
              v-if="mapData.lines[marker.lineIdx] && mapData.lines[marker.lineIdx].iconMdi"
              style="display: flex; align-items: center; justify-content: center; height: 32px; width: 32px; border-radius: 9999px; background-color: white; border: solid 2px #3388ff;"
            )
              Icon(
                :data="mapData.lines[marker.lineIdx].iconMdi"
                size="24px"
                :color="mapData.lines[marker.lineIdx].iconColor ? mapData.lines[marker.lineIdx].iconColor : 'rgb(var(--v-theme-primary))'"
                style="width: 32px; height: 32px; transform: translate(3px, 3px);"
                )
            img(
              v-else-if="mapData.lines[marker.lineIdx] && mapData.lines[marker.lineIdx].iconImg"
              loading="lazy"
              :src="mapData.lines[marker.lineIdx].iconImg"
              style="height: 32px; width: 32px; border-radius: 9999px; border: solid 2px #3388ff;"
              onerror="this.src='/icons/question.png'"
            )
  //-- 下部のアクションバー --
  .action-bar
    .buttons
      .button(
        v-ripple
        @click="setCurrentPosition"
        )
        v-icon mdi-map-marker
        p マップ
      .button(
        v-ripple
        @click="commentDialog = true"
        style="opacity: 0.8;"
        )
        v-icon mdi-comment-outline
        p コメント
      .button(
        v-ripple
        @click="optionsDialog = true"
        style="opacity: 0.8;"
        )
        v-icon mdi-cog
        p サーバー情報
    .bottom-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
  .right-bottom-buttons
    //- 編集モードでないときは編集ボタンを表示
    .current-button
      v-btn(
        v-if="!editMode && isEditorable"
        size="x-large"
        icon
        @click="editMode = true"
        style="background-color: rgba(var(--v-theme-surface), 0.9);"
        )
        v-icon mdi-pencil
      //- 編集モードのときは保存ボタンを表示
      v-btn(
        v-if="editMode && isEditorable"
        size="x-large"
        icon
        @click="optionsDialog = true"
        style="background-color: rgba(var(--v-theme-surface), 0.9);"
        )
        v-icon mdi-content-save
    //-- 右下の現在地ボタン --
    .current-button
      v-btn(
        size="x-large"
        icon
        @click="setCurrentPosition"
        style="background-color: rgb(var(--v-theme-primary)); color: white"
        )
        v-icon mdi-crosshairs-gps
    .bottom-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
  //-- 左上の戻るボタン --
  .left-top-buttons
    .top-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
    .current-button
      v-btn(
        size="x-large"
        icon
        @click="editMode ? editModeEndDialog = true : $router.push('/')"
        style="background-color: rgba(var(--v-theme-surface), 0.9);"
        )
        v-icon mdi-arrow-left
  //-- 右上のオプションボタン --
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
          :src="myProfile && myProfile.icon ? myProfile.icon : '/icons/map.png'"
          style="height: 4em; width: 4em; border-radius: 9999px; border: solid 2px #000; background-color: white;"
          onerror="this.src='/icons/map.png'"
          )
  //- 編集モードであることを表示
  .edit-mode-indicator(
    v-if="editMode && !drawingLine"
    style="position: fixed; top: calc(32px - 6px); left: calc(50% - 5em); z-index: 1000;"
    )
    .top-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
    p.py-2(
      style="width: 10em; text-align: center; background-color: rgba(var(--v-theme-primary), 0.9); color: white; border-radius: 9999px; font-size: 1.2em;"
    ) 編集モード
  //- 線描画中の案内バナー
  .drawing-line-banner(
    v-if="drawingLine"
    style="position: fixed; top: calc(32px - 6px); left: 50%; transform: translateX(-50%); z-index: 1000; white-space: nowrap; text-align: center; background-color: rgba(220, 50, 50, 0.9); color: white; border-radius: 9999px; font-size: 1em; padding: 0.4em 1.2em;"
    )
    .top-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
    p 地図をタップして経由地点を追加
  //- 線描画中の完了・キャンセルボタン
  .drawing-line-actions(
    v-if="drawingLine"
    style="position: fixed; bottom: calc(5em + 16px); left: 50%; transform: translateX(-50%); z-index: 1000; display: flex; gap: 8px; white-space: nowrap;"
    )
    v-btn(
      @click="cancelDrawingLine"
      prepend-icon="mdi-close"
      style="background-color: rgba(var(--v-theme-surface), 0.95);"
    ) キャンセル
    v-btn(
      @click="finishDrawingLine"
      :disabled="!drawingLine || drawingLine.waypoints.length < 2"
      prepend-icon="mdi-check"
      style="background-color: rgb(var(--v-theme-primary)); color: white;"
    ) 完了
  //- 地図で押したポイントの詳細カード
  .detail-card-target
    v-card(
      v-if="detailCardTarget"
      style="position: fixed; bottom: 0; left: 0; z-index: 1000; width: 100%; border-radius: 16px 16px 0 0;"
    )
      v-card-actions
        .ml-2(
          style="display: flex; align-items: center;"
        )
          .icon-wrap.mr-2(
            v-if="detailCardTarget.iconMdi"
            style="display: flex; align-items: center; justify-content: center; height: 2em; width: 2em; border-radius: 9999px; background-color: white; border: solid 1px #000;"
          )
            Icon(
              :data="detailCardTarget.iconMdi"
              size="24px"
              :color="detailCardTarget.iconColor ? detailCardTarget.iconColor : 'rgb(var(--v-theme-primary))'"
              style="width: 32px; height: 32px; transform: translate(3px, 3px);"
              )
          img.mr-2(
            v-else-if="detailCardTarget.iconImg"
            loading="lazy"
            :src="detailCardTarget.iconImg && detailCardTarget.iconImg.length ? detailCardTarget.iconImg : '/icons/question.png'"
            style="height: 1.5em; min-width: 1.5em;"
            onerror="this.src='/icons/question.png'"
            )
          span {{ editMode ? `${detailCardTarget.name}を編集` : detailCardTarget.name ?? '無題' }}
        v-spacer
        v-btn(
          text
          @click="detailCardTarget = null"
          icon="mdi-close"
          )
      v-card-text
        .infos(
          v-if="!editMode"
        )
          .info
            v-icon mdi-map-marker
            p {{ detailCardTarget.description ?? '説明なし' }}
          .info(v-if="detailCardTarget.waypoints")
            v-icon mdi-map-marker-distance
            p 総距離: {{ formatDistance(calcLineDistance(detailCardTarget.waypoints.map(w => w.latlng))) }}
          .info
            v-icon mdi-account
            p {{ detailCardTarget.authorUserId ?? '不明なユーザー' }}
        .edit-form(
          v-else
        )
          .info
            v-icon mdi-map-marker
            p
              span 登録者:
              b.ml-2 {{ detailCardTarget.authorUserId ? `@${detailCardTarget.authorUserId}` : '不明なユーザー' }}
          .info
            v-icon mdi-image
            .icon-settings(
              style="width: 100%; display: flex; flex-direction: column; align-items: flex-start;"
            )
              v-select(
                :model-value="detailCardTarget.iconImg ? 'img' : 'svg'"
                @update:model-value="onPointIconTypeChange"
                :items="[{ title: 'SVG（アイコン）', value: 'svg' }, { title: '画像', value: 'img' }]"
                label="アイコン種別"
                variant="outlined"
                density="compact"
                style="width: 100%;"
              )
              template(v-if="!detailCardTarget.iconImg")
                Vue3IconPicker(
                  v-model="detailCardTarget.iconMdi"
                  searchPlaceholder="アイコン名で検索…"
                  placeholder="アイコンを選択"
                  :theme="$vuetify.theme.global.name"
                  selectedIconBgColor="rgb(var(--v-theme-primary))"
                  selectedIconColor="white"
                  inputSize="large"
                  valueType="name"
                  iconLibrary="all"
                )
                p アイコン名: {{ detailCardTarget.iconMdi }}
              template(v-else)
                v-btn(
                  variant="outlined"
                  style="width: 100%;"
                  @click="iconImgDialog = true; iconImgDialogTarget = 'point'"
                )
                  img.mr-2(
                    :src="detailCardTarget.iconImg"
                    style="height: 1.5em; width: 1.5em; object-fit: contain;"
                  )
                  | 画像を変更
          .info.mt-4.mb-6
            v-icon mdi-palette
            v-btn(
              :style="`background-color: ${detailCardTarget.iconColor ? detailCardTarget.iconColor : 'rgb(var(--v-theme-primary))'}; color: white;`"
            ) 色を選択
              v-menu(
                activator="parent"
                location="bottom"
              )
                v-color-picker(
                  v-model="detailCardTarget.iconColor"
                  show-swatches
                  hide-canvas
                  hide-inputs
                  hide-mode-switch
                  mode="hexa"
                  @click.stop
                )
          .info
            v-icon mdi-tag
            v-text-field(
              v-model="detailCardTarget.name"
              label="名前"
              placeholder="例: 東京タワー"
              variant="outlined"
              style="flex: 1;"
              clearable
            )
          .info
            v-icon mdi-text-box-edit-outline
            v-textarea(
              v-model="detailCardTarget.description"
              label="説明"
              placeholder="例: 東京タワーの説明"
              variant="outlined"
              style="flex: 1;"
              clearable
              auto-grow
              rows="1"
              max-rows="5"
            )
        v-btn.my-2(
          v-if="!editMode && !detailCardTarget.waypoints"
          text
          @click="openGoogleMaps(detailCardTarget.location)"
          prepend-icon="mdi-map-marker"
          style="background-color: rgb(var(--v-theme-primary)); width: 100%;"
        ) Google Mapsで開く
        v-btn.my-2(
          v-if="editMode"
          text
          @click="startDrawingLine(detailCardTarget)"
          prepend-icon="mdi-vector-line"
          style="background-color: rgb(var(--v-theme-secondary)); color: white; width: 100%;"
        ) ここから線を描画
        v-btn.my-2(
          v-if="editMode && !detailCardTarget.waypoints"
          text
          @click="duplicatePoint"
          prepend-icon="mdi-content-copy"
          style="background-color: rgb(var(--v-theme-secondary)); color: white; width: 100%;"
        ) 複製
        v-btn.my-2(
          v-if="editMode"
          text
          @click="deletePointDialog = true"
          prepend-icon="mdi-delete"
          style="background-color: rgb(var(--v-theme-error)); width: 100%;"
        ) 削除
        v-btn.my-2(
          v-if="editMode"
          text
          @click="detailCardTarget = null"
          prepend-icon="mdi-check"
          style="background-color: rgb(var(--v-theme-primary)); width: 100%;"
        ) 閉じる
        .my-4
  //-- オプションダイアログ --
  v-dialog(
    v-model="optionsDialog"
    transition="dialog-bottom-transition"
    fullscreen
  )
    v-card(
      style="width: 100%; height: 100%;"
    )
      .top-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
      v-card-actions
        p.ml-2(class="headline" style="font-size: 1.3em") サーバー情報
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
              :src="mapData.icon ? mapData.icon : '/icons/map.png'"
              style="height: 8em; width: 8em; border-radius: 9999px; background-color: white; border: solid 2px #000;"
              onerror="this.src='/icons/map.png'"
              )
          .account-info(
            style="text-align: center;"
            v-if="!editMode"
          )
            p(
              style="font-size: 1.2em; margin: 0; padding: 0;"
              ) {{ mapData.name ? mapData.name : '無題の地図' }}
            p(style="margin: 0; padding: 0;")
              | {{ mapData.serverId }}
            p(style="margin: 0; padding: 0;")
              | {{ mapData.description ? mapData.description : '説明なし' }}
        .server-info-form(
          v-if="!editMode"
          style="display: flex; flex-direction: column; gap: 1em;"
        )
          p 公開設定: {{ mapData.isPublic ? '公開' : '非公開' }}
          p 閲覧可能ユーザーIDリスト: {{ mapData.sharedUserIds.length ? mapData.sharedUserIds.join(', ') : 'なし' }}
          p 編集可能ユーザーIDリスト: {{ mapData.editorUserIds.length ? mapData.editorUserIds.join(', ') : 'なし' }}
          v-btn.my-2(
            v-if="isEditorable"
            text
            @click="editMode = true"
            append-icon="mdi-pencil"
            style="background-color: rgb(var(--v-theme-primary)); color: white;"
          ) 編集する
        .server-info-form(
          v-if="editMode"
          style="display: flex; flex-direction: column; gap: 1em;"
        )
          v-text-field(
            v-model="mapData.name"
            :disabled="mapData.ownerUserId !== myProfile.userId"
            label="地図の名前"
            placeholder="例: 東京観光地図"
            variant="outlined"
            clearable
          )
          v-text-field(
            v-model="mapData.serverId"
            :disabled="mapData.ownerUserId !== myProfile.userId"
            label="地図のID（URLに表示されます）"
            placeholder="例: tokyo-tourist-map"
            variant="outlined"
            clearable
          )
          v-textarea(
            v-model="mapData.description"
            label="地図の説明"
            placeholder="例: 東京の観光地をまとめた地図です。"
            variant="outlined"
            clearable
            auto-grow
            rows="1"
            max-rows="5"
          )
          .for-guest.mb-4
            v-alert(
              v-if="myProfile.guest"
              type="warning"
              variant="outlined"
            ) 以下の設定を使用するには、ログインが必要です。
          v-select(
            v-model="mapData.isPublic"
            :disabled="mapData.ownerUserId !== myProfile.userId || myProfile.guest"
            :items="[{ title: '非公開', value: false },{ title: '公開', value: true }]"
            label="公開設定"
            variant="outlined"
          )
          v-combobox(
            v-if="!mapData.isPublic"
            :disabled="mapData.ownerUserId !== myProfile.userId || myProfile.guest"
            v-model="mapData.sharedUserIds"
            label="閲覧可能ユーザーIDリスト（エンター区切り）"
            placeholder="例: user1,user2,user3"
            variant="outlined"
            chips
            multiple
            clearable
          )
          v-combobox(
            v-model="mapData.editorUserIds"
            :disabled="mapData.ownerUserId !== myProfile.userId || myProfile.guest"
            label="編集可能ユーザーIDリスト（エンター区切り）"
            placeholder="例: user1,user2,user3"
            variant="outlined"
            chips
            multiple
            clearable
          )
          v-text-field(
            v-if="settings.developerOptions.enabled"
            v-model="mapData.icon"
            label="地図のアイコン画像URL（開発者オプション）"
            placeholder="例: https://example.com/icon.png"
            variant="outlined"
            clearable
          )
          v-btn.my-2(
            v-else
            text
            @click="save"
            append-icon="mdi-content-save"
            style="background-color: rgb(var(--v-theme-primary)); color: white;"
          ) 保存
          hr.my-4
        v-alert.mb-2(
          v-if="serverIdConflict"
          type="warning"
          variant="outlined"
          density="compact"
        ) このサーバーIDは他のユーザーが使用中のため、アップロードできません。別のIDを使用してください。
        v-btn.my-2(
          v-if="isEditorable"
          :disabled="mapData.serverId === '' || mapData.name === '' || myProfile.guest || serverIdConflict"
          :loading="uploadLoading"
          text
          @click="save"
          append-icon="mdi-upload"
          style="background-color: rgb(var(--v-theme-primary)); color: white; width: 100%;"
        ) サーバーにアップロード
        v-list.options-list
          v-list-item.item(
            @click="toggleFavorite"
            v-if="!myProfile.guest"
            )
            .icon-and-text
              v-icon(:color="isFavorite ? 'pink' : ''") {{ isFavorite ? 'mdi-heart' : 'mdi-heart-outline' }}
              v-list-item-title {{ isFavorite ? 'お気に入りから削除' : 'お気に入りに追加' }}
          v-list-item.item(
            @click="share(`https://map.enoki.xyz/map/${mapData.serverId}`, mapData.name)"
            v-if="!myProfile.guest"
            )
            .icon-and-text
              v-icon mdi-share-variant
              v-list-item-title この地図を共有する
          v-list-item.item(
            @click="mapQrDialog = true; optionsDialog = false"
            v-if="!myProfile.guest"
            )
            .icon-and-text
              v-icon mdi-qrcode
              v-list-item-title この地図のQRコードを表示
          v-list-item.item(
            @click="openJsonExportDialog"
            )
            .icon-and-text
              v-icon mdi-export
              v-list-item-title JSONエクスポート
          v-list-item.item(
            @click="jsonImportDialog = true; optionsDialog = false"
            )
            .icon-and-text
              v-icon mdi-import
              v-list-item-title JSONインポート
          v-list-item.item(
            @click="openImportFromMapDialog"
            )
            .icon-and-text
              v-icon mdi-map-plus
              v-list-item-title この地図からインポート
          v-alert.mt-4(
            v-if="!mapData.isPublic"
            type="warning"
            variant="outlined"
          )
            span この地図は非公開です。
            br
            span 閲覧・編集できるユーザーのみがアクセスできます。
            br
            span(
              v-if="!myProfile.guest"
            ) 公開設定を変更するには、上記の「公開設定」から「公開」を選択してください。
            span(
              v-else
            ) 公開設定を変更するには、ログインしてから「公開」を選択してください。
  //- 編集モードを終了するか確認するダイアログ --
  v-dialog(
    v-model="editModeEndDialog"
    persistent
    max-width="400"
  )
    v-card
      v-card-title(class="headline") 編集モードを終了しますか？
      v-card-text
        .text-content
          p 編集内容は自動で保存されます。（初回保存を除く）
          .my-2
          p 続行するには、以下の「ええで！」を押してください。
      v-card-actions
        v-spacer
        v-btn(
          text
          @click="editModeEndDialog = false"
          prepend-icon="mdi-close"
          ) いやだ！
        v-btn(
          style="background-color: rgb(var(--v-theme-primary)); color: white"
          text
          @click="editMode = false; editModeEndDialog = false"
          prepend-icon="mdi-check"
          ) ええで！
  //- 点を削除するか確認するダイアログ --
  v-dialog(
    v-model="deletePointDialog"
    persistent
    max-width="400"
  )
    v-card
      v-card-title(class="headline") この点を削除しますか？
      v-card-text
        .text-content
          p 削除した点は元に戻せません。
          .my-2
          p 続行するには、以下の「削除する」を押してください。
      v-card-actions
        v-spacer
        v-btn(
          text
          @click="deletePointDialog = false"
          prepend-icon="mdi-close"
          ) キャンセル
        v-btn(
          style="background-color: rgb(var(--v-theme-error)); color: white"
          text
          @click="deletePoint"
          prepend-icon="mdi-delete"
          ) 削除する
  //- 線を削除するか確認するダイアログ --
  v-dialog(
    v-model="deleteLineDialog"
    persistent
    max-width="400"
  )
    v-card
      v-card-title(class="headline") この線を削除しますか？
      v-card-text
        .text-content
          p 削除した線は元に戻せません。
          .my-2
          p 続行するには、以下の「削除する」を押してください。
      v-card-actions
        v-spacer
        v-btn(
          text
          @click="deleteLineDialog = false"
          prepend-icon="mdi-close"
          ) キャンセル
        v-btn(
          style="background-color: rgb(var(--v-theme-error)); color: white"
          text
          @click="deleteLine"
          prepend-icon="mdi-delete"
          ) 削除する
  //- 経由地点を削除するか確認するダイアログ --
  v-dialog(
    v-model="deleteWaypointDialog"
    persistent
    max-width="400"
  )
    v-card
      v-card-title(class="headline") この経由地点を削除しますか？
      v-card-text
        .text-content
          p 削除した経由地点は元に戻せません。
          .my-2
          p 続行するには、以下の「削除する」を押してください。
      v-card-actions
        v-spacer
        v-btn(
          text
          @click="deleteWaypointDialog = false"
          prepend-icon="mdi-close"
          ) キャンセル
        v-btn(
          style="background-color: rgb(var(--v-theme-error)); color: white"
          text
          @click="deleteWaypoint"
          prepend-icon="mdi-delete"
          ) 削除する
  //- 選択した線の編集カード
  .selected-line-card(v-if="selectedLineCardIsActive")
    v-card(
      style="position: fixed; bottom: 0; left: 0; z-index: 1000; width: 100%; border-radius: 16px 16px 0 0;"
    )
      v-card-actions
        .ml-2(
          style="display: flex; align-items: center;"
        )
          .icon-wrap.mr-2(
            v-if="selectedLine.iconMdi"
            style="display: flex; align-items: center; justify-content: center; height: 2em; width: 2em; border-radius: 9999px; background-color: white; border: solid 1px #000;"
          )
            Icon(
              :data="selectedLine.iconMdi"
              size="24px"
              :color="selectedLine.iconColor ? selectedLine.iconColor : 'rgb(var(--v-theme-primary))'"
              style="width: 32px; height: 32px; transform: translate(3px, 3px);"
              )
          img.mr-2(
            v-else-if="selectedLine.iconImg"
            loading="lazy"
            :src="selectedLine.iconImg && selectedLine.iconImg.length ? selectedLine.iconImg : '/icons/question.png'"
            style="height: 1.5em; width: 1.5em; border-radius: 9999px;"
            onerror="this.src='/icons/question.png'"
            )
          span {{ selectedLine.name ? selectedLine.name : '線' }}を編集
        v-spacer
        v-btn(
          text
          @click="selectedLine = null; selectedLineIndex = -1"
          icon="mdi-close"
          )
      v-card-text
        .edit-form.detail-card-target
          .info
            v-icon mdi-account
            p
              span 登録者:
              b.ml-2 {{ selectedLine.authorUserId ? `@${selectedLine.authorUserId}` : '不明なユーザー' }}
          .info
            v-icon mdi-image
            .icon-settings(
              style="width: 100%; display: flex; flex-direction: column; align-items: flex-start;"
            )
              v-select(
                :model-value="selectedLine.iconImg ? 'img' : 'svg'"
                @update:model-value="onLineIconTypeChange"
                :items="[{ title: 'SVG（アイコン）', value: 'svg' }, { title: '画像', value: 'img' }]"
                label="アイコン種別"
                variant="outlined"
                density="compact"
                style="width: 100%;"
              )
              template(v-if="!selectedLine.iconImg")
                Vue3IconPicker(
                  v-model="selectedLine.iconMdi"
                  searchPlaceholder="アイコン名で検索…"
                  placeholder="アイコンを選択"
                  :theme="$vuetify.theme.global.name"
                  selectedIconBgColor="rgb(var(--v-theme-primary))"
                  selectedIconColor="white"
                  inputSize="large"
                  valueType="name"
                  iconLibrary="all"
                )
                p アイコン名: {{ selectedLine.iconMdi }}
              template(v-else)
                v-btn(
                  variant="outlined"
                  style="width: 100%;"
                  @click="iconImgDialog = true; iconImgDialogTarget = 'line'"
                )
                  img.mr-2(
                    :src="selectedLine.iconImg"
                    style="height: 1.5em; width: 1.5em; object-fit: contain;"
                  )
                  | 画像を変更
          .info
            v-icon mdi-tag
            v-text-field(
              v-model="selectedLine.name"
              label="名前"
              placeholder="例: ルート1"
              variant="outlined"
              style="flex: 1;"
              clearable
            )
          .info
            v-icon mdi-text-box-edit-outline
            v-textarea(
              v-model="selectedLine.description"
              label="説明"
              placeholder="例: ルート1の説明"
              variant="outlined"
              style="flex: 1;"
              auto-grow
              rows="1"
              clearable
            )
          .info
            v-icon mdi-text-box-edit-outline
            v-number-input(
              v-model="selectedLine.width"
              label="線の幅（px）"
              placeholder="例: 4"
              variant="outlined"
              style="flex: 1;"
              :min="1"
              :max="20"
            )
          .info.my-2
            v-icon mdi-palette
            v-btn(
              :style="`background-color: ${selectedLine.color ? selectedLine.color : '#3388ff'}; color: white;`"
            ) 色を選択
              v-menu(
                activator="parent"
                location="bottom"
              )
                v-color-picker(
                  v-model="selectedLine.color"
                  show-swatches
                  hide-canvas
                  hide-inputs
                  hide-mode-switch
                  mode="hexa"
                  @click.stop
                )
        v-btn.my-2(
          text
          @click="deleteLineDialog = true"
          prepend-icon="mdi-delete"
          style="background-color: rgb(var(--v-theme-error)); width: 100%;"
        ) 削除
        v-btn.my-2(
          text
          @click="selectedLine = null; selectedLineIndex = -1"
          prepend-icon="mdi-check"
          style="background-color: rgb(var(--v-theme-primary)); width: 100%;"
        ) 閉じる
        .my-4
  //- 選択した経由地点の編集カード
  .selected-waypoint-card(v-if="selectedWaypointIsActive")
    v-card(
      style="position: fixed; bottom: 0; left: 0; z-index: 1001; width: 100%; border-radius: 16px 16px 0 0;"
    )
      v-card-actions
        p.ml-2(style="display: flex; align-items: center;")
          v-icon.mr-2 mdi-map-marker-path
          span {{ selectedWaypoint.name ? selectedWaypoint.name : '経由地点' }}を編集
        v-spacer
        v-btn(
          text
          @click="selectedWaypoint = null; selectedWaypointLineIdx = -1; selectedWaypointWpIdx = -1"
          icon="mdi-check"
          )
      v-card-text
        .edit-form.detail-card-target
          .info
            v-icon mdi-image
            .icon-settings(
              style="width: 100%; display: flex; flex-direction: column; align-items: flex-start;"
            )
              v-select(
                :model-value="selectedWaypoint.iconImg ? 'img' : 'svg'"
                @update:model-value="onWaypointIconTypeChange"
                :items="[{ title: 'SVG（アイコン）', value: 'svg' }, { title: '画像', value: 'img' }]"
                label="アイコン種別"
                variant="outlined"
                density="compact"
                style="width: 100%;"
              )
              template(v-if="!selectedWaypoint.iconImg")
                Vue3IconPicker(
                  v-model="selectedWaypoint.iconMdi"
                  searchPlaceholder="アイコン名で検索…"
                  placeholder="アイコンを選択"
                  :theme="$vuetify.theme.global.name"
                  selectedIconBgColor="rgb(var(--v-theme-primary))"
                  selectedIconColor="white"
                  inputSize="large"
                  valueType="name"
                  iconLibrary="all"
                )
                p アイコン名: {{ selectedWaypoint.iconMdi }}
              template(v-else)
                v-btn(
                  variant="outlined"
                  style="width: 100%;"
                  @click="iconImgDialog = true; iconImgDialogTarget = 'waypoint'"
                )
                  img.mr-2(
                    :src="selectedWaypoint.iconImg"
                    style="height: 1.5em; width: 1.5em; object-fit: contain;"
                  )
                  | 画像を変更
          .info.mt-4.mb-6
            v-icon mdi-palette
            v-btn(
              :style="`background-color: ${selectedWaypoint.iconColor ? selectedWaypoint.iconColor : 'rgb(var(--v-theme-primary))'}; color: white;`"
            ) 色を選択
              v-menu(
                activator="parent"
                location="bottom"
              )
                v-color-picker(
                  v-model="selectedWaypoint.iconColor"
                  show-swatches
                  hide-canvas
                  hide-inputs
                  hide-mode-switch
                  mode="hexa"
                  @click.stop
                )
          .info
            v-icon mdi-tag
            v-text-field(
              v-model="selectedWaypoint.name"
              label="名前"
              placeholder="例: 休憩ポイント"
              variant="outlined"
              style="flex: 1;"
              clearable
            )
          .info
            v-icon mdi-text-box-edit-outline
            v-textarea(
              v-model="selectedWaypoint.description"
              label="説明"
              placeholder="例: 公園の入口"
              variant="outlined"
              style="flex: 1;"
              clearable
              auto-grow
              rows="1"
              max-rows="5"
            )
        v-btn.my-2(
          text
          @click="deleteWaypointDialog = true"
          prepend-icon="mdi-delete"
          style="background-color: rgb(var(--v-theme-error)); width: 100%;"
        ) 削除
        v-btn.my-2(
          text
          @click="selectedWaypoint = null; selectedWaypointLineIdx = -1; selectedWaypointWpIdx = -1"
          prepend-icon="mdi-check"
          style="background-color: rgb(var(--v-theme-primary)); width: 100%;"
        ) 閉じる
        .my-4
  //-- 位置情報利用許可ダイアログ --
  v-dialog(
    v-model="requestGeoPermissionDialog"
    persistent
    max-width="400"
  )
    v-card
      v-card-title(class="headline") 位置情報利用のお願い
      v-card-text
        .text-content
          p このアプリは現在地を表示するために位置情報を利用します。
          .my-2
          p 続行するには、以下の「ええで！」を押してください。
        .privacy-policy-link.mt-4
          p
            span 詳細は
            a.pa-2.ma-2(
              @click="openURL('https://enoki.xyz/privacy')"
              style="background-color: rgb(var(--v-theme-primary)); color: white; cursor: pointer; border-radius: 8px;"
            ) プライバシーポリシー
            span をご覧ください。
      v-card-actions
        v-spacer
        v-btn(
          text
          @click="requestGeoPermissionDialog = false; requestBackgroundDialog = true"
          prepend-icon="mdi-close"
          ) 嫌だ！
        v-btn(
          style="background-color: rgb(var(--v-theme-primary)); color: white"
          text
          @click="requestGeoPermission"
          prepend-icon="mdi-check"
          ) ええで！
  //- 画像アイコン選択ダイアログ
  v-dialog(
    v-model="iconImgDialog"
    max-width="500"
  )
    v-card
      v-card-title 画像を選択
      v-card-text
        v-row
          v-col(
            v-for="img in iconImages"
            :key="img.path"
            cols="4"
            class="text-center pa-2"
            style="cursor: pointer;"
            @click="selectIconImg(img.path)"
          )
            v-card.pa-2(elevation="1")
              img(
                :src="img.path"
                style="height: 3em; width: 3em; object-fit: contain; display: block; margin: 0 auto;"
              )
              p.text-body-2.mt-1 {{ img.name }}
              p.text-caption {{ img.description }}
      v-card-actions
        v-spacer
        v-btn(@click="iconImgDialog = false") キャンセル
  //- 所有権移転確認ダイアログ
  v-dialog(
    v-model="claimOwnershipDialog"
    max-width="400"
  )
    v-card
      v-card-title(class="headline") この地図の所有権を取得しますか？
      v-card-text
        .text-content
          p この地図のオーナーがゲストのままです。
          .my-2
          p 現在のアカウント（{{ myProfile.userId }}）に所有権を移してサーバーにアップロードするか、ゲストのままローカルにのみ保存するか選択してください。
      v-card-actions
        v-spacer
        .btns(style="display: grid; gap: 8px;")
          v-btn(
            text
            @click="claimOwnershipDialog = false; performLocalSave()"
            prepend-icon="mdi-content-save"
          ) ローカルのみ保存
          v-btn(
            style="background-color: rgb(var(--v-theme-primary)); color: white"
            text
            @click="claimOwnershipAndSave"
            prepend-icon="mdi-account-check"
          ) 所有権を取得してアップロード
  //- QRコードダイアログ
  v-dialog(
    v-model="mapQrDialog"
    max-width="400"
  )
    v-card
      v-card-title この地図のQRコード
      v-card-text
        p.mb-4 以下のURLを共有してください
        pre.pa-4(style="border-radius: 8px; word-break: break-all; max-width: calc(100vw - 40px); overflow-x: auto;") {{ mapQrUrl }}
        .canvas-area.my-4(
          style="display: flex; justify-content: center;"
          v-show="!qrLoading"
          )
          canvas#map-qr-canvas(style="border-radius: 10%; max-width: 20em; max-height: 20em;")
        .qr-loading.my-4(
          v-show="qrLoading"
          style="display: flex; justify-content: center; width: 70vw; height: 70vw; max-width: 20em; max-height: 20em; background-color: white; border-radius: 10%; display: flex; flex-direction: column; align-items: center; justify-content: center; justify-self: center;"
        )
          v-progress-circular.my-4(
            indeterminate
            :size="64"
            color="black"
            )
          p.my-4(
            style="color: black;"
          ) QRコード読み込み中…
      v-card-actions
        v-btn(
          @click="share(mapQrUrl, mapData.name)"
          prepend-icon="mdi-share-variant"
          style="background-color: rgb(var(--v-theme-primary)); color: white;"
        ) 共有
        v-spacer
        v-btn(@click="mapQrDialog = false" prepend-icon="mdi-close") 閉じる
  //- JSONエクスポートダイアログ
  v-dialog(
    v-model="jsonExportDialog"
    max-width="600"
  )
    v-card
      v-card-title JSONエクスポート
      v-card-text
        p.mb-2(style="font-size: 0.85em; color: rgba(var(--v-theme-on-surface), 0.6);") 以下のJSONをコピーするか、ファイルとしてダウンロードしてください。
        v-textarea(
          :model-value="jsonExportContent"
          readonly
          variant="outlined"
          rows="10"
          auto-grow
          max-rows="20"
          style="font-family: monospace; font-size: 0.8em;"
        )
      v-card-actions
        v-btn(
          @click="downloadJsonExport"
          prepend-icon="mdi-download"
          style="background-color: rgb(var(--v-theme-primary)); color: white;"
        ) ダウンロード
        v-spacer
        v-btn(@click="jsonExportDialog = false" prepend-icon="mdi-close") 閉じる
  //- JSONインポートダイアログ
  v-dialog(
    v-model="jsonImportDialog"
    max-width="600"
  )
    v-card
      v-card-title JSONインポート
      v-card-text
        v-alert.mb-4(
          type="warning"
          variant="outlined"
          density="compact"
        )
          | JSONの点と線のデータを現在の地図にマージします。既存のデータは削除されません。
          br
          | インポート後はサーバーに自動で同期されます。
        v-textarea(
          v-model="jsonImportContent"
          variant="outlined"
          label="JSONを貼り付けるか、ファイルをアップロードしてください"
          rows="10"
          auto-grow
          max-rows="20"
          style="font-family: monospace; font-size: 0.8em;"
        )
        v-btn(
          @click="triggerJsonFileUpload"
          prepend-icon="mdi-upload"
          variant="outlined"
          style="width: 100%;"
        ) JSONファイルをアップロード
        input(
          ref="jsonFileInput"
          type="file"
          accept=".json"
          style="display: none;"
          @change="onJsonFileSelected"
        )
      v-card-actions
        v-btn(
          @click="performJsonImport"
          :loading="jsonImportLoading"
          prepend-icon="mdi-import"
          style="background-color: rgb(var(--v-theme-primary)); color: white;"
        ) インポート
        v-spacer
        v-btn(@click="jsonImportDialog = false" prepend-icon="mdi-close") キャンセル
  //- この地図からインポートダイアログ
  v-dialog(
    v-model="importFromMapDialog"
    max-width="600"
  )
    v-card
      v-card-title この地図からインポート
      v-card-text
        v-alert.mb-4(
          type="info"
          variant="outlined"
          density="compact"
        )
          | 選択した地図の点と線のデータを現在の地図にマージします。既存のデータは削除されません。
          br
          | インポート後はサーバーに自動で同期されます。
        p.mb-2(v-if="importableMapList.length === 0" style="color: rgba(var(--v-theme-on-surface), 0.6);") インポート可能な地図がありません。
        v-list(v-else)
          v-list-item(
            v-for="m in importableMapList"
            :key="m.serverId"
            @click="performImportFromMap(m)"
            :disabled="importFromMapLoading"
            style="cursor: pointer; border-radius: 8px;"
          )
            v-list-item-title {{ m.name || '無題の地図' }}
            v-list-item-subtitle {{ m.serverId }}
      v-card-actions
        v-spacer
        v-btn(@click="importFromMapDialog = false" prepend-icon="mdi-close") 閉じる
  //- コメントダイアログ
  v-dialog(
    v-model="commentDialog"
    transition="dialog-bottom-transition"
    fullscreen
  )
    v-card(style="width: 100%; height: 100%;")
      .top-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
      v-card-actions
        p.ml-2(style="font-size: 1.3em") コメント
        v-spacer
        v-btn(text @click="commentDialog = false" icon="mdi-close")
      v-card-text(
        style="height: calc(100% - 8em); overflow: hidden; position: relative;"
        )
        v-alert.mb-4(
          v-if="myProfile.guest"
          type="info"
          variant="outlined"
        ) コメントするにはログインが必要です。
        v-progress-linear(v-if="commentsLoading" indeterminate)
        .comments-list(
          v-show="comments.length"
          style="height: calc(100% - 200px); overflow-y: auto;"
        )
          .comment-item.mb-3(
            v-for="comment in comments"
            :key="comment.id"
            style="padding: 1em; border-radius: 12px; background-color: rgba(var(--v-theme-surface-variant), 0.5);"
          )
            .comment-header(style="display: flex; align-items: center; gap: 0.5em; margin-bottom: 0.5em;")
              v-icon(size="small") mdi-account
              span(style="font-weight: 500;") {{ comment.userId }}
              v-spacer
              span(style="font-size: 0.85em; opacity: 0.7;") {{ new Date(comment.createdAt * 1000).toLocaleString() }}
            p(style="white-space: pre-wrap; word-break: break-word; max-height: 7em; overflow: auto;") {{ comment.comment }}
        p.text-center.ma-16(
          v-if="!commentsLoading && comments.length === 0"
          style="opacity: 0.6;"
          ) コメントはまだありません
          .bottom-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
        .comment-form.mb-4.mx-2(
          v-if="!myProfile.guest"
          style="position: absolute; bottom: 1em; left: 1em; right: 1em; background-color: rgba(var(--v-theme-surface), 1);"
          )
          v-textarea.mb-2(
            v-model="newComment"
            label="コメントを入力"
            placeholder="例: この場所はとても良かったです！"
            variant="outlined"
            auto-grow
            rows="2"
            max-rows="5"
            :counter="1000"
            clearable
            @keydown.ctrl.enter="submitComment"
          )
          v-btn(
            :disabled="!newComment || newComment.length === 0 || newComment.length > 1000 || commentLoading"
            :loading="commentLoading"
            @click="submitComment"
            style="background-color: rgb(var(--v-theme-primary)); color: white; width: 100%;"
            prepend-icon="mdi-send"
          ) 送信
          .bottom-android-15-or-higher(v-if="settings.hidden.isAndroid15OrHigher")
</template>

<script lang="ts">
  import { App } from '@capacitor/app'
  import { Browser } from '@capacitor/browser'
  import { Capacitor } from '@capacitor/core'
  import { Directory, Encoding, Filesystem } from '@capacitor/filesystem'
  import { Geolocation } from '@capacitor/geolocation'
  import { Share } from '@capacitor/share'
  import { Toast } from '@capacitor/toast'

  import { LIcon, LMap, LMarker, LPolyline, LTileLayer } from '@vue-leaflet/vue-leaflet'
  import { defineComponent } from 'vue'
  import { Icon, Vue3IconPicker } from 'vue3-icon-picker'
  import { iconImages } from '@/js/iconImages'
  import muniArray from '@/js/muni'
  // @ts-ignore
  import mixins from '@/mixins/mixins'
  import { type Map, useMapsStore } from '@/stores/maps'
  import { useMyProfileStore } from '@/stores/myProfile'
  import { useSettingsStore } from '@/stores/settings'
  import 'leaflet/dist/leaflet.css'
  import 'vue3-icon-picker/dist/style.css'

  type Waypoint = {
    name: string | undefined
    description: string | undefined
    iconImg: string | undefined
    iconMdi: string | undefined
    iconColor: string | undefined
    latlng: [number, number]
  }

  export default defineComponent({
    components: {
      LMap,
      LMarker,
      LPolyline,
      LTileLayer,
      LIcon,
      Vue3IconPicker,
      Icon,
    },
    mixins: [mixins],
    data () {
      return {
        /** Leafletの設定 */
        leaflet: {
          zoom: 13,
          center: [35.690_430_765_555_42, 139.700_211_526_229_54],
        },
        /** 位置情報利用許可ダイアログの表示フラグ */
        requestGeoPermissionDialog: false,
        /** 自分の現在地 */
        myLocation: [0, 0],
        /** 詳細カードのターゲット */
        detailCardTarget: null as {
          iconMdi: string | undefined
          iconImg: string | undefined
          iconColor: string | undefined
          name: string | undefined
          description: string | undefined
          authorUserId: string | undefined
          waypoints?: { latlng: [number, number] }[]
          location?: string
        } | null,
        /** 詳細カードに現在の住所を表示 */
        detailCardTargetAddress: null as string | null,
        /** 自分のプロフィール */
        myProfile: useMyProfileStore(),
        /** 環境変数 */
        env: null as any,
        /** 設定ストア */
        settings: useSettingsStore(),
        maps: useMapsStore(),
        /** 編集モード */
        editMode: false,
        /** 編集モードを終了するか確認するダイアログの表示フラグ */
        editModeEndDialog: false,
        /** 点を削除するか確認するダイアログの表示フラグ */
        deletePointDialog: false,
        /** 線を削除するか確認するダイアログの表示フラグ */
        deleteLineDialog: false,
        /** 経由地点を削除するか確認するダイアログの表示フラグ */
        deleteWaypointDialog: false,
        /** オプションダイアログの表示フラグ */
        optionsDialog: false,
        /** 地図データ */
        mapData: {
          serverId: '',
          icon: '',
          isPublic: false,
          description: '',
          ownerUserId: '',
          name: '',
          points: [],
          lines: [],
          createdAt: undefined,
          sharedUserIds: [],
          editorUserIds: [],
        } as Map,
        /** 保存ダイアログの表示フラグ */
        savedDialog: false,
        /** ルートパラメータ（URLパラメータ） */
        params: '',
        /** 描画中の線 */
        drawingLine: null as { waypoints: Waypoint[] } | null,
        /** 編集中の線 */
        selectedLine: null as {
          name: string | undefined
          description: string | undefined
          iconImg: string | undefined
          iconMdi: string | undefined
          waypoints: Waypoint[]
          color: string | undefined
          authorUserId: string | undefined
          width: number | undefined } | null,
        /** 編集中の線のインデックス */
        selectedLineIndex: -1,
        /** 編集中の経由地点 */
        selectedWaypoint: null as Waypoint | null,
        /** 編集中の経由地点が属する線のインデックス */
        selectedWaypointLineIdx: -1,
        /** 編集中の経由地点のインデックス */
        selectedWaypointWpIdx: -1,
        /** 最後に操作したピンのスタイル */
        lastPointStyle: null as {
          iconMdi: string | undefined
          iconColor: string | undefined
          iconDescription: string | undefined
          iconName: string | undefined
          iconImg: string | undefined } | null,
        /** 最後に操作した線のスタイル */
        lastLineStyle: null as {
          color: string | undefined
          iconMdi: string | undefined
          iconDescription: string | undefined
          iconName: string | undefined
          iconImg: string | undefined
          width: number | undefined } | null,
        /** 画像アイコン選択ダイアログ表示フラグ */
        iconImgDialog: false,
        /** 画像アイコン選択ダイアログのターゲット */
        iconImgDialogTarget: null as 'point' | 'line' | 'waypoint' | null,
        /** 使用可能なアイコン画像リスト */
        iconImages,
        /** アップロード中フラグ */
        uploadLoading: false,
        /** サーバーから地図データ取得中フラグ */
        fetchMapLoading: false,
        /** サーバーIDが他のユーザーと競合しているか */
        serverIdConflict: false,
        /** 所有権移転確認ダイアログ表示フラグ */
        claimOwnershipDialog: false,
        /** 差分マージ用ベースデータ（最後にサーバーと同期した状態） */
        serverBaseData: null as Map | null,
        /** 地図データがローカルにもサーバーにも存在しない */
        mapNotFound: false,
        /** お気に入り状態 */
        isFavorite: false,
        /** QRコードダイアログの表示フラグ */
        mapQrDialog: false,
        /** QRコードのURL */
        mapQrUrl: '',
        /** コメントダイアログの表示フラグ */
        commentDialog: false,
        /** コメント一覧 */
        comments: [] as { id: number, userId: string, comment: string, createdAt: number }[],
        /** コメント読み込み中フラグ */
        commentsLoading: false,
        /** 新規コメント入力 */
        newComment: '',
        /** コメント送信中フラグ */
        commentLoading: false,
        /** QRコード読み込み中フラグ */
        qrLoading: false,
        /** JSONエクスポートダイアログの表示フラグ */
        jsonExportDialog: false,
        /** JSONエクスポートの内容 */
        jsonExportContent: '',
        /** JSONインポートダイアログの表示フラグ */
        jsonImportDialog: false,
        /** JSONインポートの入力内容 */
        jsonImportContent: '',
        /** JSONインポート処理中フラグ */
        jsonImportLoading: false,
        /** この地図からインポートダイアログの表示フラグ */
        importFromMapDialog: false,
        /** インポート処理中フラグ */
        importFromMapLoading: false,
      }
    },
    computed: {
      isEditorable () {
        if (this.mapData.ownerUserId === this.myProfile.userId) {
          return true
        } else if (this.mapData.ownerUserId === 'guest') {
          return true
        } else if (this.mapData.editorUserIds.includes(this.myProfile.userId ?? '')) {
          return true
        }
        return false
      },
      /** 全ての線の経由地点を線インデックス・地点インデックス付きでフラット化 */
      linesWaypointsFlat () {
        const result: { lineIdx: number, wpIdx: number, latlng: [number, number], waypoint: Waypoint }[] = []
        for (const [lineIdx, line] of this.mapData.lines.entries()) {
          for (const [wpIdx, wp] of line.waypoints.entries()) {
            result.push({ lineIdx, wpIdx, latlng: wp.latlng, waypoint: wp })
          }
        }
        return result
      },
      /** 全ての線の隣接する経由地点間の中間点を線インデックス付きでフラット化 */
      linesMidpointsFlat (): { lineIdx: number, afterWpIdx: number, latlng: [number, number] }[] {
        const result: { lineIdx: number, afterWpIdx: number, latlng: [number, number] }[] = []
        for (const [lineIdx, line] of this.mapData.lines.entries()) {
          for (let i = 0; i < line.waypoints.length - 1; i++) {
            const wp1 = line.waypoints[i]!
            const wp2 = line.waypoints[i + 1]!
            const midLat = (wp1.latlng[0] + wp2.latlng[0]) / 2
            const midLng = (wp1.latlng[1] + wp2.latlng[1]) / 2
            result.push({ lineIdx, afterWpIdx: i, latlng: [midLat, midLng] })
          }
        }
        return result
      },
      selectedLineCardIsActive () {
        return this.editMode && this.selectedWaypoint === null && this.selectedLine !== null
      },
      selectedWaypointIsActive () {
        return this.editMode && this.selectedWaypoint !== null
      },
      /** 全ての線のズームレベルに応じた間隔でのアイコン表示位置 */
      linesIntervalMarkers (): { lineIdx: number, latlng: [number, number] }[] {
        /** アイコンを表示する距離 */
        const INTERVAL = 5000
        const result: { lineIdx: number, latlng: [number, number] }[] = []
        for (const [lineIdx, line] of this.mapData.lines.entries()) {
          if (!line.iconMdi && !line.iconImg) continue
          if (line.waypoints.length < 2) continue
          const pts = this.catmullRomSpline(line.waypoints.map(w => w.latlng))
          let accumulated = 0
          let nextMark = INTERVAL
          for (let i = 0; i < pts.length - 1; i++) {
            const segDist = this.calcDistance(pts[i]!, pts[i + 1]!)
            /** 1区間に複数マークが含まれる場合も処理する */
            while (accumulated + segDist >= nextMark) {
              /** 区間内の補間比率でマーク位置を精確に求める */
              const ratio = (nextMark - accumulated) / segDist
              const lat = pts[i]![0] + ratio * (pts[i + 1]![0] - pts[i]![0])
              const lng = pts[i]![1] + ratio * (pts[i + 1]![1] - pts[i]![1])
              result.push({ lineIdx, latlng: [lat, lng] })
              nextMark += INTERVAL
            }
            accumulated += segDist
          }
        }
        return result
      },
      /** ローカルに保存されていて自分が編集可能な地図リスト（現在の地図を除く） */
      importableMapList () {
        const myUserId = this.myProfile.userId
        return this.maps.maps.filter((m: any) => {
          if (m.serverId === this.mapData.serverId) return false
          if (m.ownerUserId === myUserId) return true
          if (m.ownerUserId === 'guest') return true
          if (m.editorUserIds && m.editorUserIds.includes(myUserId ?? '')) return true
          return false
        })
      },
    },
    watch: {
      /** プロフィール詳細を押された時に、現在の住所を表示する */
      detailCardTarget: {
        handler: async function (newProfile, oldProfile) {
          // 編集モード中にピンの詳細カードが閉じられた時、スタイルを記憶する（waypoints がない = 線ではなくピン）
          if (!newProfile && oldProfile && this.editMode && !oldProfile.waypoints) {
            this.lastPointStyle = {
              iconMdi: oldProfile.iconMdi,
              iconColor: oldProfile.iconColor,
              iconImg: oldProfile.iconImg,
              iconDescription: oldProfile.description,
              iconName: oldProfile.name,
            }
          }
          if (!newProfile || !newProfile.location) {
            this.detailCardTargetAddress = null
            return
          } else {
            const latlng = newProfile.location
            this.detailCardTargetAddress = await this.getAddress(latlng[0], latlng[1])
            return
          }
        },
        deep: true,
        immediate: true,
      },
      /** 線の編集が完了した時、スタイルを記憶する */
      selectedLine (newLine, oldLine) {
        if (!newLine && oldLine && this.editMode) {
          this.lastLineStyle = {
            color: oldLine.color,
            width: oldLine.width,
            iconMdi: oldLine.iconMdi,
            iconImg: oldLine.iconImg,
            iconDescription: oldLine.description,
            iconName: oldLine.name,
          }
        }
      },
      /** ようこそ画面の表示状態を保存 */
      optionsDialog: {
        handler: async function (dialog: boolean) {
          localStorage.setItem('welcomeDialog', String(dialog))
        },
      },
      /** QRコードダイアログが開いたらQRコードを生成 */
      mapQrDialog: {
        handler: async function (val: boolean) {
          this.qrLoading = true
          if (!val) return
          const host = window.location.host
          this.mapQrUrl = `https://${host}/map/${this.mapData.serverId}`
          await this.$nextTick()
          const canvas = document.querySelector('#map-qr-canvas') as HTMLCanvasElement | null
          if (!canvas) return
          const ctx = canvas.getContext('2d')
          if (!ctx) return false
          const QRCode = (await import('qrcode')).default
          QRCode.toCanvas(canvas, this.mapQrUrl, { scale: 10 })
          canvas.style.height = '70vw'
          canvas.style.width = '70vw'
          canvas.style.maxWidth = '20em'
          canvas.style.maxHeight = '20em'
          const logo = new Image()
          logo.src = '/icon.png'
          logo.addEventListener('load', () => {
            const actualCanvasWidth = canvas.width
            const actualCanvasHeight = canvas.height
            const logoDiameter = actualCanvasWidth * (15 / 70)

            const logoWidth = logoDiameter
            const logoHeight = logoDiameter

            const startX = (actualCanvasWidth / 2) - (logoWidth / 2)
            const startY = (actualCanvasHeight / 2) - (logoHeight / 2)

            ctx.beginPath()
            const rad = logoDiameter / 2
            ctx.arc(actualCanvasWidth / 2, actualCanvasHeight / 2, rad, 0, Math.PI * 2, false)
            ctx.fillStyle = '#FFFFFF'
            ctx.fill()

            ctx.drawImage(logo, startX, startY, logoWidth, logoHeight)
            this.qrLoading = false
          })
        },
      },
      /** コメントダイアログが開いたらコメントを取得 */
      async commentDialog (val: boolean) {
        if (val) {
          await this.fetchComments()
        }
      },
      /** 編集モードがオフになったときに、詳細カードのターゲットをリセットする */
      editMode (newEditMode) {
        this.detailCardTarget = null
        this.drawingLine = null
        this.selectedLine = null
        this.selectedLineIndex = -1
        this.selectedWaypoint = null

        if (!newEditMode && this.params === 'create') {
          this.$router.back()
        }
      },
    },
    async mounted () {
      // @ts-ignore
      this.env = import.meta.env as any

      /** ルートパラメータ */
      const params = this.$route.params
      // @ts-ignore
      this.params = params['serverId'] as string

      /** ローカルストレージから最後に取得した位置情報を読み込み */
      const localStorageLatlng = localStorage.getItem('latlng')
      if (localStorageLatlng) {
        this.myLocation = JSON.parse(localStorageLatlng)
        /** バグるので0.1秒待ってから地図の中心を設定 */
        setTimeout(() => {
          this.leaflet.center = this.myLocation
          this.leaflet.zoom = 15
        }, 100)
      }

      /** ログイン情報 */
      if (this.myProfile.$state.guest == false) {
        if (this.myProfile.lastGetLocationTime) {
          this.myProfile.lastGetLocationTime = new Date(this.myProfile.lastGetLocationTime)
        }

        setTimeout(async () => {
          const token = this.myProfile.userToken
          const profile: any = await this.getProfile(this.myProfile.userId ?? '')
          if (profile) {
            profile.userToken = token
            profile.battery = this.myProfile.battery
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

      /** 位置情報の許可を確認 */
      Geolocation.checkPermissions().then(result => {
        if (result.location === 'granted') {
          this.setCurrentPosition()
        } else {
          Geolocation.requestPermissions().then(result => {
            if (result.location === 'granted') {
              this.setCurrentPosition()
            }
          }).catch(() => {
            this.requestGeoPermissionDialog = true
          })
        }
      })

      /** バックボタンのリスナーを追加 */
      App.addListener('backButton', () => {
        if (this.requestGeoPermissionDialog) {
          /** 位置情報利用許可ダイアログは閉じない */
        } else if (this.deletePointDialog) {
          /** 点削除確認ダイアログは閉じない */
        } else if (this.deleteLineDialog) {
          /** 線削除確認ダイアログは閉じない */
        } else if (this.deleteWaypointDialog) {
          /** 経由地点削除確認ダイアログは閉じない */
        } else if (this.claimOwnershipDialog) {
          /** 所有権移転確認ダイアログを閉じる */
          this.claimOwnershipDialog = false
        } else if (this.mapQrDialog) {
          /** QRコードダイアログを閉じる */
          this.mapQrDialog = false
        } else if (this.jsonExportDialog) {
          /** JSONエクスポートダイアログを閉じる */
          this.jsonExportDialog = false
        } else if (this.jsonImportDialog) {
          /** JSONインポートダイアログを閉じる */
          this.jsonImportDialog = false
        } else if (this.importFromMapDialog) {
          /** この地図からインポートダイアログを閉じる */
          this.importFromMapDialog = false
        } else if (this.commentDialog) {
          /** コメントダイアログを閉じる */
          this.commentDialog = false
        } else if (this.optionsDialog) {
          /** オプションダイアログを閉じる */
          this.optionsDialog = false
        } else if (this.drawingLine) {
          /** 線描画モードをキャンセルする */
          this.cancelDrawingLine()
        } else if (this.selectedWaypoint) {
          /** 経由地点編集カードを閉じる */
          this.selectedWaypoint = null
          this.selectedWaypointLineIdx = -1
          this.selectedWaypointWpIdx = -1
        } else if (this.detailCardTarget) {
          /** 詳細カードを閉じる */
          this.detailCardTarget = null
        } else if (this.selectedLine) {
          /** 選択中の線を閉じる */
          this.selectedLine = null
          this.selectedLineIndex = -1
        } else if (this.editMode) {
          /** 編集モードを終了するか確認する */
          this.editModeEndDialog = true
        } else {
          /** 1つ戻る */
          this.$router.back()
        }
      })

      /** 現在地を監視 */
      Geolocation.watchPosition({
        timeout: 2000,
        interval: 2000,
        enableHighAccuracy: false, // 最初は速度優先
        maximumAge: 30_000, // 30秒以内のキャッシュを許可
      }, position =>
        this.watchPosition(position),
      )

      /** 現在地を取得し、地図の中心も移動 */
      setTimeout(async () => {
        await this.setCurrentPosition()
      }, 1000)

      // 新規作成の場合は、地図データの初期値を設定
      if (!this.params || this.params === 'create') {
        this.mapData.name = `${this.myProfile.name ?? 'ゲスト'}の地図（${new Date().toLocaleDateString()}）`
        this.mapData.ownerUserId = this.myProfile.userId
        this.mapData.createdAt = Date.now()
        this.mapData.serverId = `${this.mapData.ownerUserId}-${this.mapData.createdAt}`
        this.editMode = true
      } else {
        // 既存の地図を読み込む
        const mapData = this.maps.maps.find(map => map.serverId === this.params)
        const hasLocalData = !!mapData
        if (mapData) {
          this.mapData = mapData
          /** 後方互換: linesフィールドが存在しない場合は初期化 */
          if (!this.mapData.lines) {
            this.mapData.lines = []
          }
        }
        // サーバーから最新データを取得
        if (this.mapData.ownerUserId === 'guest') {
          if (this.myProfile.guest) {
            Toast.show({
              text: 'オンラインにデータを保存するには、ログインしてください。',
            })
          } else {
            Toast.show({
              text: 'オンラインにデータを保存するには、サーバー情報からアップロードを行ってください。',
            })
          }
          // ローカルにもなければ不存在エラー
          if (!hasLocalData) {
            this.mapNotFound = true
          }
        } else {
          this.fetchMapFromServer(hasLocalData)
        }
      }
      // お気に入り状態を取得
      if (!this.myProfile.guest && this.params && this.params !== 'create') {
        this.fetchFavoriteStatus()
      }
    },
    unmounted () {
      App.removeAllListeners()
    },
    methods: {
      /** 地点のアイコン種別変更 */
      onPointIconTypeChange (type: string) {
        if (type === 'img') {
          this.detailCardTarget!.iconMdi = undefined
          this.iconImgDialogTarget = 'point'
          this.iconImgDialog = true
        } else {
          this.detailCardTarget!.iconImg = undefined
        }
      },
      /** 線のアイコン種別変更 */
      onLineIconTypeChange (type: string) {
        if (type === 'img') {
          this.selectedLine!.iconMdi = undefined
          this.iconImgDialogTarget = 'line'
          this.iconImgDialog = true
        } else {
          this.selectedLine!.iconImg = undefined
        }
      },
      /** 経由地点のアイコン種別変更 */
      onWaypointIconTypeChange (type: string) {
        if (type === 'img') {
          this.selectedWaypoint!.iconMdi = undefined
          this.iconImgDialogTarget = 'waypoint'
          this.iconImgDialog = true
        } else {
          this.selectedWaypoint!.iconImg = undefined
        }
      },
      /** 画像アイコンを選択する */
      selectIconImg (path: string) {
        if (this.iconImgDialogTarget === 'point' && this.detailCardTarget) {
          this.detailCardTarget.iconImg = path
          this.detailCardTarget.iconMdi = undefined
        } else if (this.iconImgDialogTarget === 'line' && this.selectedLine) {
          this.selectedLine.iconImg = path
          this.selectedLine.iconMdi = undefined
        } else if (this.iconImgDialogTarget === 'waypoint' && this.selectedWaypoint) {
          this.selectedWaypoint.iconImg = path
          this.selectedWaypoint.iconMdi = undefined
        }
        this.iconImgDialog = false
      },
      onMapClick (event: any) {
        /** クリックされた場所 */
        const latlng = event.latlng

        if (!this.editMode) {
          return
        }
        if (this.selectedLineCardIsActive) {
          return
        }

        /** 線描画モード中は経由地点を追加 */
        if (this.drawingLine) {
          this.drawingLine.waypoints.push({
            latlng: [latlng.lat, latlng.lng],
            name: undefined,
            description: undefined,
            iconImg: undefined,
            iconMdi: undefined,
            iconColor: undefined,
          })
          return
        }

        if (this.detailCardTarget) {
          this.detailCardTarget = null
          return
        }

        this.mapData.points.push({
          id: this.generateId(),
          latlng: [latlng.lat, latlng.lng],
          name: this.lastPointStyle?.iconName ?? `新しい地点${this.mapData.points.length + 1}`,
          description: this.lastPointStyle?.iconDescription,
          iconImg: this.lastPointStyle?.iconImg,
          iconMdi: this.lastPointStyle?.iconMdi ?? this.lastPointStyle?.iconImg ? undefined : 'MapMarkerAlt',
          iconColor: this.lastPointStyle?.iconColor,
          authorUserId: this.myProfile.userId,
        })
      },
      /** ドラッグ後にポイントの位置を更新する */
      onPointDragEnd (event: any, index: number) {
        const latlng = event.target.getLatLng()
        if (this.mapData.points[index]) {
          this.mapData.points[index].latlng = [latlng.lat, latlng.lng]
        }
      },
      /** ドラッグ後に保存済み経由地点の位置を更新する */
      onWaypointDragEnd (event: any, lineIdx: number, wpIdx: number) {
        const latlng = event.target.getLatLng()
        const wp = this.mapData.lines[lineIdx]?.waypoints[wpIdx]
        if (wp) {
          wp.latlng = [latlng.lat, latlng.lng]
        }
      },
      /** ドラッグ後に描画中経由地点の位置を更新する */
      onDrawingWaypointDragEnd (event: any, wpIdx: number) {
        const latlng = event.target.getLatLng()
        if (this.drawingLine?.waypoints[wpIdx]) {
          this.drawingLine.waypoints[wpIdx].latlng = [latlng.lat, latlng.lng]
        }
      },
      /** ローカルストアへの保存（共通処理） */
      performLocalSave () {
        let cnt = 0
        let found = false
        for (const map of this.maps.maps) {
          if (map.serverId === this.mapData.serverId) {
            // 上書き保存する
            this.maps.maps[cnt] = this.mapData
            found = true
            break
          }
          cnt++
        }
        if (!found) {
          // 新規保存する
          this.maps.maps.push(this.mapData)
        }
        Toast.show({ text: '保存しました！' })
        this.editMode = false
      },
      /** 編集内容を保存 */
      save () {
        if (!this.isEditorable) {
          Toast.show({ text: '保存できませんでした。編集権限がありません。' })
          return
        } else if (!this.mapData.name) {
          Toast.show({ text: '地図の名前を入力してください。' })
          return
        } else if (!this.mapData.serverId) {
          Toast.show({ text: '地図のIDを入力してください。' })
          return
        }
        // ログイン済みでオーナーがゲストの場合は所有権移転ダイアログを表示
        if (!this.myProfile.guest && this.mapData.ownerUserId === 'guest') {
          this.claimOwnershipDialog = true
          return
        }
        this.performLocalSave()
        // ログイン済みユーザーは自動的にサーバーにアップロード（ID競合がない場合）
        // アップロードはバックグラウンドで実行し、成否はトーストで通知する
        if (!this.myProfile.guest && !this.serverIdConflict) {
          this.uploadMap()
        }
      },
      /** 所有権を現在のユーザーに移転してローカル保存＋サーバーアップロード */
      claimOwnershipAndSave () {
        this.claimOwnershipDialog = false
        this.mapData.ownerUserId = this.myProfile.userId
        this.performLocalSave()

        this.uploadMap()
      },
      /** 線描画を開始する */
      startDrawingLine (point: { latlng: [number, number] }) {
        this.detailCardTarget = null
        this.drawingLine = {
          waypoints: [{
            latlng: [point.latlng[0], point.latlng[1]],
            name: undefined,
            description: undefined,
            iconImg: undefined,
            iconMdi: undefined,
            iconColor: undefined,
          }],
        }
      },
      /** 線描画を完了する */
      finishDrawingLine () {
        if (!this.drawingLine || this.drawingLine.waypoints.length < 2) return
        if (!this.mapData.lines) {
          this.mapData.lines = []
        }
        this.mapData.lines.push({
          id: this.generateId(),
          waypoints: this.drawingLine.waypoints,
          color: this.lastLineStyle?.color ?? '#3388ff',
          width: this.lastLineStyle?.width ?? 4,
          iconImg: this.lastLineStyle?.iconImg,
          iconMdi: this.lastLineStyle?.iconMdi ?? this.lastLineStyle?.iconImg ? undefined : 'OpenInFullOutlined',
          description: this.lastLineStyle?.iconDescription,
          name: this.lastLineStyle?.iconName ?? `線${this.mapData.lines.length + 1}`,
          authorUserId: this.myProfile.userId,
        })
        this.drawingLine = null
      },
      /** 線描画をキャンセルする */
      cancelDrawingLine () {
        this.drawingLine = null
      },
      /** 確認ダイアログで承認後に点を削除する */
      deletePoint () {
        this.mapData.points = this.mapData.points.filter(point => point !== this.detailCardTarget)
        this.detailCardTarget = null
        this.deletePointDialog = false
      },
      /** ピンを複製する（約100m東にずらして配置） */
      duplicatePoint () {
        const METERS_PER_DEGREE_AT_EQUATOR = 111_320
        const DUPLICATE_OFFSET_METERS = 100
        const target = this.detailCardTarget as Map['points'][number] | null
        if (!target) return
        const lat: number = target.latlng[0]
        const lng: number = target.latlng[1]
        const lngOffset = DUPLICATE_OFFSET_METERS / (METERS_PER_DEGREE_AT_EQUATOR * Math.cos(lat * Math.PI / 180))
        this.mapData.points.push({
          id: this.generateId(),
          latlng: [lat, lng + lngOffset],
          name: target.name,
          description: target.description,
          iconImg: target.iconImg,
          iconMdi: target.iconMdi,
          iconColor: target.iconColor,
          authorUserId: this.myProfile.userId,
        })
        this.detailCardTarget = null
      },
      /** 確認ダイアログで承認後に線を削除する */
      deleteLine () {
        this.mapData.lines.splice(this.selectedLineIndex, 1)
        this.selectedLine = null
        this.selectedLineIndex = -1
        this.deleteLineDialog = false
      },
      /** 確認ダイアログで承認後に経由地点を削除する */
      deleteWaypoint () {
        const line = this.selectedWaypointLineIdx >= 0
          ? this.mapData.lines[this.selectedWaypointLineIdx]
          : undefined
        if (line && this.selectedWaypointWpIdx >= 0 && this.selectedWaypointWpIdx < line.waypoints.length) {
          line.waypoints.splice(this.selectedWaypointWpIdx, 1)
        }
        this.selectedWaypoint = null
        this.selectedWaypointLineIdx = -1
        this.selectedWaypointWpIdx = -1
        this.deleteWaypointDialog = false
      },
      /** 経由地点の編集カードを開く */
      openWaypointEditor (lineIdx: number, wpIdx: number) {
        const wp = this.mapData.lines[lineIdx]?.waypoints[wpIdx]
        if (wp) {
          this.selectedWaypoint = wp
          this.selectedWaypointLineIdx = lineIdx
          this.selectedWaypointWpIdx = wpIdx
          this.selectedLine = null
        }
      },
      /** 経由地点を指定した位置の後に挿入する */
      insertWaypointAt (lineIdx: number, afterWpIdx: number, latlng: [number, number]) {
        const line = this.mapData.lines[lineIdx]
        if (!line) return
        const newWaypoint: Waypoint = {
          latlng,
          name: undefined,
          description: undefined,
          iconImg: undefined,
          iconMdi: undefined,
          iconColor: undefined,
        }
        line.waypoints.splice(afterWpIdx + 1, 0, newWaypoint)
      },
      /** 線の総延長距離（メートル）をスプライン上で計算する */
      calcLineDistance (waypoints: [number, number][]): number {
        if (waypoints.length < 2) return 0
        const pts = this.catmullRomSpline(waypoints)
        let total = 0
        for (let i = 0; i < pts.length - 1; i++) {
          total += this.calcDistance(pts[i]!, pts[i + 1]!)
        }
        return total
      },
      /** 距離をメートルまたはキロメートルの文字列に変換する */
      formatDistance (meters: number): string {
        const METERS_PER_KM = 1000
        if (meters >= METERS_PER_KM) {
          return `${(meters / METERS_PER_KM).toFixed(2)} km`
        }
        return `${Math.round(meters)} m`
      },
      /**
       * Catmull-Romスプライン補間で滑らかな曲線の座標列を生成する
       * @param points 経由地点の配列
       * @param segments 各区間の補間点数
       */
      catmullRomSpline (points: [number, number][], segments = 20): [number, number][] {
        if (points.length < 2) return points
        if (points.length === 2) return points
        /** ここに到達する時点で points.length >= 3 が保証されている */
        const result: [number, number][] = []
        /** 端点を複製してスプラインが両端の点を通るようにする */
        const pts: [number, number][] = [points[0]!, ...points, points.at(-1)!]
        /** i は 1 〜 pts.length-3 の範囲なので i-1〜i+2 は常に有効なインデックス */
        for (let i = 1; i < pts.length - 2; i++) {
          const p0 = pts[i - 1]!
          const p1 = pts[i]!
          const p2 = pts[i + 1]!
          const p3 = pts[i + 2]!
          for (let t = 0; t <= segments; t++) {
            const tt = t / segments
            const tt2 = tt * tt
            const tt3 = tt2 * tt
            const lat = 0.5 * (
              (2 * p1[0])
              + (-p0[0] + p2[0]) * tt
              + (2 * p0[0] - 5 * p1[0] + 4 * p2[0] - p3[0]) * tt2
              + (-p0[0] + 3 * p1[0] - 3 * p2[0] + p3[0]) * tt3
            )
            const lng = 0.5 * (
              (2 * p1[1])
              + (-p0[1] + p2[1]) * tt
              + (2 * p0[1] - 5 * p1[1] + 4 * p2[1] - p3[1]) * tt2
              + (-p0[1] + 3 * p1[1] - 3 * p2[1] + p3[1]) * tt3
            )
            result.push([lat, lng])
          }
        }
        return result
      },
      /** 位置情報監視のコールバック */
      async watchPosition (position: {
        coords: {
          [key: string]: any
        }
      } | null) {
        // console.log('最終更新:', new Date(this.lastUpdateMyLocationTime))
        if (position) {
          const lat: number = position.coords.latitude
          const lng: number = position.coords.longitude
          this.myLocation = [lat, lng]
        }

        return
      },
      /** 現在地を取得し、地図の中心も移動 */
      async setCurrentPosition () {
        /** 仮で最後に取得した位置情報を地図の中心に設定 */
        this.leaflet.center = this.myLocation
        /** バグるので0.5秒待つ */
        await new Promise(resolve => setTimeout(resolve, 500))
        this.leaflet.zoom = 15
      },
      /** 位置情報の許可を求める */
      async requestGeoPermission () {
        Geolocation.getCurrentPosition()
        this.requestGeoPermissionDialog = false
        return
      },
      /** 2地点間の距離（メートル）を計算 */
      calcDistance (latlng1: [number, number], latlng2: [number, number]) {
        const R = Math.PI / 180
        const lat1 = latlng1[0] * R
        const lat2 = latlng2[0] * R
        const lng1 = latlng1[1] * R
        const lng2 = latlng2[1] * R
        return 6371e3 * Math.acos(Math.sin(lat1) * Math.sin(lat2) + Math.cos(lat1) * Math.cos(lat2) * Math.cos(lng2 - lng1)) as number
      },
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
      /**
       * 緯度経度から住所を取得
       * @param lat 緯度
       * @param lng 経度
       */
      async getAddress (lat: number, lng: number) {
        const geoCodingUrl = new URL(
          'https://mreversegeocoder.gsi.go.jp/reverse-geocoder/LonLatToAddress',
        )
        geoCodingUrl.searchParams.set('lat', String(lat))
        geoCodingUrl.searchParams.set('lon', String(lng))
        return fetch(geoCodingUrl.toString())
          .then(res => res.json())
          .then(json => {
            const data = json.results
            const muniData = muniArray[data.muniCd]
            if (!muniData) {
              return '住所不明'
            }
            const splitedMuniData = muniData.split(',')
            const pref = splitedMuniData[1]
            const city = splitedMuniData[3]
            const address = data.lv01Nm
            return `${pref}${city}${address}`
          })
          .catch(() => {
            return null
          })
      },
      /** シェアダイアログ */
      async share (content: string, title = '') {
        await Share.share({
          url: content,
          title: title,
        })
      },
      /** 差分マージ用のランダムIDを生成（crypto.randomUUID優先） */
      generateId (): string {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
          return crypto.randomUUID()
        }
        return Math.random().toString(36).slice(2, 10) + Date.now().toString(36) + Math.random().toString(36).slice(2, 5)
      },
      /** IDを持っていないアイテムにIDを付与（後方互換・サーバーデータの読み込み時に使用） */
      ensureItemIds (items: any[]): any[] {
        return items.map((item: any) => item.id ? item : { ...item, id: this.generateId() })
      },
      /** 地図をサーバーにアップロードする */
      async uploadMap () {
        if (this.myProfile.guest) {
          Toast.show({ text: 'ログインが必要です。' })
          return
        }
        if (!this.mapData.serverId || !this.mapData.name) {
          Toast.show({ text: '地図のIDと名前を入力してください。' })
          return
        }
        this.uploadLoading = true
        try {
          const payload: any = { mapData: JSON.stringify(this.mapData) }
          // ベースデータがある場合は差分マージ用に送信
          if (this.serverBaseData) {
            payload.baseData = JSON.stringify(this.serverBaseData)
          }
          const res = await this.sendAjaxWithAuth('/uploadMap.php', {
            id: this.myProfile.userId,
            token: this.myProfile.userToken,
          }, payload, true) as any
          if (res.body.status === 'ok') {
            if (res.body.wasMerged) {
              // サーバーのマージ結果でローカルを更新
              this.mapData.points = this.ensureItemIds(res.body.points ?? [])
              this.mapData.lines = this.ensureItemIds(res.body.lines ?? [])
              Toast.show({ text: '他のユーザーの変更とマージしました！' })
            } else {
              Toast.show({ text: 'サーバーにアップロードしました！' })
            }
            // ベースデータを最新の状態に更新
            this.serverBaseData = { ...this.mapData }
          } else {
            Toast.show({ text: res.body.reason ?? 'アップロードに失敗しました。' })
          }
        } catch (error: any) {
          if (error?.ajaxInfo?.status === 403) {
            Toast.show({ text: '権限がないためアップロードできません。' })
          } else {
            Toast.show({ text: 'アップロードに失敗しました。' })
          }
        } finally {
          this.uploadLoading = false
        }
      },
      /** サーバーから地図データを取得する */
      async fetchMapFromServer (hasLocalData = true) {
        if (!this.params || this.params === 'create') return
        this.fetchMapLoading = true
        this.serverIdConflict = false
        try {
          const headers = {
            serverId: this.params,
          } as any
          if (!this.myProfile.guest) {
            headers.id = this.myProfile.userId
            headers.token = this.myProfile.userToken
          }
          const res = await this.sendAjaxWithAuth('/getMap.php', headers, null, false) as any
          if (res.body.status === 'ok' && res.body.map) {
            const serverMap = res.body.map
            // IDを持っていないアイテムにIDを付与（後方互換）
            serverMap.points = this.ensureItemIds(serverMap.points ?? [])
            serverMap.lines = this.ensureItemIds(serverMap.lines ?? [])
            this.mapData = {
              ...serverMap,
              lines: serverMap.lines,
              points: serverMap.points,
              sharedUserIds: serverMap.sharedUserIds ?? [],
              editorUserIds: serverMap.editorUserIds ?? [],
            }
            // ローカルストアも更新
            const idx = this.maps.maps.findIndex((m: any) => m.serverId === this.params)
            if (idx === -1) {
              this.maps.maps.push(this.mapData)
            } else {
              this.maps.maps[idx] = this.mapData
            }
            // 差分マージ用ベースデータを保存
            this.serverBaseData = { ...this.mapData }
          }
        } catch (error: any) {
          // サーバーにも地図がなく、ローカルにもない場合は「見つかりません」エラー
          if (!hasLocalData) {
            this.mapNotFound = true
          }
          if (error?.ajaxInfo?.status === 404) {
            //
          } else if (error?.ajaxInfo?.status === 403) {
            // 他のユーザーがこのサーバーIDを保有しているため、アップロード不可を通知
            this.serverIdConflict = true
          }
          // その他のエラー（ネットワーク等）はローカルデータをそのまま使用
        } finally {
          this.fetchMapLoading = false
        }
      },
      /** お気に入り状態を取得する */
      async fetchFavoriteStatus () {
        const res = await this.sendAjaxWithAuth('/getFavorites.php', {
          id: this.myProfile.userId,
          token: this.myProfile.userToken,
        }, null, false) as any
        if (res && res.body && res.body.status === 'ok') {
          this.isFavorite = res.body.favorites.some((f: any) => f.serverId === this.params)
        }
      },
      /** お気に入りをトグルする */
      async toggleFavorite () {
        if (this.myProfile.guest) return
        const res = await this.sendAjaxWithAuth('/favoriteMap.php', {
          id: this.myProfile.userId,
          token: this.myProfile.userToken,
        }, { serverId: this.mapData.serverId }) as any
        if (res && res.body && res.body.status === 'ok') {
          this.isFavorite = res.body.action === 'added'
        }
      },
      /** コメント一覧を取得する */
      async fetchComments () {
        if (!this.params || this.params === 'create') return
        this.commentsLoading = true
        const res = await this.sendAjaxWithAuth('/getComments.php', {
          serverId: this.params,
        }, null, false) as any
        if (res && res.body && res.body.status === 'ok') {
          this.comments = res.body.comments
        }
        this.commentsLoading = false
      },
      /** コメントを送信する */
      async submitComment () {
        const nospaceComment = this.newComment?.trim()
        if (!nospaceComment) {
          Toast.show({ text: 'コメントを入力してください。' })
          return
        }
        if (!this.newComment || this.myProfile.guest) return
        this.commentLoading = true
        const res = await this.sendAjaxWithAuth('/addComment.php', {
          id: this.myProfile.userId,
          token: this.myProfile.userToken,
        }, { serverId: this.mapData.serverId, comment: this.newComment }) as any
        if (res && res.body && res.body.status === 'ok') {
          this.newComment = ''
          await this.fetchComments()
        }
        this.commentLoading = false
      },
      /** JSONエクスポートダイアログを開く */
      openJsonExportDialog () {
        this.jsonExportContent = JSON.stringify(this.mapData, null, 2)
        this.jsonExportDialog = true
        this.optionsDialog = false
      },
      /** JSONファイルをダウンロードまたはAndroidネイティブ保存する */
      async downloadJsonExport () {
        const now = new Date()
        const pad = (n: number) => String(n).padStart(2, '0')
        const datePart = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`
        const timePart = `${pad(now.getHours())}${pad(now.getMinutes())}`
        const fileName = `${this.mapData.serverId}-${datePart}-${timePart}.json`
        if (Capacitor.getPlatform() === 'android') {
          try {
            await Filesystem.writeFile({
              path: fileName,
              data: this.jsonExportContent,
              directory: Directory.Documents,
              encoding: Encoding.UTF8,
            })
            Toast.show({ text: `ドキュメントに保存しました: ${fileName}` })
          } catch {
            Toast.show({ text: 'ファイルの保存に失敗しました。' })
          }
        } else {
          const blob = new Blob([this.jsonExportContent], { type: 'application/json' })
          const url = URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = fileName
          a.click()
          URL.revokeObjectURL(url)
        }
      },
      /** JSONファイル選択をトリガーする */
      triggerJsonFileUpload () {
        const input = this.$refs.jsonFileInput as HTMLInputElement | undefined
        if (input) input.click()
      },
      /** JSONファイルが選択された時の処理 */
      async onJsonFileSelected (event: Event) {
        const file = (event.target as HTMLInputElement).files?.[0]
        if (!file) return
        this.jsonImportContent = await file.text()
        // ファイル選択をリセットして同一ファイルの再選択を可能にする
        ;(event.target as HTMLInputElement).value = ''
      },
      /** JSONインポートを実行する */
      async performJsonImport () {
        if (!this.jsonImportContent.trim()) {
          Toast.show({ text: 'JSONを入力またはファイルをアップロードしてください。' })
          return
        }
        let parsed: any
        try {
          parsed = JSON.parse(this.jsonImportContent)
        } catch {
          Toast.show({ text: 'JSONの形式が正しくありません。' })
          return
        }
        const importPoints = Array.isArray(parsed.points) ? parsed.points : []
        const importLines = Array.isArray(parsed.lines) ? parsed.lines : []
        if (importPoints.length === 0 && importLines.length === 0) {
          Toast.show({ text: '点と線のデータが見つかりませんでした。' })
          return
        }
        this.jsonImportLoading = true
        try {
          let addedPoints = 0
          let updatedPoints = 0
          let addedLines = 0
          let updatedLines = 0

          const mergedPoints = [...this.mapData.points]
          for (const p of importPoints) {
            if (p.id) {
              const idx = mergedPoints.findIndex((existing: any) => existing.id === p.id)
              if (idx === -1) {
                mergedPoints.push({ ...p })
                addedPoints++
              } else {
                mergedPoints[idx] = { ...p }
                updatedPoints++
              }
            } else {
              mergedPoints.push({ ...p, id: this.generateId() })
              addedPoints++
            }
          }

          const mergedLines = [...this.mapData.lines]
          for (const l of importLines) {
            if (l.id) {
              const idx = mergedLines.findIndex((existing: any) => existing.id === l.id)
              if (idx === -1) {
                mergedLines.push({ ...l })
                addedLines++
              } else {
                mergedLines[idx] = { ...l }
                updatedLines++
              }
            } else {
              mergedLines.push({ ...l, id: this.generateId() })
              addedLines++
            }
          }

          this.mapData.points = mergedPoints
          this.mapData.lines = mergedLines
          this.performLocalSave()
          if (!this.myProfile.guest && !this.serverIdConflict) {
            await this.uploadMap()
          }
          const addMsg = (addedPoints + addedLines > 0) ? `追加: 点${addedPoints}件・線${addedLines}件` : ''
          const updMsg = (updatedPoints + updatedLines > 0) ? `更新: 点${updatedPoints}件・線${updatedLines}件` : ''
          Toast.show({ text: [addMsg, updMsg].filter(Boolean).join('、') + 'インポートしました。' })
          this.jsonImportDialog = false
          this.jsonImportContent = ''
        } finally {
          this.jsonImportLoading = false
        }
      },
      /** この地図からインポートダイアログを開く */
      openImportFromMapDialog () {
        this.importFromMapDialog = true
        this.optionsDialog = false
      },
      /** 選択した地図から点と線をマージインポートする */
      async performImportFromMap (sourceMap: any) {
        this.importFromMapLoading = true
        try {
          const importPoints = Array.isArray(sourceMap.points) ? sourceMap.points : []
          const importLines = Array.isArray(sourceMap.lines) ? sourceMap.lines : []
          const newPoints = importPoints.map((p: any) => ({ ...p, id: this.generateId() }))
          const newLines = importLines.map((l: any) => ({ ...l, id: this.generateId() }))
          this.mapData.points = [...this.mapData.points, ...newPoints]
          this.mapData.lines = [...this.mapData.lines, ...newLines]
          this.performLocalSave()
          if (!this.myProfile.guest && !this.serverIdConflict) {
            await this.uploadMap()
          }
          Toast.show({ text: `${newPoints.length}件の点と${newLines.length}件の線をインポートしました。` })
          this.importFromMapDialog = false
        } finally {
          this.importFromMapLoading = false
        }
      },
    },
  })
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
</style>
