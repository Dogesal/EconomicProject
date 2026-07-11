<?php

namespace Economia\WebViewFileChooser\Commands;

use Native\Mobile\Plugins\Commands\NativePluginHookCommand;

/**
 * Patches the generated Android scaffold so <input type="file"> opens the
 * system document picker. Two files are involved:
 *
 * - WebViewManager.kt: adds WebChromeClient.onShowFileChooser, delegating to
 *   MainActivity (without it the webview silently swallows the click).
 * - MainActivity.kt: adds the ActivityResult launcher and showFileChooser().
 *
 * MainActivity.kt is rewritten on every build by the splashscreen plugin from
 * its backup copy (nativephp-backups/MainActivity.kt.original), so that backup
 * gets patched too — whichever file the final build uses, the patch is in it.
 * All patches are idempotent (guarded by str_contains).
 */
class PreCompileCommand extends NativePluginHookCommand
{
    protected $signature = 'nativephp:webview-file-chooser:pre-compile';

    protected $description = 'Inject WebChromeClient.onShowFileChooser into the Android scaffold';

    public function handle(): int
    {
        if (! $this->isAndroid()) {
            return self::SUCCESS;
        }

        $this->patchWebViewManager();
        $this->patchMainActivity($this->buildPath().'/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt');
        $this->patchMainActivity($this->buildPath().'/app/src/main/nativephp-backups/MainActivity.kt.original');

        return self::SUCCESS;
    }

    protected function patchWebViewManager(): void
    {
        $path = $this->buildPath().'/app/src/main/java/com/nativephp/mobile/network/WebViewManager.kt';

        if (! file_exists($path)) {
            $this->warn("WebViewManager.kt not found at: {$path}");

            return;
        }

        $content = file_get_contents($path);

        if (str_contains($content, 'onShowFileChooser')) {
            return;
        }

        $anchor = <<<'KT'
            override fun onConsoleMessage(consoleMessage: ConsoleMessage): Boolean {
                Log.d(
                    "$TAG-Console",
                    "${consoleMessage.message()} -- From line ${consoleMessage.lineNumber()}"
                )
                return true
            }
KT;

        $addition = <<<'KT'

            override fun onShowFileChooser(
                webView: WebView,
                filePathCallback: ValueCallback<Array<Uri>>,
                fileChooserParams: FileChooserParams
            ): Boolean {
                val activity = context as? MainActivity ?: return false
                return activity.showFileChooser(filePathCallback, fileChooserParams)
            }
KT;

        if (! str_contains($content, $anchor)) {
            $this->error('WebViewManager.kt: onConsoleMessage anchor not found — scaffold layout changed, file chooser NOT injected.');

            return;
        }

        file_put_contents($path, str_replace($anchor, $anchor.$addition, $content));
        $this->info('Android: WebViewManager.kt patched (onShowFileChooser)');
    }

    protected function patchMainActivity(string $path): void
    {
        if (! file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);

        if (str_contains($content, 'showFileChooser')) {
            return;
        }

        $importsAnchor = 'import android.webkit.CookieManager';
        $imports = <<<'KT'
import android.webkit.CookieManager
import android.content.ActivityNotFoundException
import android.net.Uri
import android.webkit.ValueCallback
import androidx.activity.result.contract.ActivityResultContracts
KT;

        $fieldsAnchor = '    // Status bar style configuration - replaced during build';
        $fields = <<<'KT'
    // Pending callback from WebChromeClient.onShowFileChooser (<input type="file">)
    private var filePathCallback: ValueCallback<Array<Uri>>? = null

    private val fileChooserLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        val callback = filePathCallback ?: return@registerForActivityResult
        filePathCallback = null
        callback.onReceiveValue(
            WebChromeClient.FileChooserParams.parseResult(result.resultCode, result.data)
        )
    }

    // Status bar style configuration - replaced during build
KT;

        $methodAnchor = '    override fun getWebView(): WebView {';
        $method = <<<'KT'
    /**
     * Launches the system document picker for <input type="file"> elements.
     * Uses a generic wildcard GET_CONTENT intent instead of
     * FileChooserParams.createIntent() because accept extensions like ".sqlite"
     * don't map to a MIME type and would leave files greyed out in the picker.
     */
    fun showFileChooser(
        callback: ValueCallback<Array<Uri>>,
        params: WebChromeClient.FileChooserParams
    ): Boolean {
        filePathCallback?.onReceiveValue(null)
        filePathCallback = callback

        val intent = Intent(Intent.ACTION_GET_CONTENT).apply {
            addCategory(Intent.CATEGORY_OPENABLE)
            type = "*/*"
        }

        return try {
            fileChooserLauncher.launch(Intent.createChooser(intent, params.title ?: "Seleccionar archivo"))
            true
        } catch (e: ActivityNotFoundException) {
            Log.e("FileChooser", "No activity to handle file chooser", e)
            filePathCallback = null
            false
        }
    }

    override fun getWebView(): WebView {
KT;

        foreach ([$importsAnchor, $fieldsAnchor, $methodAnchor] as $anchor) {
            if (! str_contains($content, $anchor)) {
                $this->error("MainActivity: anchor not found ({$anchor}) — scaffold layout changed, file chooser NOT injected into {$path}.");

                return;
            }
        }

        $content = str_replace($importsAnchor, $imports, $content);
        $content = str_replace($fieldsAnchor, $fields, $content);
        $content = str_replace($methodAnchor, $method, $content);

        file_put_contents($path, $content);
        $this->info("Android: patched {$path} (showFileChooser)");
    }
}
