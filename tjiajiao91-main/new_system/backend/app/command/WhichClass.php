<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

/**
 * 打印某个类实际加载的文件路径（用于排查重复类/加载歧义）
 *
 * 用法：
 * - php think debug:which-class app\\BaseController
 * - php think debug:which-class app\\controller\\admin\\Upload
 */
class WhichClass extends Command
{
    protected function configure()
    {
        $this->setName('debug:which-class')
            ->addArgument('class', Argument::REQUIRED, '类名（FQN，如 app\\\\BaseController）')
            ->setDescription('打印类的 ReflectionClass->getFileName()');
    }

    protected function execute(Input $input, Output $output)
    {
        $class = (string)$input->getArgument('class');
        $class = ltrim($class, '\\');

        if ($class === '') {
            $output->writeln('<error>请提供类名</error>');
            return 1;
        }

        try {
            $loader = null;
            foreach ((array)spl_autoload_functions() as $fn) {
                if (is_array($fn) && isset($fn[0]) && is_object($fn[0])) {
                    // Composer\Autoload\ClassLoader
                    if (get_class($fn[0]) === 'Composer\\Autoload\\ClassLoader' && method_exists($fn[0], 'findFile')) {
                        $loader = $fn[0];
                        break;
                    }
                }
            }

            if (!$loader) {
                $output->writeln('<error>未找到 Composer ClassLoader，无法定位文件</error>');
                return 4;
            }

            $file = (string)$loader->findFile($class);
            $output->writeln('<info>' . $class . '</info>');
            if ($file === '') {
                $output->writeln('<comment>(not found by composer)</comment>');
                return 2;
            }
            $output->writeln($file);
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('<error>解析失败：' . $e->getMessage() . '</error>');
            return 3;
        }
    }
}

