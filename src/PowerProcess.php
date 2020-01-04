<?php

namespace ProvisionOps\Tools;

use Psr\Log\LoggerAwareTrait;
use Robo\Common\OutputAwareTrait;
use Robo\Common\TimeKeeper;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Process\Process as BaseProcess;

class PowerProcess extends BaseProcess {

    /**
     * @var Style
     */
    public $io;
    public $successMessage = 'Process Succeeded';
    public $failureMessage = 'Process Failed';
    public $duration = '';

    /**
     * @param $io Style
     */
    public function setIo(Style $io) {
        $this->io = $io;
    }

    /**
     * PowerProcess constructor.
     * @param $commandline
     * @param Style $io
     * @param null $cwd
     * @param array|null $env
     * @param null $input
     * @param int $timeout
     * @param array $options
     */
    public function __construct($commandline, $io, $cwd = null, array $env = null, $input = null, $timeout = 60, array $options = array())
    {
        $this->setIo($io);
        parent::__construct($commandline, $cwd, $env, $input, $timeout, $options);
    }


    /**
     * Runs the process.
     *
     * @param callable|null $callback A PHP callback to run whenever there is some
     *                                output available on STDOUT or STDERR
     *
     * @return int The exit status code
     *
     * @throws RuntimeException When process can't be launched
     * @throws RuntimeException When process stopped after receiving signal
     * @throws LogicException   In case a callback is provided and output has been disabled
     *
     * @final
     */
    public function run(callable $callback = null, array $env = []): int
    {
        $this->io->write(" <comment>$</comment> {$this->getCommandLine()} <fg=black>Output:/path/to/file</>");

        $timer = new TimeKeeper();
        $timer->start();

        $exit = parent::run(function ($type, $buffer) {
          if (getenv('PROVISION_PROCESS_OUTPUT') == 'direct') {
            echo $buffer;
          }
          else {
            $lines = explode("\n", $buffer);
            foreach ($lines as $line) {
              $this->io->outputBlock(trim($line), false, false);
            }
          }
        }, $env);

        $timer->stop();
        $this->duration = $timer->formatDuration($timer->elapsed());

        // @TODO: Optionally print something helpful but hideable here.
        // $suffix = "<fg=black>Output: /path/to/file</>";
        $suffix = '';

        if ($exit == 0) {
            $this->io->newLine();
            $this->io->writeln(" <info>✔</info> {$this->successMessage} in {$this->duration} $suffix");
        }
        else {
            $this->io->newLine();
            $this->io->writeln(" <fg=red>✘</> {$this->failureMessage} in {$this->duration} {$suffix}");
        }

        return $exit;

    }
}
