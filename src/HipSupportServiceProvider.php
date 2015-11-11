<?php

namespace Estey\HipSupport;

use Illuminate\Support\ServiceProvider;
use HipChat\HipChat;

class HipSupportServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }
    
    /**
     * Bootstrap the application events.
     * @return void
     */
    public function boot()
    {
        $this->package('estey/hipsupport', null, __DIR__);

        $path = '../app/config/packages/estey/hipsupport/';
        if ($this->app['files']->isDirectory($path)) {
            $this->app['config']->package('estey/hipsupport', $path);
        }
        
        $this->registerHipSupport();
        $this->registerHipSupportOnlineCommand();
        $this->registerHipSupportOfflineCommand();

        $this->registerCommands();
    }

    /**
     * Create a new HipSupport instance.
     *
     * @return Estey\HipSupport\HipSupport
     */
    protected function initHipSupport()
    {
        return new HipSupport(
            new HipChat($this->getToken(), $this->getTarget()),
            $this->app['config'],
            $this->app['cache']
        );
    }

    /**
     * Register hipsupport
     *
     * @return Estey\HipSupport\HipSupport
     */
    protected function registerHipSupport()
    {
        $hipsupport = $this->initHipSupport();
        $this->app['hipsupport'] = $this->app->share(
            function ($app) use ($hipsupport) {
                return $hipsupport;
            }
        );
    }

    /**
     * Register hipsupport.online
     *
     * @return Estey\HipSupport\HipSupportOnlineCommand
     */
    protected function registerHipSupportOnlineCommand()
    {
        $hipsupport = $this->initHipSupport();
        $this->app['hipsupport.online'] = $this->app->share(
            function ($app) use ($hipsupport) {
                return new HipSupportOnlineCommand($hipsupport);
            }
        );
    }

    /**
     * Register hipsupport.offline
     *
     * @return Estey\HipSupport\HipSupportOfflineCommand
     */
    protected function registerHipSupportOfflineCommand()
    {
        $hipsupport = $this->initHipSupport();
        $this->app['hipsupport.offline'] = $this->app->share(
            function ($app) use ($hipsupport) {
                return new HipSupportOfflineCommand($hipsupport);
            }
        );
    }

    /**
     * Make commands visible to Artisan
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands(
            'hipsupport.online',
            'hipsupport.offline'
        );
    }

    /**
     * Get HipChat API Token from Config.
     *
     * @return string
     */
    private function getToken()
    {
        return $this->app['config']->get('hipsupport::config.token');
    }

    /**
     * Get HipChat API Token from Config.
     *
     * @return string
     */
    private function getTarget()
    {
        return $this->app['config']->get('hipsupport::config.target');
    }

}
