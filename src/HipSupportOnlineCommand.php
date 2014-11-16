<?php 

namespace Estey\HipSupport;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class HipSupportOnlineCommand extends Command
{
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'hipsupport:online';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Bring HipSupport Online';

    /**
     * The console command description.
     * @var Estey\HipSupport\HipSupport
     */
    private $hipsupport;

    /**
     * Create a new HipSupportOnlineCommand instance.
     *
     * @param Estey\HipSupport\HipSupport $hipsupport
     * @return void
     */
    public function __construct(HipSupport $hipsupport)
    {
        parent::__construct();
        $this->hipsupport = $hipsupport;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->hipsupport->online($this->argument('minutes'));
        $this->info('HipSupport is Online.');
    }

    /**
     * The console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [
                'minutes',
                InputArgument::OPTIONAL,
                'The number of minutes to bring HipSupport online.'
            ]
        ];
    }
}
