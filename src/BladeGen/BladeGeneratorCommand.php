<?php namespace BladeGen;

use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem as Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\Container\Container as Container;
use Illuminate\View\Factory;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\View as View;
use Illuminate\Events\Dispatcher;

class BladeGeneratorCommand extends Command {
	public $path;
	public $base;
	public $fs;

	protected $name = 'generate';

	public function __construct() {
		parent::__construct();

		require_once dirname(__FILE__) . '/helpers.php';

		$this->fs = new Filesystem();
		$this->base = dirname(dirname(dirname(__FILE__)));
		$this->path = $this->base . '/views';

		$this->builder = new HtmlBuilder();
	}

	public function render($view, array $data = []) {
		$data += [
			'view' => $view,
			'html' => $this->builder
		];

		$this->info('Compiling ' . $view . '...');

		$path = $this->path . '/' . $view . '.blade.php';

		// this path needs to be array
		$viewFinder = new FileViewFinder($this->fs, [ realpath($this->path) ]);

		// use blade instead of phpengine
		// pass in filesystem object and cache path
		$compiler = new BladeCompiler($this->fs, $this->base . '/storage');
		$engine = new CompilerEngine($compiler);

		// create a dispatcher
		$dispatcher = new Dispatcher(new Container);

		$resolver = new EngineResolver();

		$resolver->register('blade', function() use ($engine) {
			return $engine;
		});

		// build the factory
		$factory = new Factory(
			$resolver,
			$viewFinder,
			$dispatcher
		);

		// this path needs to be string
		$viewObj = new View(
			$factory,
			$engine,
			$view,
			$path,
			$data
		);

		$this->fs->put($this->base . '/output/' . $view . '.html', $viewObj->render());
	}

	public function handle() {
		$glob = $this->fs->glob($this->base . '/views/*.blade.php');

		$this->info('Compiling ' . count($glob) . ' files...');

		foreach ($glob as $path) {
			$name = basename($path);
			$name = substr($name, 0, strpos($name, '.'));
			$this->render($name);
		}

		$this->info('Finished!');
	}
}