<?php

namespace Economia\WebViewFileChooser;

use Economia\WebViewFileChooser\Commands\PreCompileCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the webview file-chooser NativePHP plugin. The NativePHP Android
 * scaffold ships without WebChromeClient.onShowFileChooser, so every
 * <input type="file"> is a dead click on-device. The pre-compile hook patches
 * the generated Kotlin sources on each build (they are regenerated from
 * templates/backups, so a one-off edit does not survive).
 */
class WebViewFileChooserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PreCompileCommand::class,
            ]);
        }
    }
}
