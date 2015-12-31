<?php
/*
 * This file is part of the frenzy-framework package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
class CacheClear extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('cache:clear');
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->write(' <comment>Clearing Cache. Please, wait...</comment> ');

        try {
            $kernel = $this->getKernel();

            $kernel->clearCache();

            $output->writeln('[ <info>OK</info> ]');

            $output->writeln('');
            $output->writeln(' <info>Task Complete.</info>');
        } catch (\Exception $e) {
            $output->writeln(
                '[ <error>ERROR</error> ] - Exception ('.get_class($e).') - Code: '.
                $e->getCode().' - Message: '.$e->getMessage()
            );
        }

        $output->writeln('');
    }

}