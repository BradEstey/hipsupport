<?php namespace Estey\HipSupport;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class HipSupportOfflineCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'hipsupport:offline';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Take HipSupport Offline';

	/**
	 * The console command description.
	 *
	 * @var Estey\HipSupport\HipSupport
	 */
	private $hipsupport;

	/**
	 * Create a new HipSupportOfflineCommand instance.
	 *
	 * @param  Estey\HipSupport\HipSupport  $hipsupport
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
		$this->hipsupport->offline();
		$this->info('HipSupport is Offline.');
	}

}