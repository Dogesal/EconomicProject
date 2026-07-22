package com.economia.voicewidget

import android.app.PendingIntent
import android.appwidget.AppWidgetManager
import android.appwidget.AppWidgetProvider
import android.content.Context
import android.content.Intent
import android.util.Log
import android.widget.RemoteViews
import com.nativephp.mobile.R

/**
 * Widget de pantalla de inicio "Mi Economía · Voz": un botón de micrófono
 * que lanza VoiceCaptureActivity (transparente) para dictar un gasto, pago,
 * deuda o consulta sin abrir la app.
 */
class VoiceWidgetProvider : AppWidgetProvider() {

    companion object {
        private const val TAG = "VoiceWidget"
    }

    override fun onUpdate(context: Context, appWidgetManager: AppWidgetManager, appWidgetIds: IntArray) {
        Log.i(TAG, "onUpdate for ${appWidgetIds.size} widget(s)")

        appWidgetIds.forEach { appWidgetId ->
            val views = RemoteViews(context.packageName, R.layout.voice_widget)

            val tapIntent = PendingIntent.getActivity(
                context,
                appWidgetId,
                Intent(context, VoiceCaptureActivity::class.java).apply {
                    flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                },
                PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
            )

            // El click va al root y también a los hijos: algunos launchers
            // (MIUI entre ellos) no propagan el tap del contenedor raíz.
            views.setOnClickPendingIntent(R.id.voice_widget_root, tapIntent)
            views.setOnClickPendingIntent(R.id.voice_widget_icon, tapIntent)
            views.setOnClickPendingIntent(R.id.voice_widget_label, tapIntent)

            appWidgetManager.updateAppWidget(appWidgetId, views)
            Log.i(TAG, "Widget $appWidgetId updated with tap intent")
        }
    }
}
